<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_INCLUDE . '/Schbas/Loan.php';
require_once PATH_WEB . '/WebInterface.php';
require_once PATH_WEB . '/Schbas/Schbas.php';
require_once PATH_INCLUDE . '/Schbas/SchbasPdf.php';

class LoanSystem extends Schbas {

	////////////////////////////////////////////////////////////////////////////////
	//Attributes
	protected $_smartyPath;
	protected $_smarty;
	protected $_interface;

	////////////////////////////////////////////////////////////////////////////////
	//Constructor
	public function __construct($name, $display_name, $path) {
		parent::__construct($name, $display_name, $path);
		$this->_smartyPath = PATH_SMARTY_TPL . '/web' . $path;
	}

	////////////////////////////////////////////////////////////////////////////////
	//Methods
	public function execute($dataContainer) {

		$this->init($dataContainer);

		$schbasEnabled = $this->_pdo->query("SELECT * FROM SystemGlobalSettings WHERE name = 'isSchbasClaimEnabled'")->fetch();

		if(
			isset($_GET['action']) && $_GET['action'] == 'showLoanOverviewPdf'
		) {
			// Allow downloading the overview-pdf even when schbas is not
			// enabled at the moment
			$this->showLoanOverviewPdf();
		}
		if($schbasEnabled['value'] === '0') {
			$this->showLoanList();
		}
		else {

			if (isset($_GET['action'])) {
				$action=$_GET['action'];
				switch ($action) {
					case 'showPdf':
						$this->showSchbasInfoPdf();
						break;
					case 'showFormPdf':
						$this->showParticipationConfirmation();
						break;
					case 'loanShowBuy':
						$this->saveSelfBuy();
						break;
					default:
						die('wrong Action-value');
						break;
				}
			}
			else {
				$this->showMainMenu();
			}
		}
	}

	private function init($dataContainer) {

		defined('_WEXEC') or die("Access denied");
		$this->entryPoint($dataContainer);

		$this->_smarty = $dataContainer->getSmarty();
		$this->_interface = new WebInterface($this->_smarty);

		require_once PATH_INCLUDE . '/TableMng.php';
		TableMng::init();

	}

