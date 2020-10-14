<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_ADMIN . '/Schbas/Schbas.php';
require_once PATH_INCLUDE . '/Schbas/LoanInfoPdf.php';

class SchbasSettings extends Schbas {

	////////////////////////////////////////////////////////////////////////////////
	//Attributes

	////////////////////////////////////////////////////////////////////////////////
	//Constructor
	public function __construct($name, $display_name, $path) {
		parent::__construct($name, $display_name, $path);
	}

	////////////////////////////////////////////////////////////////////////////////
	//Methods
	public function execute($dataContainer) {

		defined('_AEXEC') or die('Access denied');
		parent::entryPoint($dataContainer);
		parent::moduleTemplatePathSet();

		require_once 'AdminSchbasSettingsInterface.php';

		$SchbasSettingsInterface = new AdminSchbasSettingsInterface($this->relPath);

		if (!isset($_GET['action'])) {
            $SchbasSettingsInterface->InitialMenu();
        }else {
			switch ($_GET['action']){
				case 'editBankAccount':
					$this->editBankAccount();
					break;
				case '3':
					$SchbasSettingsInterface->RetourSettings();
					break;
				case '4':
					TableMng::query(sprintf("UPDATE SystemGlobalSettings SET value = '%s' WHERE name = '%s'", $_POST['owner']."|".$_POST['number']."|".$_POST['blz']."|".$_POST['institute'], 'bank_details'));
					break;
				case '5':
					$this->updateFee();
				$SchbasSettingsInterface->LoanSettings($SchbasSettingsProcessing->getLoanSettings(),true);
					break;
				case '6':
				$claim_date = $_POST['claim_Year']."-". $_POST['claim_Month']."-". $_POST['claim_Day'];
				$transfer_date = $_POST['transfer_Year']."-". $_POST['transfer_Month']."-". $_POST['transfer_Day'];
				TableMng::query(sprintf("UPDATE SystemGlobalSettings SET value = '%s' WHERE name = '%s'", $claim_date,"schbasDeadlineClaim"));
				TableMng::query(sprintf("UPDATE SystemGlobalSettings SET value = '%s' WHERE name = '%s'", $transfer_date,"schbasDeadlineTransfer"));
					break;
				case '7':
					$claimEnabled = TableMng::query(sprintf("SELECT value FROM SystemGlobalSettings WHERE name='isSchbasClaimEnabled'"));
				$SchbasSettingsInterface->enableFormConfirm($claimEnabled[0]['value']);
					break;
				case '8':
					$SchbasSettingsInterface->TextSettings();
					break;
				case '9':
					if (isset($_POST['enable'])){
					TableMng::query(sprintf("UPDATE SystemGlobalSettings SET value = '%s' WHERE name = '%s'", 1, 'isSchbasClaimEnabled'));
				}else{
					TableMng::query(sprintf("UPDATE SystemGlobalSettings SET value = '%s' WHERE name = '%s'", 0, 'isSchbasClaimEnabled'));
				}
				$SchbasSettingsInterface->enableFormConfirmFin();
					break;
				case '10': $this->saveTexts();
				break;
				case '11':
                    require_once PATH_ACCESS . '/SchoolyearManager.php';
                    $syManager = new SchoolyearManager();
                    require_once PATH_ACCESS . '/GlobalSettingsManager.php';
                    $globalSettings = new GlobalSettingsManager();
                    $prepSy = $globalSettings->valueGet('schbasPreparationSchoolyearId');
                    $prepSyName = $syManager->getEntryValue($prepSy, 'label');
                    $SchbasSettingsInterface->showMoveConfirm($prepSyName);
					break;
				case '12':
					$this->moveUsersToTmpClasses();
					$SchbasSettingsInterface->InitialMenu();
					break;
				case 'editCoverLetter': $this->editCoverLetter();
				break;
				case 'previewInfoDocs': $this->previewInfoDocs();
				break;
				case 'setReminder': if (isset($_POST['templateID']) && isset($_POST['authorID'])){
					TableMng::query(sprintf("UPDATE SystemGlobalSettings SET value = '%s' WHERE name = '%s'", $_POST['templateID'], 'schbasReminderMessageID'));
					TableMng::query(sprintf("UPDATE SystemGlobalSettings SET value = '%s' WHERE name = '%s'", $_POST['authorID'], 'schbasReminderAuthorID'));
					$SchbasSettingsInterface->enableFormConfirmFin();break;
				} else $this->setReminder();
				break;

				case 'fetchTextsAjax':
					$this->fetchTextsAjax();
					break;
			}
		}
	}

