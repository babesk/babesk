<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_ADMIN . '/Schbas/Schbas.php';
require_once PATH_INCLUDE . '/Schbas/Loan.php';
require_once PATH_INCLUDE . '/pdf/GeneralPdf.php';


class SchbasAccounting extends Schbas {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////



	public function execute($dataContainer) {
		//no direct access
		defined('_AEXEC') or die("Access denied");
		$this->entryPoint($dataContainer);

		require_once 'SchbasAccountingInterface.php';
		require_once PATH_ACCESS.'/LoanManager.php';

		$this->SchbasAccountingInterface = new SchbasAccountingInterface($this->relPath);
		$this->lm = new LoanManager();

		$this->_pdo = $dataContainer->getPdo();


		if(isset($_GET['action'])) {
			switch($_GET['action']) {

				case 'bookOverview':
					$this->bookOverview();
					break;
				case 'userSetReturnedFormByBarcode':
					$this->SchbasAccountingInterface->Scan();
					break;
				case 'userSetReturnedFormByBarcodeAjax':
					$this->userSetReturnedFormByBarcodeAjax();
					break;
				case 'userSetReturnedMsgByButtonAjax':
					$this->userSetReturnedMsgByButtonAjax();
					break;
				case 'sendReminder':
					$this->sendReminder();
					break;
				case 'deleteAll':
					$this->deleteAll();
					break;
				case 'remember':
					$this->remember();
					break;
				case 'userRemoveByID':
					if (isset($_POST['UserID'])){
						$this->userRemoveByID();
					}else{
						$this->SchbasAccountingInterface->showDelete();
					}
					break;
				case 'remember2':
					$this->remember2();
					break;
				case 'rebmemer2':
					$this->rebmemer2();
					break;
				default:
					die('Wrong action-value given');

					break;
			}
		}
		else {

			$listOfClasses = $this->getListOfClasses();
			$listOfClassesRebmemer = $this->getListOfClasses("rebmemer2");
			$gradesTbl = TableMng::query("SELECT * FROM SystemGrades");
			$this->_smarty->assign('listOfClasses', $listOfClasses);
			$this->_smarty->assign(
				'listOfClassesRebmemer', $listOfClassesRebmemer
			);
			$this->_smarty->assign('grades', $gradesTbl);
			$this->displayTpl('menu.tpl');
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function entryPoint($dataContainer) {

		parent::entryPoint($dataContainer);
		parent::moduleTemplatePathSet();
		$this->_loanHelper = new \Babesk\Schbas\Loan($dataContainer);
	}

	/**
	 * send reminders via the message module to all users who haven't payed the
	 * complete fee yet. this function uses the selected reminder template for usage.
	 */
	protected function sendReminder () {
		try {
			$loanHelper = new \Babesk\Schbas\Loan();
			$prepSchoolyear = $loanHelper->schbasPreparationSchoolyearGet();
			$template= TableMng::query("SELECT mt.title, mt.text FROM MessageTemplate AS mt WHERE mt.ID=(SELECT value FROM SystemGlobalSettings WHERE name='schbasReminderMessageID')");
			$author = TableMng::query("SELECT value FROM SystemGlobalSettings WHERE name='schbasReminderAuthorID'");
			$group = TableMng::query("SELECT ID FROM MessageGroups WHERE name='schbas'");
			TableMng::query("INSERT INTO MessageMessages (`ID`, `title`, `text`, `validFrom`, `validTo`, `originUserId`, `GID`) VALUES (NULL, '".$template[0]['title']."', '".$template [0]['text']."', '".date("Y-m-d")."', '".date("Y-m-d",strtotime("+4 weeks"))."', '".$author[0]['value']."', '".$group[0]['ID']."');");
			$messageID = TableMng::$db->insert_id;
			TableMng::query("INSERT INTO MessageManagers (`ID`, `messageID`, `userId`) VALUES (NULL, '".$messageID."','".$author[0]['value']."')");
			$usersToRemind = TableMng::query(
				"SELECT * FROM SchbasAccounting
				WHERE payedAmount < amountToPay
					AND schoolyearId = $prepSchoolyear
			");
			foreach ($usersToRemind as $user) {
				TableMng::query(
					"INSERT INTO MessageReceivers
					(`ID`, `messageID`, `userID`, `read`, `return`)
					VALUES (
						NULL, '$messageID', '".$user['userId']."',
						'0', 'noReturn'
					);
				");
			}
		}
		catch (Exception $e) {
		}
		$this->SchbasAccountingInterface->reminderSent();
	}

	/**
	 * based on the post-values given from Ajax, this function sets the
	 * has-user-returned-the-message-value to "hasReturned"
	 *
	 * @return void
	 */
	protected function userSetReturnedFormByBarcodeAjax() {

		$formDataStr = filter_input(INPUT_POST, 'barcode');
		if(!$formDataStr) {
			$this->SchbasAccountingInterface->dieError(
				'Bitte auszutauschenden oder neuen Antrag einscannen!'
			);
		}
		$formData = explode(' ', $formDataStr);
		if(count($formData) != 2) {
			$this->SchbasAccountingInterface->dieError(
				'Bitte auszutauschenden oder neuen Antrag einscannen!'
			);
		}

		$prepSchoolyear = $this->preparationSchoolyearGet();
		list($userId, $loanChoiceStr) = $formData;
		if($userId && $loanChoiceStr) {

			$loanChoices = array('nl','ln','lr','ls');
			$user = $this->getUserByID($userId);
			if(!$user) {
				$this->SchbasAccountingInterface->dieError(
					'Konnte den Benutzer nicht finden.'
				);
			}
			$stmt = $this->_pdo->prepare("SELECT * FROM SchbasAccounting WHERE userId = ? AND schoolyearId = ?");
			$stmt->execute(array($userId, $prepSchoolyear));
			$accounting = $stmt->fetch();
			if ($accounting) {
				http_response_code(409);
				die('Der Antrag für diesen Benutzer wurde bereits ' .
					'eingescannt. Bitte löschen Sie ihn manuell, um ihn neu ' .
					'hinzuzufügen.');
			}
			if(!$this->isUserInSchoolyearCheck($user, $prepSchoolyear)) {
				http_response_code(400);
				die('Der Benutzer ist im Vorbereitungsschuljahr in keiner ' .
					'Klasse');
			}
			if(in_array($loanChoiceStr, $loanChoices, true)) {

				require_once PATH_INCLUDE . '/Schbas/Loan.php';
				$loanHelper = new \Babesk\Schbas\Loan($this->_dataContainer);
				$stmt = $this->_pdo->prepare("SELECT * FROM SchbasLoanChoices WHERE abbreviation = ?");
				$stmt->execute(array($loanChoiceStr));
				$loanChoice = $stmt->fetch();
				try {
					list($feeNormal, $feeReduced) = $loanHelper
						->loanPriceOfAllBookAssignmentsForUserCalculate($user);

					if ($loanChoice['abbreviation'] == "ln") {
						$amountToPay = $feeNormal;
					}
					else if ($loanChoice['abbreviation'] == "lr") {
						$amountToPay = $feeReduced;
					}
					else {
						$amountToPay = 0.00;
					}

					$stmt = $this->_pdo->prepare("INSERT INTO SchbasAccounting(userId, loanChoiceId, payedAmount, amountToPay, schoolyearId) VALUES (?,?,?,?,?)");
					$stmt->execute(array($user['ID'], $loanChoice['ID'], 0, $amountToPay, $prepSchoolyear));
					die('success');
				}
				catch(\Exception $e) {
					$this->_logger->logO('Error adding accounting-entry',
						['sev' => 'error', 'moreJson' => $e->getMessage()]);
					die('Ein Fehler ist beim Hinzufügen aufgetreten.');
				}
			}
			else {
				die('notValid');
			}
		}
		else {
			die('error');
		}
	}

	protected function isUserInSchoolyearCheck($user, $schoolyear) {
		//Check if the user is in the SchbasPreparationSchoolyear
		$stmt = $this->_pdo->prepare("SELECT * FROM SystemAttendances WHERE userId = ? AND schoolyearId = ?");
		$stmt->execute(array($user['ID'], $schoolyear));
		return $stmt->fetch();
	}

	function userRemoveByID() {

		$formDataStr = filter_input(INPUT_POST, 'UserID');
		if(!$formDataStr) {
			$this->SchbasAccountingInterface->dieError(
				'Bitte auszutauschenden oder neuen Antrag einscannen!'
			);
		}
		$formData = explode(' ', $formDataStr);
		if(count($formData) != 2) {
			$this->SchbasAccountingInterface->dieError(
				'Bitte auszutauschenden oder neuen Antrag einscannen!'
			);
		}

		list($userId, $loanChoice) = $formData;
		if($userId && $loanChoice) {
			$user = $this->getUserByID($userId);
			$prepSchoolyear = $this->preparationSchoolyearGet();
			if($user) {
                $stmt = $this->_pdo->prepare("SELECT * FROM SchbasAccounting WHERE userId = ? AND schoolyearId = ?");
                $stmt->execute(array($userId, $prepSchoolyear));
                $accounting = $stmt->fetch();
				if($accounting) {
					$stmt = $this->_pdo->prepare("DELETE FROM SchbasAccounting WHERE userId = ? AND schoolyearId = ?");
                    $stmt->execute(array($userId, $prepSchoolyear));
					$this->SchbasAccountingInterface->showDeleteSuccess();
				}
				else {
					$this->SchbasAccountingInterface->dieError(
						'Benutzer hat noch keinen Antrag abgegeben!'
					);
				}
			}
			else {
				$this->SchbasAccountingInterface->dieError(
					'Benutzer-ID nicht gültig'
				);
			}
		}
		else {
			$this->SchbasAccountingInterface->dieError(
				'Bitte auszutauschenden oder neuen Antrag einscannen!'
			);
		}
	}

	protected function preparationSchoolyearGet() {

        $loanHelper = new \Babesk\Schbas\Loan($this->_dataContainer);
        $prepSchoolyear = $loanHelper->schbasPreparationSchoolyearGet();
		return $prepSchoolyear;
	}

	private function remember(){	// function prints lend books

		$showIdAfterName = false;				// to enable the id after name set this value to true

		$lending = TableMng::query('SELECT * FROM SchbasLending');

		for ($i=0; $i < (count($lending)); $i++){	// one loop prodices one line of the table
			//name
			$id = (int) $lending[$i]["user_id"];
			$name = TableMng::query("SELECT name FROM SystemUsers WHERE ID=$id");
			$name = $name[0]["name"];
			$forename = TableMng::query("SELECT forename FROM SystemUsers WHERE ID=$id");
			$forename = $forename[0]["forename"];
			if($showIdAfterName == true){
				$schueler = ("$forename $name($id)");
			}else{
				$schueler = ("$forename $name");
			}
			$schueler_arr[] = $schueler;

			//class
			try{
				$schoolyearDesired = TableMng::query('SELECT ID FROM SystemSchoolyears WHERE active = 1');
				$schoolyearID = $schoolyearDesired[0]['ID'];
				$gradeID = TableMng::query(sprintf("SELECT GradeID FROM SystemAttendances WHERE UserID = '$id' AND schoolyearID = $schoolyearID"));
				$gradeIDtemp = (int)$gradeID[0]['GradeID'];
				$gradelevel = TableMng::query(sprintf("SELECT gradelevel FROM SystemGrades WHERE ID = $gradeIDtemp"));
				$grade = $gradelevel[0]['gradelevel'];
				$label = TableMng::query(sprintf("SELECT label FROM SystemGrades WHERE ID = $gradeIDtemp"));
				$label = $label[0]['label'];
			}catch (Exception $e){
				$grade = 0;
			}
			$class = "$grade-$label";
			$class_arr[]= $class;

			//book
			$bookid = (int) $lending[$i]["inventory_id"];
			$title = TableMng::query("SELECT title FROM SchbasBooks WHERE id=$bookid");
			$book[] = $title[0]["title"];

			//date
			$date[] = $lending[$i]["lend_date"];
			//$date = date_format('%d.%m.%Y');
			//$date[] = $date;
			//$date[] = date_format(strtodate($lending[$i]["lend_date"]),"%d.%m.%Y");

		}
		$this->SchbasAccountingInterface->showRememberList($schueler_arr, $class_arr, $book, $date, count($lending)-1);
	}

	private function getStudentIDsOfClass($gradeId){
		$ids = TableMng::query("SELECT userId
			FROM SystemAttendances uigs
			JOIN SystemSchoolyears s ON uigs.schoolyearId = s.ID
			WHERE gradeId='$gradeId' AND s.active = true
		");
		$nr = count($ids);
		$studentIDs;
		for($i=0;$i<$nr;$i++){
			$studentIDs[$i] = $ids[$i]["userId"];
		}
		return $studentIDs;
	}

	private function getNameOfStudentId($studentId){
		$name = TableMng::query("SELECT name FROM SystemUsers WHERE ID='$studentId'");
		$name = $name[0]["name"];
		return $name;
	}

	private function getForenameOfStudentId($studentId){
		$forename = TableMng::query("SELECT forename FROM SystemUsers WHERE ID='$studentId'");
		$forename = $forename[0]["forename"];
		return $forename;
	}

	private function getBooksOfStudentId($studentId){
		$books = TableMng::query("SELECT inventory_id FROM SchbasLending WHERE user_id='$studentId'");
		$booklist = "";
		$nr = count($books);
		for($i=0;$i<$nr;$i++){
			$bookid = TableMng::query("SELECT book_id FROM SchbasInventory WHERE id='".$books[$i]["inventory_id"]."'");
			$bookIDs[] = $bookid[0]["book_id"];
		}

		for ($i=0;$i<$nr;$i++){
			$bookName = TableMng::query("SELECT title FROM SchbasBooks WHERE id='$bookIDs[$i]'");
			if (!empty($bookName)) {
			$bookName = $bookName[0]["title"];
			if ($i==0){
				$booklist = "$bookName";
			}else{
				$booklist = "$booklist </br> $bookName";
			}
			}
		}

		return $booklist;
	}

	private function remember2(){	// function prints lend books

		if(!isset($_GET['class'])){
			die("ERROR: No Class selected.");
		}else{

			$classId = $_GET['class'];

			$classNamelabel = TableMng::query("SELECT label FROM SystemGrades WHERE ID='$classId'");
			$classNamelabel = $classNamelabel[0]["label"];
			$classNamelevel = TableMng::query("SELECT gradelevel FROM SystemGrades WHERE ID='$classId'");
			$classNamelevel = $classNamelevel[0]["gradelevel"];
			$className = "$classNamelevel$classNamelabel";

			$studentIDs = $this->getStudentIDsOfClass($classId);

			$nrOfStudentIDs = count($studentIDs);
			for($i=0; $i<$nrOfStudentIDs; $i++){
				$name[] = $this->getNameOfStudentId($studentIDs[$i]);
				$forename[] = $this->getForenameOfStudentId($studentIDs[$i]);
				$books[] = $this->getBooksOfStudentID($studentIDs[$i]);
			}

			$listOfClasses = $this->getListOfClasses();

		}
		$this->SchbasAccountingInterface->showRememberList2($name, $forename, $books, $nrOfStudentIDs-1, $className, $listOfClasses);
	}

	private function getListOfClasses($func="remember2"){
		$gradesTbl = TableMng::query("SELECT * FROM SystemGrades");
		$nr = count($gradesTbl);

		$listOfClasses="";

		for ($i=0; $i<$nr; $i++){
			$gradesTblLine = $gradesTbl[$i];
			$gradeId = $gradesTblLine["ID"];
			$gradelabel = $gradesTblLine["label"];
			$gradelevel = $gradesTblLine["gradelevel"];
			$listOfClasses = "$listOfClasses <a class='btn btn-default btn-sm' href='./index.php?section=Schbas|SchbasAccounting&action=".$func."&class=$gradeId'>$gradelevel$gradelabel</a>";
		}
		return $listOfClasses;
	}

	private function rebmemer2(){		// REBMEMER IS REMEMBER BACKWARDS, BECAUSE IT DOES THE OPPOSITE (and i like it... ;P)
		if(!isset($_GET['class'])){
			die("ERROR: No Class selected.");
		}else{
			$classId = $_GET['class'];

			$classNamelabel = TableMng::query("SELECT label FROM SystemGrades WHERE ID='$classId'");
			$classNamelabel = $classNamelabel[0]["label"];
			$classNamelevel = TableMng::query("SELECT gradelevel FROM SystemGrades WHERE ID='$classId'");
			$classNamelevel = $classNamelevel[0]["gradelevel"];
			$className = "$classNamelevel$classNamelabel";

			$studentIDs = $this->getStudentIDsOfClass($classId);

			$nrOfStudentIDs = count($studentIDs);	// excluded from for loop to increase speed.... (dont like it? channge it...)
			$name = $forename = $books = [];
			foreach($studentIDs as $userId) {
				$name[] = $this->getNameOfStudentId($userId);
				$forename[] = $this->getForenameOfStudentId($userId);
				$books[] = $this->_loanHelper->loanBooksOfUserGet(array('ID' => $userId));
			}

			$listOfClasses = $this->getListOfClasses("rebmemer2");
		}
		$this->SchbasAccountingInterface->showRebmemerList2($name, $forename, $books, $nrOfStudentIDs-1, $className, $listOfClasses);

	}

	private function deleteAll()
	{
		try {
			$stmt = $this->_pdo->query('TRUNCATE TABLE schbas_accounting');
			$stmt->execute();
			$this->SchbasAccountingInterface->dieSuccess('Tabelle Buchhaltung erfolgreich geleert!');
		} catch (PDOException $e) {
			$this->SchbasAccountingInterface->dieError('Konnte die Tabelle Buchhaltung nicht leeren!');
		}

	}

	private function bookOverview() {

		$loanHelper = new \Babesk\Schbas\Loan($this->_dataContainer);
		$stmt = $this->_pdo->prepare("SELECT * FROM SystemSchoolyears WHERE active = 1");
		$stmt->execute();
		$schoolyear = $stmt->fetch();
		$gradeId = filter_input(INPUT_POST, 'grade');
		if(!$gradeId) {
			$this->_interface->dieError('Keine Klasse übergeben');
		}
		$stmt = $this->_pdo->prepare("SELECT u.* FROM UserActiveClass u JOIN SystemAttendances a ON (u.ID = a.userId) WHERE gradeId = ? AND schoolyearId = ?");
		$stmt->execute(array($gradeId, $schoolyear['ID']));
		$users = $stmt->fetchAll();

		// The only difference between the PDF for booksToReturn and
		// booksToLoan is the title and the included books
		$pdfTitle = '';
		$syName = $schoolyear['label'];
		$gradename = $users[0]['gradelevel'] . $users[0]['label'];
		$usersWithBooks = [];

		if(isset($_POST['booksToReturn'])) {
			$pdfTitle = "Abzugebende Bücher für $gradename ($syName)";
			foreach($users as $user) {
				$books = $loanHelper->lendBooksToReturnOfUserGet(
					$user, $schoolyear['ID']
				);
				$usersWithBooks[] = [
					'user' => $user,
					'books' => $books
				];
			}
		}
		else if(isset($_POST['booksToLoan'])) {
			$pdfTitle = "Noch auszuleihende Bücher für $gradename ($syName)";
			foreach($users as $user) {
				$books = $loanHelper->loanBooksOfUserGet(
					$user, ['schoolyear' => $schoolyear['ID']]
				);
				$usersWithBooks[] = [
					'user' => $user,
					'books' => $books
				];
			}
		}
		$date = date('d.m.Y H:i:s');
		$this->_smarty->assign('date', $date);
		$this->_smarty->assign('usersWithBooks', $usersWithBooks);
		$this->_smarty->assign('pdfTitle', $pdfTitle);
		$pdfContent = $this->_smarty->fetch(
			PATH_SMARTY_TPL . '/pdf/schbas-books-overview.pdf.tpl'
		);
		$pdf = new GeneralPdf($this->_pdo);
		$pdf->create($pdfTitle, $pdfContent);
		$pdf->output();
	}

	private function getUserByID($uid){
        $stmt = $this->_pdo->prepare("SELECT * FROM SystemUsers WHERE ID = ?");
        $stmt->execute(array($uid));
        $user = $stmt->fetch();
        return $user;
	}


	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////
}
?>