	private function showMainMenu() {

		$prepSchoolyear = $this->preparationSchoolyearGet();
		$user = array("ID" => $_SESSION['uid']);

		$gradelevelStmt = $this->_pdo->prepare(
			"SELECT gradelevel FROM SystemGrades g
				LEFT JOIN SystemAttendances uigs
					ON uigs.gradeId = g.ID
				WHERE uigs.userId = ? AND uigs.schoolyearId = @activeSchoolyear
		");
		$gradelevelStmt->execute(array($_SESSION['uid']));
		$gradelevel = $gradelevelStmt->fetchColumn();
		//Check if we got an entry back
		if($gradelevel === false) {
			$this->_logger->log('User accessing Schbas not in a grade!',
				'Notice', Null, json_encode(array('uid' => $_SESSION['uid'])));
			$this->_interface->dieError(
				'Du bist in keiner Klasse eingetragen!'
			);
		}


		$loanHelper = new \Babesk\Schbas\Loan($this->_dataContainer);
		$fees = $loanHelper->loanPriceOfAllBookAssignmentsForUserCalculate(
			$user
		);
		list($feeNormal, $feeReduced) = $fees;

		$loanbooksTest = $loanHelper->loanBooksOfUserGet(
			$user, ['includeSelfpay' => true, 'includeAlreadyLend' => true]
		);
		$selfpayingBooks = $this->_pdo->prepare("SELECT b.* FROM SchbasSelfpayer sp JOIN SchbasBooks b ON (sp.BID = b.id) WHERE UID = ?");
		$selfpayingBooks->execute(array($user['ID']));
		$selfpayingBooks = $selfpayingBooks->fetchAll();
		// [ {
		//     'book': '<book>',
		//     'selfpaying': '<boolean>'
		// } ]
		$booksWithSelfpayingStatus = array();
		foreach($loanbooksTest as $book) {
			$isSelfpaying = false;
			foreach ($selfpayingBooks as $selfpayingBook){
				if($book['id'] == $selfpayingBook['id']){
					$isSelfpaying = true;
				}
			}
			$booksWithSelfpayingStatus[] = [
				'book' => $book,
				'selfpaying' => $isSelfpaying
			];
		}

		$this->_smarty->assign('booksWithStatus', $booksWithSelfpayingStatus);
		$this->_smarty->assign('feeNormal', $feeNormal);
		$this->_smarty->assign('feeReduced', $feeReduced);
		$this->_smarty->assign('prepSchoolyear', $prepSchoolyear);
		$this->_smarty->assign('BaBeSkTerminal', $this->checkIsKioskMode());
		$this->_smarty->assign('loanShowForm', isset($_POST['loanShowForm']));
		$this->_smarty->assign('loanShowBuy', isset($_POST['loanShowBuy']));


		$this->_smarty->display($this->_smartyPath . 'menu.tpl');
	}



	private function showLoanList() {
		require_once PATH_ACCESS . '/LoanManager.php';
		require_once PATH_ACCESS . '/InventoryManager.php';
		require_once PATH_ACCESS . '/BookManager.php';
		$this->loanManager = new LoanManager();
		$this->inventoryManager = new InventoryManager();
		$this->bookManager = new BookManager();

		$loanbooks = $this->loanManager->getLoanlistByUID($_SESSION['uid']);
		$data = array();
		foreach ($loanbooks as $loanbook){
			$invData = $this->inventoryManager->getInvDataByID($loanbook['inventory_id']);
			$bookdata = $this->bookManager->getBookDataByID($invData['book_id']);
			$datatmp = array_merge($loanbook, $invData, $bookdata);
			$data[] = $datatmp;

		}
		$this->_smarty->assign('data', $data);
		$this->_smarty->display($this->_smartyPath . 'loanList.tpl');
	}

	/**
	 * Checks if the Client runs in Kioskmode
	 * We dont want to let the user circumvent the Kioskmode (for example if he
	 * opens PDF-files, another program gets opened up, which can break the
	 * kiosk-mode)
	 */
	private function checkIsKioskMode() {
		return preg_match("/BaBeSK/i", $_SERVER['HTTP_USER_AGENT']);
	}

	private function saveSelfBuy() {
		TableMng::query("DELETE FROM SchbasSelfpayer WHERE UID=".$_SESSION['uid']);
		if (isset($_POST['bookID'])) {
			foreach ($_POST['bookID'] as $book) {
				TableMng::query("INSERT IGNORE INTO SchbasSelfpayer (UID, BID) VALUES (".$_SESSION['uid'].",".$book.")");
			}
		}
		$this->_smarty->display($this->_smartyPath . 'saved.tpl');
	}

	protected function preparationSchoolyearGet() {


		$schoolyear = $this->_pdo->prepare("SELECT * FROM SystemSchoolyears WHERE ID = 
													(SELECT value FROM SystemGlobalSettings WHERE name = 'schbasPreparationSchoolyearId')");
		$schoolyear->execute();
		$schoolyear = $schoolyear->fetch();

		if(!$schoolyear) {
			$this->_logger->logO('Could not fetch PreparationSchoolyear',
				['sev' => 'error']);
			$this->SchbasAccountingInterface->dieError('Ein Fehler ist ' .
				'beim Abrufen des Vorbereitungs-Schuljahres aufgetreten.');
		}
		return $schoolyear;
	}

	private function showParticipationConfirmation() {

		$userQuery = $this->_pdo->prepare("SELECT ID, name, forename, username FROM SystemUsers WHERE ID = ?");
		$userQuery->execute(array($_SESSION['uid']));
		$user = $userQuery->fetch();

		$prepSchoolyear = $this->preparationSchoolyearGet();
        $gradeQuery = $this->_pdo->prepare("SELECT g.* FROM SystemGrades g JOIN SystemAttendances a ON (a.gradeId = g.ID)
                                            WHERE a.schoolyearId = ? AND a.userId = ?");
        $gradeQuery->execute(array($prepSchoolyear['ID'], $user['ID']));
        $grade = $gradeQuery->fetch();
		if(!$grade) {
			$this->_interface->dieError(
				'Der Schüler ist nicht im nächsten Schuljahr eingetragen. ' .
				'Bitte informieren sie die Schule.'
			);
		}

		$letterDate = date('d.m.Y');

        $schbasDeadlineTransferIso = $this->_pdo->query("SELECT value FROM SystemGlobalSettings WHERE name = 'schbasDeadlineTransfer'")->fetch();
		$schbasDeadlineTransfer = date(
			'd.m.Y', strtotime($schbasDeadlineTransferIso['value'])
		);

        $schbasDeadlineClaimIso = $this->_pdo->query("SELECT value FROM SystemGlobalSettings WHERE name = 'schbasDeadlineClaim'")->fetch();
		$schbasDeadlineClaim = date(
			'd.m.Y', strtotime($schbasDeadlineClaimIso['value'])
		);
        $bankAccount = $this->_pdo->query("SELECT value FROM SystemGlobalSettings WHERE name = 'bank_details'")->fetch();
		$bankData = explode('|', $bankAccount['value']);

		//get loan fees
		$loanHelper = new \Babesk\Schbas\Loan($this->_dataContainer);
		$fees = $loanHelper->loanPriceOfAllBookAssignmentsForUserCalculate(
			$user
		);
		list($feeNormal, $feeReduced) = $fees;

		$feedback = '';
		$loanChoice = filter_input(INPUT_POST, 'loanChoice');
		$loanFee = filter_input(INPUT_POST, 'loanFee');
		$siblings = filter_input(INPUT_POST, 'siblings');
		$eb_name = filter_input(INPUT_POST, 'eb_name');
		$eb_vorname = filter_input(INPUT_POST, 'eb_vorname');
		$eb_adress = filter_input(INPUT_POST, 'eb_adress');
		$eb_tel = filter_input(INPUT_POST, 'eb_tel');
		if($loanChoice == 'noLoan') { $feedback = 'nl'; }
		else if($loanFee == 'loanSoli') { $feedback = 'ls'; }
		else if($loanFee == 'loanNormal') { $feedback = 'ln'; }
		else if($loanFee == 'loanReduced') { $feedback = 'lr'; }

		$this->_smarty->assign('user', $user);
		$this->_smarty->assign('grade', $grade);
		$this->_smarty->assign('schoolyear', $prepSchoolyear['label']);
		$this->_smarty->assign('letterDate', $letterDate);
		$this->_smarty->assign('schbasDeadlineClaim', $schbasDeadlineClaim);
		$this->_smarty->assign('bankData', $bankData);
		$this->_smarty->assign('feeNormal', $feeNormal);
		$this->_smarty->assign('feeReduced', $feeReduced);
		$this->_smarty->assign('loanFee', $loanFee);
		$this->_smarty->assign('siblings', $siblings);
		$this->_smarty->assign('loanChoice', $loanChoice);
		$this->_smarty->assign('parentName', $eb_name);
		$this->_smarty->assign('parentForename', $eb_vorname);
		$this->_smarty->assign('parentAddress', $eb_adress);
		$this->_smarty->assign('parentTelephone', $eb_tel);
		$this->_smarty->assign(
			'schbasDeadlineTransfer', $schbasDeadlineTransfer
		);
		$content = $this->_smarty->fetch(
			PATH_SMARTY_TPL . '/pdf/schbas-participation-confirmation.pdf.tpl'
		);
		$schbasPdf = new \Babesk\Schbas\SchbasPdf(
			$user['ID'], $grade['gradelevel']
		);
		$barcode = $user['ID'] . ' ' . $feedback;
		$schbasPdf->create($content, $barcode);
		$schbasPdf->output();

	}

	private function showSchbasInfoPdf() {

		require_once PATH_INCLUDE . '/Schbas/LoanInfoPdf.php';
		$pdf = new \Babesk\Schbas\LoanInfoPdf($this->_dataContainer);
		$user = $this->_pdo->prepare("SELECT ID, name, forename FROM SystemUsers WHERE ID = ?");
		$user->execute(array($_SESSION['uid']));
		$user = $user->fetch();
		$pdf->setDataByUser($user);
		$pdf->showPdf();
	}

	private function showLoanOverviewPdf() {

		require_once PATH_INCLUDE . '/Schbas/LoanOverviewPdf.php';
		$pdf = new \Babesk\Schbas\LoanOverviewPdf($this->_dataContainer);
		$stmt = $this->_pdo->prepare("SELECT * FROM SystemUsers WHERE ID = ?");
		$stmt->execute(array($_SESSION['uid']));
		$user = $stmt->fetch();
		$pdf->showPdf($user);
	}
}
?>