	function updateFee(){
		$stmt = TableMng::getDB()->prepare("UPDATE SchbasFee SET fee_normal = ?, fee_reduced = ? WHERE grade = ?;");
		for ($i = 5;$i<=12; $i++){
			$_POST[$i.'norm'] = str_replace (",", ".", $_POST[$i.'norm'] );
			$_POST[$i.'erm'] = str_replace (",", ".", $_POST[$i.'erm'] );
			$stmt->bind_param('sss', $_POST[$i.'norm'], $_POST[$i.'erm'], $i);
			if(!$stmt->execute()) {
				die('schinken');
			}
		}
		$stmt->close();
	}

	protected function fetchTextsAjax() {

		$templateId = TableMng::getDb()->real_escape_string($_POST['templateId']);
		$textId = TableMng::getDb()->real_escape_string($_POST['textId']);
		try {
			$template = TableMng::query(sprintf(
					'SELECT * FROM SchbasTexts WHERE `description` = "%s%s"',
					$textId,$templateId));

		} catch (Exception $e) {
			die('errorFetchTemplate');
		}

		die(json_encode($template[0]));
	}

	protected function saveTexts () {
		$grade = $_POST['grade'];
		$textOneTitle = $_POST['messagetitle'];
		$textOneText = $_POST['messagetext'];
		$textTwoTitle = $_POST['messagetitle2'];
		$textTwoText = $_POST['messagetext2'];
		$textThreeTitle = $_POST['messagetitle3'];
		$textThreeText = $_POST['messagetext3'];

		if ($textOneTitle == '') $textOneTitle = '&nbsp;';
		if ($textOneText == '') $textOneText = '&nbsp;';
		if ($textTwoTitle == '') $textTwoTitle = '&nbsp;';
		if ($textTwoText == '') $textTwoText = '&nbsp;';
		if ($textThreeTitle == '') $textThreeTitle = '&nbsp;';
		if ($textThreeText == '') $textThreeText = '&nbsp;';

		$updateStmt = $this->_pdo->prepare(
			'UPDATE SchbasTexts t SET t.title = :title, t.text = :text
			WHERE description = :description
		');

		try {
			$updateStmt->execute([
				'title' => $textOneTitle, 'text' => $textOneText,
				'description' => 'textOne' . $grade
			]);
			$updateStmt->execute([
				'title' => $textTwoTitle, 'text' => $textTwoText,
				'description' => 'textTwo' . $grade
			]);
			$updateStmt->execute([
				'title' => $textThreeTitle, 'text' => $textThreeText,
				'description' => 'textThree' . $grade
			]);
			$this->displayTpl('saveSuccess.tpl');
		} catch (Exception $e) {
			$this->displayTpl('saveFailed.tpl');
		};
	}

	protected function editBankAccount () {

		require_once 'AdminSchbasSettingsInterface.php';
		$SchbasSettingsInterface = new AdminSchbasSettingsInterface($this->relPath);

		if (isset($_POST['owner']) && isset($_POST['number']) && isset($_POST['blz']) && isset($_POST['institute'])) {
			try {
				TableMng::query(sprintf("UPDATE SystemGlobalSettings SET value = '%s' WHERE name = '%s'", $_POST['owner']."|".$_POST['number']."|".$_POST['blz']."|".$_POST['institute'], 'bank_details'));
				$SchbasSettingsInterface->SavingSuccess();
			} catch (Exception $e) {
				$SchbasSettingsInterface->SavingFailed();
			}
		}
		else {
			$temp = TableMng::query('SELECT value FROM SystemGlobalSettings WHERE name="bank_details"');
			$bankAccount = explode("|", $temp[0]['value']);
			$SchbasSettingsInterface->EditBankAccount($bankAccount[0],$bankAccount[1],$bankAccount[2],$bankAccount[3]);
		}
	}

	/**
	 * sets the schbas message template for the payment reminder
	 */
	protected function setReminder() {
		require_once 'AdminSchbasSettingsInterface.php';
		$SchbasSettingsInterface = new AdminSchbasSettingsInterface($this->relPath);

		$activeReminderID = TableMng::query('SELECT value FROM SystemGlobalSettings WHERE name="schbasReminderMessageID"');
		$reminderAuthorID = TableMng::query('SELECT value FROM SystemGlobalSettings WHERE name="schbasReminderAuthorID"');
		$SchbasSettingsInterface->showReminderSelection($activeReminderID,$reminderAuthorID,$this->templatesFetchSchbas());
	}

	/**
	 * Fetches all of the Templates from the database and returns them
	 *
	 * @return array() An Array of Elements represented as arrays themselfs or
	 * a void Array if no elements where found
	 */
	protected function templatesFetchSchbas() {

		$data = array();

		try {
			$data = TableMng::query('SELECT * FROM MessageTemplate WHERE GID=(SELECT ID FROM MessageGroups WHERE name="Schbas");');

		} catch (MySQLVoidDataException $e) {
			return array();

		} catch (Exception $e) {
			$this->_interface->dieError('Konnte die Vorlagen nicht abrufen');
		}

		return $data;
	}


	protected function editCoverLetter () {

		require_once 'AdminSchbasSettingsInterface.php';
		$SchbasSettingsInterface = new AdminSchbasSettingsInterface($this->relPath);

		if (isset($_POST['messagetitle']) && isset($_POST['messagetext'])) {
			$coverLetterTitle = $_POST['messagetitle'];
			$coverLetterText = $_POST['messagetext'];


			if ($coverLetterTitle == '') $coverLetterTitle = '&nbsp;';
			if ($coverLetterText == '') $coverLetterText = '&nbsp;';

			try {
				TableMng::query('UPDATE SchbasTexts SET title="'.$coverLetterTitle.'",text="'.$coverLetterText.'" WHERE description="coverLetter"',false);
				$SchbasSettingsInterface->SavingSuccess();
			} catch (Exception $e) {
				$SchbasSettingsInterface->SavingFailed();
			}
		}
		else {
			$title = TableMng::query('SELECT title FROM SchbasTexts WHERE description="coverLetter"');
			$text = TableMng::query('SELECT text FROM SchbasTexts WHERE description="coverLetter"');
			$SchbasSettingsInterface->EditCoverLetter($title[0]['title'],$text[0]['text']);
		}
	}

	protected function previewInfoDocs () {
		require_once 'AdminSchbasSettingsInterface.php';
		$SchbasSettingsInterface = new AdminSchbasSettingsInterface($this->relPath);
		if (isset($_POST['gradelabel'])) {
			$this->showPdf();
		}
		else {
			$SchbasSettingsInterface->ShowPreviewInfoTexts();
		}
	}

	private function showPdf() {

		$pdf = new \Babesk\Schbas\LoanInfoPdf($this->_dataContainer);
		$pdf->setDataByGradelevel($_POST['gradelabel']);
		$pdf->showPdf();

	}

	private function moveUsersToTmpClasses(){
        require_once 'AdminSchbasSettingsInterface.php';
        $SchbasSettingsInterface = new AdminSchbasSettingsInterface($this->relPath);
		require_once PATH_ACCESS . '/GlobalSettingsManager.php';
		require_once PATH_ACCESS . '/SchoolyearManager.php';
		require_once PATH_ACCESS . '/AttendancesManager.php';
		$globalSettings = new GlobalSettingsManager();
		$syManager = new SchoolyearManager();
		$attendancesManager = new AttendancesManager();
		$activeSy = $syManager->getActiveSchoolyear();
		$prepSy = $globalSettings->valueGet('schbasPreparationSchoolyearId');
		$attendances = $attendancesManager->getTableData("schoolyearId = ". $activeSy['ID']);
		$errArr = [];
        $err = "";
		foreach ($attendances as $attendance){
			$nextLevel = TableMng::query("SELECT gradelevel, label FROM SystemGrades WHERE id = ".$attendance['gradeId']);
			$nextGrade = TableMng::query("SELECT * FROM SystemGrades WHERE label = 'a' AND gradelevel = ".(1+$nextLevel[0]['gradelevel']));
			if(isset($nextGrade[0]) && !isset(TableMng::query("SELECT * FROM SystemAttendances WHERE userId = ".$attendance['userId']." AND schoolyearId = ".$prepSy)[0])) {
                $attendancesManager->addEntry('schoolyearId', $prepSy, 'gradeId', $nextGrade[0]['ID'], 'userId', $attendance['userId']);
            }elseif (isset($nextGrade[0])){
				$err .= "Der Nutzer ".$attendance['userId']." ist bereits im neuen Schuljahr eingetragen <br>";
			}else{
                $errArr[] = $nextLevel[0]['gradelevel'].$nextLevel[0]['label'];
			}
		}
		$errArr = array_unique($errArr);
		foreach ($errArr as $e){
			$err .= "Für die Klasse \"".$e."\" wurde kein Nachfolger gefunden<br>";
		}
		if($err != ""){
			$SchbasSettingsInterface->dieMsg($err);
		}

	}
}

?>
