<?php

require_once PATH_INCLUDE . '/Module.php';
require_once 'SchoolyearInterface.php';
require_once PATH_ADMIN . '/System/System.php';

class Schoolyear extends System {

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

		$this->_subExecPath = $dataContainer->getExecutionCommand()->pathGet();

		if(isset($_GET['action'])) {
			switch($_GET['action']) {
				case 'addSchoolYear':
					$this->addSchoolYear();
					break;
				case 'showSchoolYear':
					$this->showSchoolYears();
					break;
				case 'activateSchoolYear':
					$this->activateSchoolYear();
					break;
				case 'changeSchoolYear':
					$this->changeSchoolYear();
					break;
				case 'deleteSchoolYear':
					$this->deleteSchoolYear();
					break;
				default:
					$this->_interface->dieError(_g('Wrong action-value!'));
					break;
			}
		}
		else {
			if($this->execPathHasSubmoduleLevel(1, $this->_subExecPath)) {
				$this->submoduleExecuteAsMethod($this->_subExecPath, 1);
				die();
			}
			$this->_interface->displayMainMenu();
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function entryPoint($dataContainer) {

		defined('_AEXEC') or die('Access denied');
		parent::entryPoint($dataContainer);

		$this->_dataContainer = $dataContainer;
		$this->_interface = new SchoolyearInterface($this->relPath,
			$this->_dataContainer->getSmarty());
		$this->_acl = $dataContainer->getAcl();
	}

	protected function addSchoolYear() {

		if(isset($_POST['label'])) {

			if(!isset($_POST['active'])) {
				$_POST['active'] = false;
			}
			else {
				if($this->schoolyearIfActiveExistsIdGet()) {
					$this->_interface->dieError(_g('An active schoolyear already exists! Please add the schoolyear without it being active and then activate it in the menu.')
					);
				}
				$_POST['active'] = true;
			}
			$this->checkInput();
			$this->addSchoolYearToDatabase();
			$this->_interface->dieMsg(_g('The Schoolyear was added successfully'));
		}
		else {
			$this->showAddSchoolYearForm();
		}
	}

	protected function handleCheckboxActive($schoolyearId) {

		if(!isset($_POST['active'])) {
			$_POST['active'] = false;
		}
		else {
			if($this->schoolyearIfActiveExistsIdGet()) {
				$this->_interface->dieError(_g('An active schoolyear already exists! Please add the schoolyear without it being active and then activate it in the menu.')
				);
			}
			$_POST['active'] = true;
		}
	}

	/**
	 * Checks if an active schoolyear already exists and returns id
	 * @return int   the id if it exists, false if not
	 */
	private function schoolyearIfActiveExistsIdGet() {

		try {
			$res = $this->_pdo->query(
				'SELECT ID FROM SystemSchoolyears WHERE active = 1'
			);
			$id = $res->fetchColumn();
			if(is_numeric($id) && $id != 0) {
				return $id;
			}
			else {
				return false;
			}

		} catch (PDOException $e) {
			$this->_logger->log(
				'Could not check if an active schoolyear already exists',
				'Notice', Null, json_encode(array('msg' => $e->getMessage()))
			);
			$this->_interface->dieError(_g('Could not check if an active ' .
				'schoolyear already exists'));
		}
	}

	protected function checkInput() {

		try {
			inputcheck($_POST['label'], 'name', _g('Name of Schoolyear'));
		} catch(WrongInputException $e) {
			$this->_interface->dieError(sprintf($this->_languageManager->getText('errorInput'), $e->getFieldName()));
		}
	}

	/**
	 * Adds the schoolyear to the database
	 */
	protected function addSchoolYearToDatabase() {

		//MariaDB does not implicitly convert boolean to int
		$active = (int)$_POST['active'];

		try {
			$stmt = $this->_pdo->prepare('INSERT INTO SystemSchoolyears
				(label, active) VALUES (?, ?)');
			$stmt->execute(array($_POST['label'], $active));

		} catch(PDOException $e) {
			$this->_logger->log('Error adding a new Schoolyear',
				'Notice', Null, json_encode(array('msg' => $e->getMessage())));
			$this->_interface->dieError(_g('Could not add the Schoolyear'));
		}
	}

	protected function showAddSchoolYearForm() {

		$this->_interface->displayAddSchoolYear();
	}

	protected function showSchoolYears() {

		$schoolYears = $this->getAllSchoolYears();
		$this->_interface->displayShowSchoolYears($schoolYears);
	}

	protected function getAllSchoolYears() {

		try {
			$schoolyears = TableMng::query('SELECT * FROM SystemSchoolyears');

		} catch(Exception $e) {
			$this->_interface->dieError(_g('Error Fetching the Schoolyears!'));
		}

		if(!count($schoolyears)) {
			throw new Exception('No Schoolyears exist!');
		}
		return $schoolyears;
	}

	protected function deleteSchoolYear() {

		$this->_interface->backlink('administrator|System|Schoolyear');
		if(isset($_POST['dialogConfirmed'])) {
			$this->deleteSchoolYearInDatabase();
			$this->_interface->dieMsg(_g('The Schoolyear was successfully deleted'));
		}
		else if(isset($_POST['dialogNotConfirmed'])) {
			$this->_interface->dieMsg(_g('The Schoolyear was not deleted'));
		}
		else {
			$stmt = $this->_pdo->prepare("SELECT * FROM SystemSchoolyears WHERE id = ?");
			$stmt->execute(array($_GET['ID']));
			$sy = $stmt->fetch();
			if($sy) {
				$this->_interface->displayDeleteSchoolYearConfirmation($sy);
			}
			else {
				$this->_interface->dieMsg('Dieses Schuljahr wurde bereits ' .
					' gelöscht.');
			}
		}
	}

	protected function deleteSchoolYearInDatabase() {

		try {
			TableMng::query("DELETE FROM SystemSchoolyears WHERE ID = {$_GET['ID']}");

		} catch(Exception $e) {
			$this->_interface->dieError(_g('Could not delete the Schoolyear!'));
		}
	}

	protected function activateSchoolYear() {

		if(isset($_POST['dialogConfirmed'])) {
			$this->activateSchoolYearInDatabase();
			$this->_interface->dieMsg(_g('The Schoolyear was successfully activated'));
		}
		else if(isset($_POST['dialogNotConfirmed'])) {
			$this->_interface->dieMsg($this->_languageManager->getText('notActivateSchoolYear'));
		}
		else {
			$this->showActivateSchoolYearConfirmationDialog();
		}
	}

	protected function activateSchoolYearInDatabase() {

		TableMng::getDb()->autocommit(false);
		TableMng::query("UPDATE SystemSchoolyears SET active = 0
			WHERE active = 1");
		TableMng::query("UPDATE SystemSchoolyears SET active = 1
			WHERE ID = {$_GET['ID']}");
		TableMng::getDb()->autocommit(true);
	}

	protected function showActivateSchoolYearConfirmationDialog() {

		$schoolYear = $this->getSchoolYear();
		$this->_interface->displayActivateSchoolYearConfirmation($schoolYear);
	}

	protected function getSchoolYear() {

		try {
			$schoolyear = TableMng::querySingleEntry(
				"SELECT * FROM SystemSchoolyears WHERE ID = {$_GET['ID']}"
			);

		} catch(Exception $e) {
			$this->_interface->dieError(_g('Could not fetch the Schoolyear from the Database'));
		}
		return $schoolyear;
	}

	protected function changeSchoolYear() {

		if(isset($_POST['label'])) {
			$this->checkInput();
			if(!isset($_POST['active'])) {
				$_POST['active'] = false;
			}
			else {
				$activeId = $this->schoolyearIfActiveExistsIdGet();
				if($activeId && $activeId != $_GET['ID']) {
					$this->_interface->dieError(_g('An active schoolyear already exists! Please add the schoolyear without it being active and then activate it in the menu.')
					);
				}
				$_POST['active'] = true;
			}
			$this->changeSchoolYearInDatabase();
			$this->_interface->dieMsg(_g('The Schoolyear was successfully changed'));
		}
		else {
			$this->showChangeSchoolYear();
		}
	}

	protected function showChangeSchoolYear() {

		$schoolYear = $this->getSchoolYear();
		$this->_interface->displayChangeSchoolYear($schoolYear);
	}

	protected function changeSchoolYearInDatabase() {

		try {
			$active = (!empty($_POST['active'])) ? 1 : 0;
			TableMng::query("UPDATE SystemSchoolyears SET label = '{$_POST['label']}',
				active = '{$active}' WHERE ID = {$_GET['ID']}");

		} catch(Exception $e) {
			$this->_logger->log('Error changing the schoolyear',
				'Notice', Null, json_encode(array('msg' => $e->getMessage())));
			$this->_interface->dieError(_g('Could not change the Schoolyear'));
		}
	}

	/**
	 * Gets and escapes the Schoolyear-ID the User selected
	 *
	 * Dies with an Error if the Variable was not found
	 *
	 * @return string The SchoolyearId
	 */
	protected function schoolyearInputVarGet() {

		if(isset($_POST['schoolyearId'])) {
			TableMng::sqlEscape($_POST['schoolyearId']);
		}
		else {
			$this->_interface->dieError(_g('No schoolyear selected!'));
		}

		return $_POST['schoolyearId'];
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////


	protected $_interface;

	protected $_dataContainer;

	protected $_acl;

	protected $_subExecPath;
}

?>
