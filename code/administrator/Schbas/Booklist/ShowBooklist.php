<?php

require_once 'Booklist.php';
require_once PATH_INCLUDE . '/Schbas/Loan.php';

class ShowBooklist extends Booklist {

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);
		if(isset($_GET['ajax'])) {
			$this->ajaxBooklist();
		}
		else {
			$loanHelper = new \Babesk\Schbas\Loan($this->_dataContainer);
			$schoolyear = $loanHelper->schbasPreparationSchoolyearGet();
			$this->_smarty->assign('preparationSchoolyear', $schoolyear);
			$this->displayTpl('show-booklist.tpl');
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function entryPoint($dataContainer) {

		parent::entryPoint($dataContainer);
		parent::moduleTemplatePathSet();
	}

	protected function ajaxBooklist() {

		$query = $this->booklistQueryGet();
		$paginator = new \Doctrine\ORM\Tools\Pagination\Paginator(
			$query, $fetchJoinCollection = true
		);
		$books = $this->bookArrayPopulate($paginator);
		$pagecount = $this->pagecountGet($paginator);
		die(json_encode(array(
			'pagecount' => $pagecount, 'books' => $books
		)));
	}

	/**
	 * Creates and returns a query that fetches the book-variables
	 * @return QueryBuilder A Doctrine-Query-Builder
	 */
	protected function booklistQueryGet() {

		$query = $this->_em->createQueryBuilder()
			->select(array('b, s'))
			->from('Babesk\ORM\SchbasBook', 'b')
			->leftJoin('b.subject', 's');
		if(isset($_POST['filterFor']) && !isBlank($_POST['filterFor'])) {
			$query->where('b.title LIKE :filterVar')
			->orWhere('b.author LIKE :filterVar')
			->orWhere('b.class LIKE :filterVar')
			->orWhere('b.bundle LIKE :filterVar')
			->orWhere('b.price LIKE :filterVar')
			->orWhere('b.isbn LIKE :filterVar')
			->orWhere('b.publisher LIKE :filterVar')
			->orWhere('s.name LIKE :filterVar')
			->orWhere('s.abbreviation LIKE :filterVar')
			->setParameter('filterVar', '%' . $_POST['filterFor'] . '%');
		}
		$query->setFirstResult($_POST['pagenumber'] * $_POST['booksPerPage'])
			->setMaxResults($_POST['booksPerPage']);
		return $query;
	}

	/**
	 * Populates the array of books to be returned to the client
	 * @param  Paginator $paginator doctrines paginator to fetch the data
	 * @return array                An array of bookdata
	 */
	protected function bookArrayPopulate($paginator) {

		$books = array();
		foreach($paginator as $book) {
			$bookAr = array(
				'id' => $book->getId(),
				'title' => $book->getTitle(),
				'author' => $book->getAuthor(),
				'gradelevel' => $book->getClass(),
				'bundle' => $book->getBundle(),
				'price' => $book->getPrice(),
				'isbn' => $book->getIsbn(),
				'publisher' => $book->getPublisher()
			);
			$bookAr['subject'] = ($book->getSubject()) ?
				$book->getSubject()->getName() : '';
			$books[$book->getId()] = $bookAr;
		}
		$invData = $this->booksInventoryDataGet($paginator);
		$books = $this->bookArrayMerge($books, $invData);
		return $books;
	}

	/**
	 * Calculates the pagecount of the booklist
	 * @param  Paginator $paginator doctrines paginator fed with the bookquery
	 * @return int                  The count of showable pages
	 */
	protected function pagecountGet($paginator) {

		$bookcount = count($paginator);
		// No division by zero, never show zero sites
		if($_POST['booksPerPage'] != 0 && $bookcount > 0) {
			$pagecount = ceil($bookcount / (int)$_POST['booksPerPage']);
		}
		else {
			$pagecount = 1;
		}
		return $pagecount;
	}

	/*====================================================
	=            Additional Bookdata to fetch            =
	====================================================*/

	/**
	 * Fetches the book-inventory-data
	 * @param  Paginator $paginator doctrines paginator containing the books
	 * @return array                an array containing the data for each book
	 *                              '<bookId>' => [
	 *                                  'highestExemplarNumber' => '<count>'
	 *                                  'exemplarsLent' => '<count>'
	 *                                  'allExemplars' => '<count>'
	 *                                  'exemplarsInStock' => '<count>'
	 *                                  'exemplarsNeeded' => '<count>'
	 *                                  'exemplarsToBuy' => '<count>'
	 *                              ]
	 */
	protected function booksInventoryDataGet($paginator) {

		try {
			$booksData = $this->booksHighestInventoryNumberGet($paginator);
			$booksData = $this->bookArrayMerge(
				$booksData, $this->bookExemplarsLentGet($paginator)
			);
			$booksData = $this->bookArrayMerge(
				$booksData, $this->bookExemplarCountGet($paginator)
			);
			$booksData = $this->bookArrayMerge(
				$booksData, $this->booksInStockCalculate($booksData)
			);
			$booksData = $this->bookArrayMerge(
				$booksData, $this->bookExemplarsNeededGet($paginator)
			);
			$booksData = $this->bookArrayMerge(
				$booksData, $this->bookExemplarsSelfpayedGet($paginator)
			);

			$booksData = $this->bookArrayMerge(
				$booksData, $this->bookExemplarsToBuyGet($booksData)
			);

		} catch (Exception $e) {
			$this->_logger->log('Error fetching the booksInventoryData',
				'Notice', Null, json_encode(array('msg' => $e->getMessage())));
			return array();
		}
		return $booksData;
	}

	/**
	 * Gets the highest exemplar number for the books in paginator
	 * @param  Paginator $paginator doctrines paginator containing the books
	 * @return array                an array containing the data for each book
	 *                              '<bookId>' => [
	 *                                  'highestExemplarNumber' => '<count>'
	 *                              ]
	 */
	protected function booksHighestInventoryNumberGet($paginator) {

		$invNums = array();
		$query = $this->_em->createQuery(
			'SELECT MAX(i.exemplar) FROM DM:SchbasInventory i
				JOIN i.book b
				WHERE b.id = :id
		');
		foreach($paginator as $book) {
			$res = $query->setParameter('id', $book->getId())
				->getSingleScalarResult();
			if(!empty($res)) {
				$invNums[$book->getId()]['highestExemplarNumber'] = (int) $res;
			}
			else {
				$invNums[$book->getId()]['highestExemplarNumber'] = 0;
			}
		}
		return $invNums;
	}

	/**
	 * Gets the count of exemplars of the given books that are lent
	 * @param  Paginator $paginator a doctrine-paginator containing the books
	 *                              which lent exemplars (inventory) to count
	 * @return array                '<bookId>' => [
	 *                                  'exemplarsLent' => '<lentCount>'
	 *                              ]
	 */
	protected function bookExemplarsLentGet($paginator) {

		$booksLent = array();
		$query = $this->_em->createQuery(
			'SELECT COUNT(l) FROM DM:SchbasBook b
				INNER JOIN b.exemplars e
				INNER JOIN e.lending l
				WHERE b.id = :id
		');
		foreach($paginator as $book) {
			$res = $query->setParameter('id', $book->getId())
				->getSingleScalarResult();
			$booksLent[$book->getId()]['exemplarsLent'] = (int)$res;
		}

		return $booksLent;
	}

	/**
	 * Gets the count of all existing exemplars of the given books
	 * @param  Paginator $paginator a doctrine-paginator containing the books
	 *                              which lent exemplars (inventory) to count
	 * @return array                '<bookId>' => [
	 *                                  'allExemplars' => '<exemplarCount>'
	 *                              ]
	 */
	protected function bookExemplarCountGet($paginator) {

		$booksInventory = array();
		$query = $this->_em->createQuery(
			'SELECT COUNT(e.id) FROM DM:SchbasBook b
				INNER JOIN b.exemplars e
				WHERE b.id = :id
		');
		foreach($paginator as $book) {
			$res = $query->setParameter('id', $book->getId())->getResult();
			$booksInventory[$book->getId()]['allExemplars'] = $res[0][1];
		}

		return $booksInventory;
	}

	/**
	 * Calculates the books in stock by allExemplars - lentExemplars
	 * @param  array  $bookData  the already fetched books-data containing
	 *                           allExemplars and exemplarsLent
	 * @return array             The books-data with the book-exemplar-count
	 *                           in stock
	 *                           '<bookId>' => [
	 *                               'exemplarsInStock' => '<exemplarCount>'
	 *                               ...
	 *                           ]
	 */
	protected function booksInStockCalculate($bookData) {

		$booksInStock = array();
		foreach($bookData as $bookId => $data) {
			$booksInStock[$bookId]['exemplarsInStock'] =
				$data['allExemplars'] - $data['exemplarsLent'];
		}
		return $booksInStock;
	}

	/**
	 * Calculates the amount of book-exemplars needed
	 * The amount needs to be calculated by two methods; The senior grades
	 * have an additional pool of books they need to lend, called
	 * 'special_course'
	 * @todo   It doesnt consider if the user already has the book
	 * @param  Paginator $paginator doctrines paginator containing the books
	 * @return array                book-ids as the key with the values being
	 *                              the amount of book-exemplars needed
	 *                              '<bookId>' => [
	 *                                  'exemplarsNeeded' => '<exemplarCount>'
	 *                              ]
	 */
	protected function bookExemplarsNeededGet($paginator) {

		$loan = new \Babesk\Schbas\Loan($this->_dataContainer);
		$trigger = $this->specialCourseTriggerGet();
		$booksNeeded = array();
		foreach($paginator as $book) {
			$count = $loan->amountOfInventoryAssignedToUsersGet($book);
			$booksNeeded[$book->getId()]['exemplarsNeeded'] = $count;
		}
		return $booksNeeded;
	}

	/**
	 * Calculates the amount of book-exemplars that users buy for themselfes
	 * @param  Paginator $paginator doctrines paginator containing the books
	 * @return array                book-ids as the key with the values being
	 *                              the amount of book-exemplars needed
	 *                              '<bookId>' => [
	 *                                  'exemplarsSelfpayed' =>
	 *                                      '<exemplarCount>'
	 *                              ]
	 */
	protected function bookExemplarsSelfpayedGet($paginator) {

		$loanHelper = new \Babesk\Schbas\Loan($this->_dataContainer);
		$prepSchoolyear = $loanHelper->schbasPreparationSchoolyearGet();
		$booksSelfpayed = [];
		foreach($paginator as $book) {
			$query = $this->_em->createQuery(
				'SELECT COUNT(u) FROM DM:SchbasBook b
				INNER JOIN b.selfpayingBookEntities sb
				INNER JOIN sb.user u
				INNER JOIN u.attendances a WITH a.schoolyear = :schoolyear
				WHERE b = :book
			');
			$query->setParameter('schoolyear', $prepSchoolyear);
			$query->setParameter('book', $book);
			$count = $query->getSingleScalarResult();
			$booksSelfpayed[$book->getId()]['exemplarsSelfpayed'] = $count;
		}
		return $booksSelfpayed;
	}

	/**
	 * Checks if the subject is listed in a subject-group like religion
	 * @param  string $subject         The abbreviation for a subject like 'EN'
	 * @param  bool   $isSpecialCourse If the special_courses list should be
	 *                                 considered, too
	 * @return bool                    True if the subject is listed, false if
	 *                                 not
	 */
	protected function bookSubjectIsListedCheck($subject, $isSpecialCourse) {

		if(empty($this->_allReligions) &&
			empty($this->_allForeignLanguages) &&
			empty($this->_allSpecialCourses)
		) {
			$this->bookSubjectIsListedCacheFill();
		}

		return (in_array($subject, $this->_allReligions) ||
			in_array($subject, $this->_allForeignLanguages) ||
			(
				$isSpecialCourse &&
				in_array($subject, $this->_allSpecialCourses)
			)
		);
	}

	/**
	 * Caches the lists defining which subject is assigned to which group
	 */
	protected function bookSubjectIsListedCacheFill() {

		$globalSettings = $this->_em
			->getRepository('DM:SystemGlobalSettings');
		$rel = $globalSettings->findOneByName('religion')->getValue();
		$this->_allReligions = explode('|', $rel);
		$lan = $globalSettings->findOneByName('foreign_language')->getValue();
		$this->_allForeignLanguages = explode('|', $lan);
		$course = $globalSettings->findOneByName('special_course')->getValue();
		$this->_allSpecialCourses = explode('|', $course);
	}

	/**
	 * Calculates the amount of book-exemplars to buy
	 * Never gets negative, will stop at 0
	 * @param  array  $bookData The already fetched bookdata
	 * @return array                '<bookId>' => [
	 *                                  'exemplarsToBuy' => '<exemplarsToBuy>'
	 *                              ]
	 */
	protected function bookExemplarsToBuyGet($bookData) {

		$toBuy = array();
		foreach ($bookData as $bookId => $data) {
			$res = ( - $data['exemplarsInStock'] + $data['exemplarsNeeded'] );
			$toBuy[$bookId]['exemplarsToBuy'] = ($res > 0) ? $res : 0 ;
		}
		return $toBuy;
	}

	/**
	 * Fetches the value from the db defining the begin of specialCourses
	 * @return int    The value
	 */
	protected function specialCourseTriggerGet() {

		$trigger = $this->_em
			->getRepository('DM:SystemGlobalSettings')
			->findOneByName('special_course_trigger');

		if(empty($trigger)) {
			$this->_logger->log(
				'no "special_course_trigger" defined in SystemGlobalSettings',
				'Notice', Null);
			throw new MySQLException('Could not fetch special_course_trigger');
		}
		else {
			return (int)$trigger->getValue();
		}
	}

	/**
	 * Splits the given gradelevels in lower and higher-same as $trigger
	 * @param  int    $trigger     The trigger with which to split the arrays
	 * @param  int    $gradelevels The gradelevels as an array
	 * @return array               An array containing the lowerGrades-Array as
	 *                             well as the upperGrades-Array
	 */
	protected function gradelevelsSplitByTrigger($trigger, $gradelevels) {

		$lowerGrades = array();
		$upperGrades = array();
		if(!$gradelevels) {
			return array(0, 0);
		}
		foreach($gradelevels as $gl) {
			if($gl < $trigger) {
				$lowerGrades[] = $gl;
			}
			else {
				$upperGrades[] = $gl;
			}
		}

		return array($lowerGrades, $upperGrades);
	}

	/*-----  End of Additional Bookdata to fetch  ------*/

	/**
	 * Combines two multi-dimensional arrays
	 * The first array defines what keys in the first dimension will be used
	 * Combines something like
	 *     [ '1' => ['A' => '5', 'B' => '6'],
	 *       '2' => ['A' => '9', 'B' => '3'] ]
	 *     and
	 *     [ '1' => ['F' => '8']
	 *       '2' => ['F' => '4']
	 *       '3' => ['F' => '6'] ]
	 *     to
	 *     [ '1' => ['A' => '5', 'B' => '6', 'F' => '8'],
	 *       '2' => ['A' => '9', 'B' => '3', 'F' => '4'] ]
	 * @param  array  $ar1 The first array
	 * @param  array  $ar2 The second array
	 * @return array       The combined array
	 */
	protected function bookArrayMerge($ar1, $ar2) {

		foreach($ar1 as $bookId1 => $book1) {
			if(!empty($ar2[$bookId1])) {
				foreach($ar2[$bookId1] as $name => $val) {
					$ar1[$bookId1][$name] = $val;
				}
			}
		}

		return $ar1;
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	/*==========  Cached Stuff  ==========*/

	/**
	 * Stores the value of GlobalSetting's religion-row
	 * @var array
	 */
	protected $_allReligions;

	/**
	 * Stores the value of GlobalSetting's foreign_language-row
	 * @var array
	 */
	protected $_allForeignLanguages;

	/**
	 * Stores the value of GlobalSetting's special_course-row
	 * @var array
	 */
	protected $_allSpecialCourses;
}

?>