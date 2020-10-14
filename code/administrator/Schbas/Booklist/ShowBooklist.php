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
			$stmt = $this->_pdo->prepare("SELECT * FROM SystemSchoolyears WHERE ID = ?");
			$stmt->execute(array($schoolyear));
			$label = $stmt->fetch()['label'];
			$this->_smarty->assign('preparationSchoolyear', $label);
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

        $stmt = $this->_pdo->prepare("SELECT * FROM SchbasBooks b
                                                LEFT JOIN SystemSchoolSubjects sub ON (sub.ID = b.subjectId)");
        $stmt_search = $this->_pdo->prepare("SELECT * FROM SchbasBooks b
                                                LEFT JOIN SystemSchoolSubjects sub ON (sub.ID = b.subjectId)
                                                WHERE sub.abbreviation LIKE ?");
        if($_POST['filterFor'] != "") {
            $stmt_search->execute(array($_POST['filterFor']));
            $books = $stmt_search->fetchAll();
        }else {
            $stmt->execute();
            $books = $stmt->fetchAll();
        }

		$books = $this->bookArrayPopulate($books);
		$pagecount = $this->pagecountGet($books);

        $books = array_slice($books, $_POST['pagenumber'] * $_POST['booksPerPage'], $_POST['booksPerPage']);
		die(json_encode(array(
			'pagecount' => $pagecount, 'books' => $books
		)));
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
				'id' => $book['id'],
				'title' => $book['title'],
				'author' => $book['author'],
				'gradelevel' => $book['class'],
				'bundle' => $book['bundle'],
				'price' => $book['price'],
				'isbn' => $book['isbn'],
				'publisher' => $book['publisher']
			);
			$stmt = $this->_pdo->prepare("SELECT * FROM SystemSchoolSubjects WHERE ID = ?");
			$stmt->execute(array($book['subjectId']));
			$subject = $stmt->fetch();
			$bookAr['subject'] = ($subject) ?
				$subject['name'] : '';
			$books[$book['id']] = $bookAr;
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
		$query = $this->_pdo->prepare("SELECT max(exemplar) FROM SchbasInventory
                                                 WHERE book_id = ?");

		foreach($paginator as $book) {
			$query->execute(array($book['id']));
			$res = $query->fetch();
			if($res) {
				$invNums[$book['id']]['highestExemplarNumber'] = (int) $res[0];
			}
			else {
				$invNums[$book['id']]['highestExemplarNumber'] = 0;
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
		$query = $this->_pdo->prepare("SELECT COUNT(*) FROM schbasbooks b
                                                 JOIN SchbasInventory i ON (i.book_id = b.id)
                                                 JOIN SchbasLending l ON (l.inventory_id=i.id)
                                                 WHERE b.id = ?");

		foreach($paginator as $book) {
		    $query->execute(array($book['id']));
			$res = $query->fetch();
			$booksLent[$book['id']]['exemplarsLent'] = (int)$res[0];
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
		$query = $this->_pdo->prepare("SELECT COUNT(*) FROM SchbasInventory WHERE book_id = ?");
		foreach($paginator as $book) {
		    $query->execute(array($book['id']));
			$res = $query->fetch();
			$booksInventory[$book['id']]['allExemplars'] = $res[0];
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
		$booksNeeded = array();
		foreach($paginator as $book) {
			$count = $loan->amountOfInventoryAssignedToUsersGet($book);
			$booksNeeded[$book['id']]['exemplarsNeeded'] = $count;
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
		$query = $this->_pdo->prepare("SELECT COUNT(*) FROM SchbasSelfpayer WHERE BID = ?");
		foreach($paginator as $book) {
		    $query->execute(array($book['id']));
			$count = $query->fetch()[0];
			$booksSelfpayed[$book['id']]['exemplarsSelfpayed'] = $count;
		}
		return $booksSelfpayed;
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



}

?>