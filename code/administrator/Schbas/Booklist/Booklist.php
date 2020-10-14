<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_ADMIN . '/Schbas/Schbas.php';
require_once PATH_INCLUDE . '/Schbas/SchbasPdf.php';

class Booklist extends Schbas {


	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		defined('_AEXEC') or die('Access denied');

		require_once 'AdminBooklistInterface.php';

		parent::entryPoint($dataContainer);
		parent::moduleTemplatePathSet();

		$BookInterface = new AdminBooklistInterface($this->relPath);

		if (isset($_GET['action'])) {
			$action = $_GET['action'];
			switch ($action) {
				case 2: //edit a book
					$this->editBook();
					break;
				case 3: //delete an entry
					$this->deleteBook();
					break;
				case 4: //add an entry
					$this->addBook();
					break;
				case 'showBooksFNY':
					$this->showBooksForNextYear();
					break;
				case 'showBooksBT':
					$this->showBooksByTopic();
					break;
				break;
			}
		}
		else {
			$BookInterface->ShowSelectionFunctionality();
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	private function editBook() {

		if(isset($_POST['title'])) {
			$this->editBookUpload();
		}
		else if(isset($_GET['ID'])) {
		    $stmt = $this->_pdo->prepare("SELECT * FROM SchbasBooks b JOIN SystemSchoolSubjects s ON (b.subjectId=s.ID) WHERE b.id = ?");
		    $stmt->execute(array($_GET['ID']));
		    $book = $stmt->fetch();
		    if($book) {
                $this->_smarty->assign('book', $book);
                $this->displayTpl('change_book.tpl');
            }else{
                $this->displayTpl('index.tpl');
            }
		}
	}

	private function editBookUpload() {

		$_POST['price'] = str_replace(',', '.', $_POST['price']);

		$stmt = $this->_pdo->prepare("SELECT * FROM SystemSchoolSubjects 
                                                WHERE name = :subname OR abbreviation = :subname");
		$stmt->execute(array(':subname' => $_POST['subject']));
		$subject = $stmt->fetch();
		if(!$subject){
            $this->_interface->dieError(
                "Konnte das Fach $_POST[subject] nicht finden."
            );
        }
		$stmt = $this->_pdo->prepare("UPDATE SchbasBooks SET 
                                                title = :title, 
                                                author = :author,
                                                publisher = :publisher,
                                                isbn = :isbn,
                                                price = :price,
                                                subjectId = :subjectID,
                                                class = :class,
                                                bundle = :bundle
                                                WHERE id = :id");
		$stmt->execute(array(
		    ':title' => $_POST['title'],
            ':author' => $_POST['author'],
            ':publisher' => $_POST['publisher'],
            ':isbn' => $_POST['isbn'],
            ':price' => $_POST['price'],
            ':subjectID' => $subject['ID'],
            ':class' => $_POST['class'],
            ':bundle' => $_POST['bundle'],
            ':id' => $_GET['ID']
        ));

        $this->_interface->backlink('administrator|Schbas|Booklist|ShowBooklist');
		$this->_interface->dieSuccess(
			"Das Buch {$_POST['title']} wurde erfolgreich verändert."
		);
	}

	private function addBook() {

		if(!isset($_POST['title'])) {
			$this->displayTpl('add_entry.tpl');
		}
		else {
			$this->addBookUpload();
		}
	}

	private function addBookUpload() {

		$_POST['price'] = str_replace(',', '.', $_POST['price']);

        $stmt = $this->_pdo->prepare("SELECT * FROM SystemSchoolSubjects 
                                                WHERE name = :subname OR abbreviation = :subname");
        $stmt->execute(array(':subname' => $_POST['subject']));
        $subject = $stmt->fetch();
        if(!$subject){
            $this->_interface->dieError(
                "Konnte das Fach $_POST[subject] nicht finden."
            );
        }

        $stmt = $this->_pdo->prepare("INSERT INTO SchbasBooks(title, author, publisher, isbn, price, subjectId, class, bundle) VALUES 
                                                (:title, :author, :publisher, :isbn, :price, :subjectID, :class, :bundle)");
        $stmt->execute(array(
            ':title' => $_POST['title'],
            ':author' => $_POST['author'],
            ':publisher' => $_POST['publisher'],
            ':isbn' => $_POST['isbn'],
            ':price' => $_POST['price'],
            ':subjectID' => $subject['ID'],
            ':class' => $_POST['class'],
            ':bundle' => $_POST['bundle'],
        ));
		$this->_interface->backlink('administrator|Schbas|Booklist');
		$this->_interface->dieSuccess(
			"Das Buch $_POST[title] wurde erfolgreich hinzugefügt."
		);
	}

	private function deleteBook() {

		if(isset($_POST['delete'])) {
			$this->deleteBookFromDatabase($_GET['ID']);
		}
		else {
			$this->deleteBookConfirmation();
		}
	}

	private function deleteBookConfirmation() {

		if(isset($_GET['ID'])) {
		    $stmt = $this->_pdo->prepare("SELECT * FROM SchbasBooks WHERE id = ?");
		    $stmt->execute(array($_GET['ID']));
		    $book = $stmt->fetch();
			if($book) {
			    $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM SchbasInventory WHERE book_id = ?");
                $stmt->execute(array($_GET['ID']));
                $count = $stmt->fetch()[0];
				$hasInventory = $count > 0;
				$this->_smarty->assign('hasInventory', $hasInventory);
				$this->_smarty->assign('book', $book);
				$this->displayTpl('deletion_confirm.tpl');
			}
			else {
				$this->_interface->dieError(
					'Das Buch konnte nicht gefunden werden.'
				);
			}
		}
		else {
			$this->_interface->dieError(
				'Das Buch konnte nicht gefunden werden.'
			);
		}
	}

	private function deleteBookFromDatabase($bookID) {

	    $lending = $this->_pdo->prepare("DELETE FROM SchbasLending WHERE inventory_id IN (SELECT id FROM SchbasInventory WHERE book_id = ?)");
	    $lending->execute(array($bookID));

	    $inventory = $this->_pdo->prepare("DELETE FROM SchbasInventory WHERE book_id = ?");
	    $inventory->execute(array($bookID));

	    $bookDel = $this->_pdo->prepare("DELETE FROM SchbasBooks WHERE id = ?");
	    $bookDel->execute(array($bookID));


        $this->_interface->backlink('administrator|Schbas|Booklist|ShowBooklist');
        $this->_interface->dieSuccess(
            "Das Buch wurde erfolgreich gelöscht."
        );

	}

    /**
     * Show list of books which students can keep for next schoolyear, ordered by schoolyear.
     */
    function showBooksForNextYear() {

        if (isset($_POST['grade'])) {
            require_once PATH_INCLUDE . '/Schbas/Loan.php';
            require_once PATH_ACCESS . '/BookManager.php';

            $gradelevel = $_POST['grade'];
            $loanHelper = new \Babesk\Schbas\Loan($this->_dataContainer);
            $booksThisYear = $loanHelper->booksInGradelevelToLoanGet(
                $gradelevel
            );
            $booksNextYear = $loanHelper->booksInGradelevelToLoanGet(
                $gradelevel + 1
            );
            // Only books that are both in this gradelevel and next gradelevel
            // will be displayed
            if(!empty($booksThisYear) && !empty($booksNextYear))
                $books = array_intersect($booksThisYear, $booksNextYear);
            else
                $books = array();
            $this->showPdf($books);
        }
        else {
            $this->BookInterface->ShowSelectionForBooksToKeep();
        }
    }

    /**
     * Show list of books by topics.
     */
    function showBooksByTopic() {

        require_once 'AdminBooklistInterface.php';
        if (isset($_POST['topic'])) {
            $stmt = $this->_pdo->prepare("SELECT * FROM SchbasBooks b JOIN SystemSchoolSubjects sub ON (b.subjectId = sub.ID) WHERE sub.abbreviation = ?");
            $stmt->execute(array($_POST['topic']));
            $books = $stmt->fetchAll();
            $this->showPdfFT($books);
        }
        else {
            $this->BookInterface->ShowSelectionForBooksByTopic();
        }
    }


    private function showPdf($booklist) {
        $title = "<h2 align='center'>Lehrb&uuml;cher, die f&uuml;r Jahrgang ".($_POST['grade']+1)." behalten werden k&ouml;nnen</h2>";
        $books = '<table border="0" bordercolor="#FFFFFF" style="background-color:#FFFFFF" width="100%" cellpadding="0" cellspacing="1">

			<tr style="font-weight:bold; text-align:center;"><th>Fach</th><th>Titel</th><th>Verlag</th><th>ISBN-Nr.</th><th>Preis</th></tr>';
        foreach ($booklist as $book) {
            // $bookPrices += $book['price'];
            $books .= '<tr><td>' . $book->getSubject()->getName() . '</td><td>' . $book->getTitle() . '</td><td>' . $book->getPublisher() . '</td><td>' . $book->getIsbn() . '</td><td align="right">' . $book->getPrice() . ' &euro;</td></tr>';
        }
        //$books .= '<tr><td></td><td></td><td></td><td style="font-weight:bold; text-align:center;">Summe:</td><td align="right">'.$bookPrices.' &euro;</td></tr>';
        $books .= '</table>';
        $books = str_replace('ä', '&auml;', $books);
        $books = str_replace('é', '&eacute;', $books);

        $schbasPdf = new \Babesk\Schbas\SchbasPdf('pdf');
        $schbasPdf->create($title . $books);
        $schbasPdf->output();
    }


    private function showPdfFT($booklist) {

        $title = "<h2 align='center'>Lehrb&uuml;cher f&uuml;r Fach " .
            ($_POST['topic']) . '</h2>';
        $books = '<table border="0" bordercolor="#FFFFFF" style="background-color:#FFFFFF" width="100%" cellpadding="0" cellspacing="1">

		<tr style="font-weight:bold; text-align:center;"><th>Klasse</th><th>Titel</th><th>Verlag</th><th>ISBN-Nr.</th><th>Preis</th></tr>';
        $classAssign = array(
            '5'=>'05,56',			// hier mit assoziativem array
            // arbeiten, in der wertzuw.
            '6'=>'56,06,69,67',		// alle kombinationen auflisten
            // sql-abfrage:
            '7'=>'78,07,69,79,67',	// SELECT * FROM `schbas_books` WHERE `class` IN (werte-array pro klasse)
            '8'=>'78,08,69,79,89',
            '9'=>'90,91,09,92,69,79,89',
            '10'=>'90,91,10,92',
            '11'=>'12,92,13',
            '12'=>'12,92,13'
        );
        foreach ($booklist as $book) {
            $classKey="";
            foreach ($classAssign as $key => $value) {
                if (strpos($value,$book['class']) !== false) {
                    $classKey.=$key."/";
                }
            }
            $classKey = rtrim($classKey, "/");
            $books.= '<tr><td>'.$classKey.'</td><td>'.$book['title'].'</td><td>'.$book['publisher'].'</td><td>'.$book['isbn'].'</td><td align="right">'.$book['price'].' &euro;</td></tr>';
        }
        $books .= '</table>';
        $books = str_replace('ä', '&auml;', $books);
        $books = str_replace('é', '&eacute;', $books);
        try {
            $schbasPdf = new \Babesk\Schbas\SchbasPdf(
                "Buchliste_Fach_".$_POST['topic']
            );
            $schbasPdf->create($title . $books);
            $schbasPdf->output();
        }
        catch(Exception $e) {
            $this->_interface->DieError('Konnte das PDF nicht erstellen!');
        }
    }

    /**
     * Returns the book ID by a given ISBN
     */
    function getBookIdByISBN($isbn_search) {
        require_once PATH_ACCESS . '/BookManager.php';
        $bookManager = new BookManager();
        try {
            $book_id = $bookManager->getBookIDByISBN($isbn_search);
        } catch (Exception $e) {
            $this->BookInterface->dieError($this->messages['error']['notFound'] . $e->getMessage());
        }
        return $book_id['id'];
    }


    /////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

}

?>