<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_ADMIN . '/Schbas/Schbas.php';
require_once PATH_INCLUDE . '/Schbas/Barcode.php';
require_once PATH_INCLUDE . '/Schbas/Loan.php';

class Retour extends Schbas {


	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct($name, $display_name, $path) {
		parent::__construct($name, $display_name, $path);
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);

		require_once PATH_ACCESS . '/CardManager.php';
		require_once PATH_ACCESS . '/UserManager.php';
		require_once PATH_ACCESS . '/LoanManager.php';
		require_once PATH_ACCESS . '/InventoryManager.php';
		require_once PATH_ACCESS . '/BookManager.php';

		$this->cardManager = new CardManager();
		$this->userManager = new UserManager();
		$this->loanManager = new LoanManager();
		$this->inventoryManager = new InventoryManager();
		$this->bookManager = new BookManager();
		$this->msg = array('err_empty_books' => 'keine B&uuml;cher ausgeliehen!',
							'err_get_user_by_card' => 'Kein Benutzer gefunden!',
							'err_card_id' => 'Die Karten-ID ist fehlerhaft!',
							'err_usr_locked' =>'Der Benutzer ist gesperrt!');

		if ('GET' == $_SERVER['REQUEST_METHOD'] && isset($_GET['inventarnr'])) {
			try {
				$res = $this->RetourBook(urldecode(trim($_GET['inventarnr'])), $_GET['uid']);
			} catch (Exception $e) {
				$this->_logger->logO('Could not retour book', ['sev' => 'error', 'moreJson' => $e->getMessage()]);
				$res = false;
			}
			if(!$res) {
				die('Konnte das Buch nicht zurückgeben. Möglicherweise falsch eingescannt?');
			}
			$this->RetourTableDataAjax($_GET['card_ID']);
		}
		else if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['card_ID'])) {
			$this->RetourTableData($_POST['card_ID']);
		}
		else{
			$this->displayTpl('form.tpl');
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function entryPoint($dataContainer) {

		parent::entryPoint($dataContainer);
		$this->moduleTemplatePathSet();
	}

	/**
	 * Ausleihtabelle anzeigen
	 */
	function RetourTableData($card_id) {

		$loanHelper = new \Babesk\Schbas\Loan($this->_dataContainer);
		$prepSy = $loanHelper->schbasPreparationSchoolyearGet();
		$uid = $this->GetUser($card_id);
		$user = $this->_em->find('DM:SystemUsers', $uid);
		$query = $this->_em->createQuery(
			'SELECT b, e, l FROM DM:SchbasBook b
			INNER JOIN b.exemplars e
			INNER JOIN e.lending l WITH l.user = :user
		');
		$query->setParameter('user', $user);
		$loanbooks = $query->getResult();
		if(!count($loanbooks)) {
			$this->_interface->dieMsg('Der Benutzer hat keine Bücher ausgeliehen.');
		}
		$grade = $this->_em->getRepository('DM:SystemUsers')->getActiveGradeByUser($user);
		$userData = "{$user->getForename()} {$user->getName()} ";
		if($grade) {
			$userData .="({$grade->getGradelevel()}{$grade->getLabel()})";
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
		$accountingQb->setParameter('prepSchoolyear', $prepSy);
		$accountingQb->setParameter('user', $user);
		$user = $accountingQb->getQuery()->getOneOrNullResult();

		$this->_smarty->assign('cardid', $card_id);
		$this->_smarty->assign('uid', $uid);
		$this->_smarty->assign('data', $loanbooks);
		$this->_smarty->assign('fullname',$userData);
		$this->_smarty->assign('user', $user);
		$this->_smarty->assign('adress', ($_SERVER['HTTP_HOST']).$_SERVER['REQUEST_URI']);
		$this->displayTpl('retourbooks.tpl');
	}

	/**
	 * Ausleihtabelle per Ajax anzeigen
	 */
	function RetourTableDataAjax($card_id) {

		try {
			$uid = $this->GetUser($card_id);
			$user = $this->_em->find('DM:SystemUsers', $uid);
			$query = $this->_em->createQuery(
				'SELECT b, e, l FROM DM:SchbasBook b
				INNER JOIN b.exemplars e
				INNER JOIN e.lending l WITH l.user = :user
			');
			$query->setParameter('user', $user);
			$loanbooks = $query->getResult();
			if(!count($loanbooks)) {
				$this->displayTpl('retourbooksAjaxEmpty.tpl');
			}
			$grade = $this->_em->getRepository('DM:SystemUsers')
				->getActiveGradeByUser($user);

		} catch (Exception $e) {
			$this->_logger->logO('Error in ajax of Schbas Retour',
				['sev' => 'error', 'moreJson' => ['msg' => $e->getMessage()]]);
			$this->_interface->showMsg('Ein Fehler ist aufgetreten.');
			die();
		}

		$this->_smarty->assign('cardid', $card_id);
		$this->_smarty->assign('uid', $uid);
		$this->_smarty->assign(
			'adress', ($_SERVER['HTTP_HOST']).$_SERVER['REQUEST_URI']
		);
		$this->_smarty->assign('data', $loanbooks);
		$this->displayTpl('retourbooksAjax.tpl');
	}


	/**
	 * Ein Buch zurückgeben
	 */
	function RetourBook($inventarnr,$uid) {

		$user = $this->_em->find('DM:SystemUsers', $uid);
		if($user) {
			$barcode = \Babesk\Schbas\Barcode::createByBarcodeString(
				$inventarnr
			);
			$inventory = $barcode->getMatchingBookExemplar($this->_em);
			if(!$inventory) { return false; }
			$lending = $inventory->getLending();
			if(!$lending) { return false; }
			try {
				$this->_em->remove($lending->first());
				$this->_em->flush();
				return true;
			}
			catch(Exception $e) {
				$this->_logger->logO('Could not retour a book',
					['sev' => 'error', 'moreJson' => $e->getMessage()]);
				return false;
			}
			return false;
		}
		else {
			return false;
		}
	}

	/**
	 * Looks the user for the given CardID up, checks if the Card is locked and returns the UserID
	 * @param string $card_id The ID of the Card
	 * @return string UserID
	 */
	public function GetUser ($card_id) {
		$isCard = TableMng::query(sprintf(
		'SELECT COUNT(*) FROM BabeskCards WHERE cardnumber LIKE "%s"',$card_id));

		$isUser = TableMng::query(sprintf(
				'SELECT COUNT(*) FROM SystemUsers WHERE username LIKE "%s"',$card_id));

		if ($isCard[0]['COUNT(*)']==="1") {
			if (!$this->cardManager->valid_card_ID($card_id))
				$this->_interface->dieError(sprintf($this->msg['err_card_id']));
			try {
				$uid = $this->cardManager->getUserID($card_id);
				if ($this->userManager->checkAccount($uid)) {
					$this->_interface->dieError(sprintf($this->msg['err_usr_locked']));
				}
			} catch (Exception $e) {
				$this->_interface->dieError($this->msg['err_get_user_by_card'] . ' Error:' . $e->getMessage());
			}
		}
		else if ($isUser[0]['COUNT(*)']==="1") {
			try {
				$uid = $this->userManager->getUserID($card_id);
				if ($this->userManager->checkAccount($uid)) {
					$this->_interface->dieError(sprintf($this->msg['err_usr_locked']));
				}
			} catch (Exception $e) {
				$this->_interface->dieError($this->msg['err_get_user_by_card'] . ' Error:' . $e->getMessage());
			}
		}
		else {
			$this->_interface->dieError(
				sprintf($this->msg['err_get_user_by_card'])
			);
		}
		return $uid;
	}

	public function fetchUserDetails($userId) {

		$userDetails = TableMng::query(sprintf(
			'SELECT u.*,
			(SELECT CONCAT(g.gradelevel, g.label) AS class
					FROM SystemAttendances uigs
					LEFT JOIN SystemGrades g ON uigs.gradeId = g.ID
					WHERE uigs.userId = u.ID AND
						uigs.schoolyearId = @activeSchoolyear) AS class
			FROM SystemUsers u WHERE `ID` = %s', $userId));


		return $userDetails[0];
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////


}

?>
