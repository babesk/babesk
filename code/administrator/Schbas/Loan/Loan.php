<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_ADMIN . '/Schbas/Schbas.php';
require_once PATH_INCLUDE . '/Schbas/Loan.php';
require_once PATH_INCLUDE . '/Schbas/Book.php';

use Doctrine\Common\Collections\ArrayCollection;

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
		$accounting = $this->_em->getRepository('DM:SchbasAccounting')
			->findOneBy(['user' => $user, 'schoolyear' => $prepSchoolyear]);
		if($accounting !== Null) {
			$userPaid = $this->userPaidForLoanCheck($accounting);
			$userSelfpayer = $this->selfpayerCheck($accounting);
			$loanChoice = $accounting->getLoanChoice()->getAbbreviation();
			$formSubmitted = true;
		}
		else {
			$userPaid = false;
			$userSelfpayer = false;
			$formSubmitted = false;
		}
		$exemplarsLent = $this->exemplarsStillLendByUserGet($user);
		$booksSelfpaid = $user->getSelfpayingBooks();
		// The books that are already lend to the user will be highlighted
		$booksToLoan = $loanHelper->loanBooksOfUserGet(
			$user, ['includeAlreadyLend' => true]
		);
		$booksToLoanWithLent = [];
		foreach($booksToLoan as $book) {
			$alreadyLent = false;
			foreach($exemplarsLent as $exemplar) {
				if(
					$book === $exemplar->getBook() &&
					$book->getId() == $exemplar->getBook()->getId()
				) {
					$alreadyLent = true;
					break;
				}
			}
			$booksToLoanWithLent[] = [
				'book' => $book,
				'alreadyLent' => $alreadyLent
			];
		}
        $accountingQb = $this->_em->createQueryBuilder()
            ->select('u, a, lc')
            ->from('DM:SystemUsers', 'u')
            ->leftJoin(
                'u.schbasAccounting', 'a',
                'WITH', 'a.schoolyear = :prepSchoolyear'
            )
            ->leftJoin('a.loanChoice', 'lc')
            ->where('u = :user');
        $accountingQb->setParameter('prepSchoolyear', $prepSchoolyear);
        $accountingQb->setParameter('user', $user);
        $userdata = $accountingQb->getQuery()->getOneOrNullResult();
        $grade = $this->_em->getRepository('DM:SystemUsers')->getActiveGradeByUser($user);
        $this->_smarty->assign('grade', $grade);
		$this->_smarty->assign('user', $user);
        $this->_smarty->assign('userdata', $userdata);
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

		$card = $this->_em->getRepository('DM:BabeskCards')
			->findOneByCardnumber($cardnumber);
		if($card) {
			if(!$card->getLost()) {
				$user = $card->getUser();
				if($user->getLocked()) {
					$this->_interface->dieError('Der Benutzer ist gesperrt!');
				}
				else {
					return $user;
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

		$loanChoice = $acc->getLoanChoice();
		return (
			(
				$loanChoice->getAbbreviation() == 'ln' ||
				$loanChoice->getAbbreviation() == 'lr'
			) &&
			$acc->getPayedAmount() >= $acc->getAmountToPay()
		);
	}

	private function selfpayerCheck($accounting) {

		$abbr = $accounting->getLoanChoice()->getAbbreviation();
		return $abbr == 'ls';
	}

	private function exemplarsStillLendByUserGet($user) {

		$exemplars = $this->_em->createQuery(
			'SELECT i FROM DM:SchbasInventory i
				INNER JOIN i.book b
				INNER JOIN i.usersLent u
				WHERE u.id = :userId
				ORDER BY b.subject
		')->setParameter('userId', $user->getId())
			->getResult();
		return $exemplars;
	}

	private function booksSelfpaidByUserGet($user) {

		$books = $user->getSelfpayingBooks();
		return $books;
	}

	private function bookLoanToUserByBarcode($barcode, $userId) {

		$exemplar = $this->exemplarByBarcodeGet($barcode);
		if($exemplar) {
			//Check if book is lent to someone
			if($exemplar->getUsersLent()->count() == 0) {
				if($this->bookLoanToUserToDb($exemplar, $userId)) {
					die(json_encode(array(
						'bookId' => $exemplar->getBook()->getId(),
						'exemplarId' => $exemplar->getId(),
						'title' => $exemplar->getBook()->getTitle()
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
				$user = $exemplar->getUsersLent()->first();
				die(json_encode(array(
					'message' => 'Dieses Exemplar ist im System bereits an ' .
					$user->getForename() . ' ' . $user->getName() .
					' verliehen!'
				)));
			}
		}
		else {
			$this->_logger->log('Book not found by barcode',
				'Notice', Null, json_encode(array('barcode' => $barcode)));
			http_response_code(500);
			die(json_encode(array(
				'message' => 'Das Exemplar konnte nicht anhand des Barcodes ' .
					'gefunden werden!'
			)));
		}
	}

	/**
	 * Checks if the book-exemplar is already lent to a user
	 * @param  string $barcode The Barcode of the exemplar
	 * @return bool            true if it is lent
	 */
	private function exemplarByBarcodeGet($barcodeStr) {

		$bookHelper = new \Babesk\Schbas\Book($this->_dataContainer);
		$barcode = $bookHelper->barcodeParseToArray($barcodeStr);
		//$barcodeStr = $this->barcodeNormalize($barcodeStr);
		//$barcode = $this->barcodeParseToArray($barcodeStr);
		//Delimiter not used in Query
		unset($barcode['delimiter']);
		$query = $this->_em->createQuery(
			'SELECT i, b FROM DM:SchbasInventory i
				INNER JOIN i.book b
					WITH b.class = :class AND b.bundle = :bundle
				INNER JOIN b.subject s
					WITH s.abbreviation = :subject
				WHERE i.yearOfPurchase = :purchaseYear
					AND i.exemplar = :exemplar
		')->setParameters($barcode);
		try {
			$lent = $query->getSingleResult();
		}
		catch(\Doctrine\ORM\NoResultException $e) {
			return false;
		}
		return $lent;
	}

	private function bookLoanToUserToDb($exemplar, $userId) {

		try {
			$lending = new \Babesk\ORM\SchbasLending();
			$user = $this->_em->find(
				'DM:SystemUsers', $userId
			);
			$lending->setUser($user);
			$lending->setInventory($exemplar);
			$lending->setLendDate(new \DateTime());
			$this->_em->persist($lending);
			$this->_em->flush();

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
