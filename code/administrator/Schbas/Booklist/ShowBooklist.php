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
            $booksData = $this->booksUsersInSystemGet($paginator);
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
    protected function booksUsersInSystemGet($paginator) {
        require_once PATH_INCLUDE . '/Schbas/Loan.php';
        $loan = new \Babesk\Schbas\Loan($this->_dataContainer);
        $invNums = array();
        foreach($paginator as $book) {
            $grades = $loan->isbnIdent2Gradelevel($book['class']);

            $query = TableMng::query("SELECT gradelevel, count(*) cnt, sum(CASE WHEN s.special_course LIKE '%".$book['abbreviation']."%' THEN 1 ELSE 0 END) sc
		                                    FROM SystemUsers s
		                                    JOIN SystemAttendances a ON s.id=a.userId
		                                    JOIN SystemSchoolyears y ON a.schoolyearId = y.ID
		                                    JOIN SystemGrades g ON a.gradeId = g.id
		                                    WHERE y.active = 1
		                                    GROUP BY gradelevel");
            $sum = 0;
            $tooltip = "'";
            foreach ($query as $grade) {
                $coreSub = TableMng::query("SELECT abbreviation
                                            FROM SchbasCoreSubjects c
                                            JOIN SystemSchoolSubjects s ON c.subject_id = s.ID
                                            WHERE gradelevel = ".($grade['gradelevel']+1));
                if (in_array($grade['gradelevel']+1, $grades)) { //benÃ¶tigt der SchÃ¼ler das Buch nÃ¤chstes Jahr
                    if (in_array($book['abbreviation'], array_column($coreSub, 'abbreviation'))){ //ist es ein Pflichtfach
                        $sum += $grade['cnt'];
                        $tooltip = "" . $tooltip . "Jahrgang " . $grade['gradelevel'] . ": " . $grade['cnt'] . "<br>";
                    }else{
                        $sum += $grade['sc'];
                        $tooltip = "" . $tooltip . "Jahrgang " . $grade['gradelevel'] . ": " . $grade['sc'] . "<br>";
                    }
                }
            }
            $tooltip .= "'";
            $invNums[$book['id']]['usersInSystem'] = $sum;
            $invNums[$book['id']]['usersInSystemByGrade'] = $tooltip;
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

        require_once PATH_INCLUDE . '/Schbas/Loan.php';
        $loan = new \Babesk\Schbas\Loan($this->_dataContainer);
        $booksLent = array();
        foreach($paginator as $book) {
            $grades = $loan->isbnIdent2Gradelevel($book['class']);
            $query = TableMng::query("SELECT gradelevel, COUNT(1) cnt
		                            FROM SchbasLending l 
		                            JOIN SchbasInventory i ON l.inventory_id = i.id
		                            JOIN SystemAttendances a ON l.user_id = a.userId
		                            JOIN SystemGrades g ON a.gradeId = g.ID
		                            JOIN SystemSchoolyears s ON a.schoolyearId = s.ID 
		                            WHERE s.active = 1 AND i.book_id = ".$book['id']
                ." GROUP BY gradelevel");
            $sum = 0;
            $notReturning = 0;
            $tooltip = "'";
            foreach ($query as $grade){
                if(in_array($grade['gradelevel']+1, $grades)){
                    $notReturning += $grade['cnt'];
                }
                $sum += $grade['cnt'];
                $tooltip = "".$tooltip."Jahrgang ".$grade['gradelevel'].": ".$grade['cnt']."<br>";
            }
            $tooltip .= "'";
            $booksLent[$book['id']]['exemplarsLent'] = $sum;
            $booksLent[$book['id']]['exemplarsLentNotReturning'] = $notReturning;
            $booksLent[$book['id']]['exemplarsLentByGrade'] = $tooltip;
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
            $query = TableMng::query("SELECT COUNT(1) cnt
		                                    FROM SchbasUsersShouldLendBooks s
		                                    JOIN SystemSchoolyears y ON s.schoolyearID=y.ID
		                                    WHERE y.active = 1 AND s.bookId = ".$book['id']);
            $booksNeeded[$book['id']]['exemplarsNeeded'] = $query[0]['cnt'];
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
            $query = TableMng::query('SELECT gradelevel, COUNT(1) cnt 
                                    FROM SchbasSelfpayer p
                                    JOIN SystemAttendances a ON p.UID = a.userId
		                            JOIN SystemGrades g ON a.gradeId = g.ID
		                            JOIN SystemSchoolyears s ON a.schoolyearId = s.ID
				                    WHERE s.active = 1 AND BID = '.$book['id']);
            $sum = 0;
            $tooltip = "'";
            foreach ($query as $grade){
                $sum += $grade['cnt'];
                $tooltip = "".$tooltip."Jahrgang ".$grade['gradelevel'].": ".$grade['cnt']."<br>";
            }
            $tooltip .= "'";

            $booksSelfpayed[$book['id']]['exemplarsSelfpayed'] = $sum;
            $booksSelfpayed[$book['id']]['exemplarsSelfpayedByGrade'] = $tooltip;
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
            /*$res = ($data['exemplarsNeeded']
                - $data['exemplarsSelfpayed']
                - $data['exemplarsInStock']
                - $data['exemplarsLentNotReturning']);*/
            $res = $data['usersInSystem']-$data['allExemplars'];
            $toBuy[$bookId]['exemplarsToBuy'] = ($res > 0) ? "<span style='color:red'>".$res."</span>" : 0 ;
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