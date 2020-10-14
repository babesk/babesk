<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_ADMIN . '/Schbas/Schbas.php';
require_once PATH_INCLUDE . '/Schbas/Loan.php';
require_once PATH_INCLUDE . '/Schbas/Book.php';
require_once PATH_INCLUDE . '/Schbas/Barcode.php';


class Loan extends Schbas {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);

		if(isset($_POST['barcode']) && isset($_POST['userId'])) {
			$this->bookLoanToUserByBarcode(
				$_POST['barcode'], $_POST['userId']
			);
		}
		else if(isset($_POST['card_ID'])) {
			$this->loanDisplay($_POST['card_ID']);
		}
		else {
			$this->displayTpl('form.tpl');
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function entryPoint($dataContainer) {

		parent::entryPoint($dataContainer);
		parent::moduleTemplatePathSet();
	}

	private function loanDisplay($cardnumber) {

		$loanHelper = new \Babesk\Schbas\Loan($this->_dataContainer);
		$prepSchoolyear = $loanHelper->schbasPreparationSchoolyearGet();
		$user = $this->userByCardnumberGet($cardnumber);
		$loanChoice = false;
		$stmt = $this->_pdo->prepare("SELECT * FROM SchbasAccounting a LEFT JOIN SchbasLoanChoices lc ON (a.loanChoiceId = lc.ID) WHERE userId = ? AND schoolyearId = ?");
		$stmt->execute(array($user['ID'], $prepSchoolyear));
		$accounting = $stmt->fetch();
		if($accounting) {
			$userPaid = $this->userPaidForLoanCheck($accounting);
			$userSelfpayer = ($accounting['abbreviation'] == 'ls');
			$loanChoice = $accounting['abbreviation'];
			$formSubmitted = true;
		}
		else {
			$userPaid = false;
			$userSelfpayer = false;
			$formSubmitted = false;
		}
		$exemplarsLent = $this->exemplarsStillLendByUserGet($user);

		$stmt = $this->_pdo->prepare("SELECT * FROM SchbasSelfpayer p JOIN SchbasBooks b ON (p.BID = b.id)
												WHERE p.UID = ?");
		$stmt->execute(array($user['ID']));
		$booksSelfpaid = $stmt->fetchAll();
		// The books that are already lend to the user will be highlighted
		$booksToLoan = $loanHelper->loanBooksOfUserGet(
			$user, ['includeAlreadyLend' => true]
		);
		$booksToLoanWithLent = [];
		foreach($booksToLoan as $book) {
			$alreadyLent = false;
			foreach($exemplarsLent as $exemplar) {
				if($book['bookId'] === $exemplar['book_id']) {
					$alreadyLent = true;
					break;
				}
			}
			$booksToLoanWithLent[] = [
				'book' => $book,
				'alreadyLent' => $alreadyLent
			];
		}

        $this->_smarty->assign('userdata', $user);
        $this->_smarty->assign('accounting', $accounting);
		$this->_smarty->assign('formSubmitted', $formSubmitted);
		$this->_smarty->assign('loanChoice', $loanChoice);
		$this->_smarty->assign('userPaid', $userPaid);
		$this->_smarty->assign('userSelfpayer', $userSelfpayer);
		$this->_smarty->assign('exemplarsLent', $exemplarsLent);
		$this->_smarty->assign('booksSelfpaid', $booksSelfpaid);
		$this->_smarty->assign('booksToLoan', $booksToLoanWithLent);
		$this->displayTpl('user-loan-list.tpl');
	}

	private function userByCardnumberGet($cardnumber) {

		$stmt = $this->_pdo->prepare("SELECT u.*,c.lost FROM BabeskCards c JOIN SystemUsers u ON (c.UID = u.ID) WHERE cardnumber = ?");
		$stmt->execute(array($cardnumber));
		$card = $stmt->fetch();
		if($card) {
			if(!$card['lost']) {
				if($card['locked']) {
					$this->_interface->dieError('Der Benutzer ist gesperrt!');
				}
				else {
					return $card;
				}
			}
			else {
				$this->_interface->dieError(
					'Diese Karte ist verloren gegangen!'
				);
			}
		}
		else {
			$this->_interface->dieError('Die Karte wurde nicht gefunden!');
		}
	}

	private function userPaidForLoanCheck($acc) {
		$stmt = $this->_pdo->prepare("SELECT * FROM SchbasLoanChoices WHERE ID = ?");
		$stmt->execute(array($acc['loanChoiceId']));
		$loanChoice = $stmt->fetch();
		return (
			(
				$loanChoice['abbreviation'] == 'ln' ||
				$loanChoice['abbreviation'] == 'lr'
			) &&
			$acc['payedAmount'] >= $acc['amountToPay']
		);
	}

	private function exemplarsStillLendByUserGet($user) {
		$stmt = $this->_pdo->prepare("SELECT * FROM SchbasInventory i 
												JOIN SchbasBooks b ON (i.book_id = b.id)
												JOIN SchbasLending l ON (l.inventory_id = i.id)
												LEFT JOIN SystemSchoolSubjects sub ON (b.subjectId = sub.ID)
												WHERE l.user_id = ?
												ORDER BY b.subjectId");
		$stmt->execute(array($user['ID']));
		$exemplars = $stmt->fetchAll();

		return $exemplars;
	}

	private function bookLoanToUserByBarcode($barcode, $userId) {

        $barcode = \Babesk\Schbas\Barcode::createByBarcodeString($barcode);

        $inventory = $barcode->getMatchingBookExemplar($this->_pdo);

		//Check if book is lent to someone
        $lent = $this->exemplarByBarcodeGet($barcode, $inventory);
		if(!$lent) {
			if($this->bookLoanToUserToDb($inventory, $userId)) {
				die(json_encode(array(
					'bookId' => $inventory['book_id'],
					'exemplarId' => $inventory['exemplar'],
					'title' => $inventory['title']
				)));
			}
			else {
				http_response_code(500);
				die(json_encode(array(
					'message' => 'Ein Fehler ist beim Eintragen der ' .
						'Ausleihe aufgetreten.'
				)));
			}
		}
		else {
			http_response_code(500);
			//Exemplar should not be lent to two users at the same time
			die(json_encode(array(
				'message' => 'Dieses Exemplar ist im System bereits verliehen!'
			)));
		}

	}

	/**
	 * Checks if the book-exemplar is already lent to a user
	 * @param  string $barcode The Barcode of the exemplar
	 * @return bool            true if it is lent
	 */
	private function exemplarByBarcodeGet($barcodeStr, $inventory) {

        if(!$inventory){
            $this->_logger->log('Book not found by barcode',
                'Notice', Null, json_encode(array('barcode' => $barcodeStr)));
            http_response_code(500);
            die(json_encode(array(
                'message' => 'Das Exemplar konnte nicht anhand des Barcodes ' .
                    'gefunden werden!'
            )));
		}

        $stmt = $this->_pdo->prepare("SELECT * FROM SchbasLending WHERE inventory_id = ?");
        $stmt->execute(array($inventory['id']));
        $lendings = $stmt->fetchAll();

		return count($lendings) > 0;
	}

	private function bookLoanToUserToDb($exemplar, $userId) {

		try {
			$timestamp = new DateTime();
			$stmt = $this->_pdo->prepare("INSERT INTO SchbasLending(user_id, inventory_id, lend_date) VALUES (?, ?, ?)");
			$stmt->execute(array($userId, $exemplar['id'], $timestamp->format("Y-m-d")));

		} catch (Exception $e) {
			$this->_logger->log('Error loaning a book-exemplar to a user',
				'error', Null, json_encode(array(
					'msg' => $e->getMessage())));
			return false;
		}
		return true;
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

}

?>
