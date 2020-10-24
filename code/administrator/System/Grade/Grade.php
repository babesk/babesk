<?php

require_once 'GradeInterface.php';
require_once PATH_INCLUDE . '/Module.php';
require_once PATH_ADMIN . '/System/System.php';

/**
 * Grade-Module
 */
class Grade extends System {

	///////////////////////////////////////////////////////////////////////
	//Constructor
	///////////////////////////////////////////////////////////////////////

	public function __construct ($name, $display_name, $path) {
		parent::__construct($name, $display_name, $path);
	}

	///////////////////////////////////////////////////////////////////////
	//Methods
	///////////////////////////////////////////////////////////////////////

	public function execute ($dataContainer) {

		$this->entryPoint($dataContainer);

		if($this->execPathHasSubmoduleLevel(1, $this->_subExecPath)) {
			$this->submoduleExecuteAsMethod($this->_subExecPath, 1);
		}
		else {
			$this->_interface->displayMainMenu();
		}

		if (isset($_GET['action'])) {
			switch ($_GET['action']) {
				case 'addGrade':
					$this->addGrade();
					break;
				case 'showGrades':
					$this->showGrades();
					break;
				case 'deleteGrade':
					$this->deleteGrade();
					break;
				case 'changeGrade':
					$this->changeGrade();
					break;
				default:
					$this->_interface->dieError(_g('wrong action-value!'));
					break;
			}
		}
		else {

		}
	}

	///////////////////////////////////////////////////////////////////////
	//Implementations
	///////////////////////////////////////////////////////////////////////

	protected function entryPoint ($dataContainer) {

		parent::entryPoint($dataContainer);
		$this->_dataContainer = $dataContainer;
		$this->_interface = new GradeInterface($this->relPath, $this->_dataContainer->getSmarty());
		$this->_subExecPath = $dataContainer->getExecutionCommand()->pathGet();
	}

	protected function submoduleAddGradeExecute() {

		if (isset($_POST['gradelabel'], $_POST['gradelevel'])) {
			$this->gradeInputPreprocess();
			$this->addGradeToDatabase();
			$this->_interface->dieMsg(
				_g('Grade "%1$s-%2$s" was successfully added',
					$_POST['gradelevel'], $_POST['gradelabel']));
		}
		else {
			$schooltypes = $this->fetchAllSchooltypes();
			$this->_interface->displayAddGrade($schooltypes);
		}
	}

	/**
	 * Checks, sanitizes and Escapes the Userinput
	 *
	 * Dies if User submitted incorrect data
	 */
	protected function gradeInputPreprocess() {

		require_once PATH_INCLUDE . '/gump.php';
		$gump = new GUMP();

		$rules = array(
			'gradelabel' => array('required|min_len,1|max_len,255',
				'sql_escape', _g('Gradelabel')),
			'gradelevel' => array('required|numeric|min_len,1|max_len,3',
				'sql_escape', _g('Gradelevel')),
			'schooltype' => array('numeric|min_len,1|max_len,11',
				'sql_escape', _g('Schooltype'))
		);

		$gump->rules($rules);

		if(!$gump->run($_POST)) {
			$this->_interface->dieError(
				$gump->get_readable_string_errors(true));
		}
	}

	protected function addGradeToDatabase() {

		$schooltypeId = (!empty($_POST['schooltype'])) ?
			$_POST['schooltype'] : 0;

		try {
			TableMng::query("INSERT INTO SystemGrades
					(label, gradelevel, schooltypeId)
				VALUES ('$_POST[gradelabel]', '$_POST[gradelevel]',
					'$schooltypeId')");

		} catch (Exception $e) {
			$this->_interface->dieError(
				_g('Error adding the Grade "%1$s-%2$s"', $_POST['gradelevel'],
					$_POST['gradelabel']));
		}
	}

	protected function submoduleShowGradesExecute() {

		$grades = $this->getAllGrades();
		$this->_interface->displayShowGrades($grades);
	}

	/**
	 * Fetches all Grades that are in the Database and returns them
	 *
	 * Dies with an Error when no Grades where found or could not be fetched
	 *
	 * @return array The fetched Grades
	 */
	protected function getAllGrades() {

		try {
			$grades = TableMng::query(
				'SELECT g.*, st.name AS schooltypeName
				FROM SystemGrades g
				LEFT JOIN SystemSchooltypes st ON g.schooltypeId = st.ID
				ORDER BY gradelevel, label
				');

		} catch (Exception $e) {
			$this->_interface->dieError(_g('Could not fetch the Grades'));
		}
		if(!count($grades)) {
			$this->_interface->dieError(_g('No Grades found'));
		}

		return $grades;
	}

	/**
	 * Deletes the Grade and all Linked Tableentrys from the Database
	 */
	protected function submoduleDeleteGradeExecute() {

		TableMng::sqlEscape($_GET['ID']);
		TableMng::getDb()->autocommit(false);
		$this->deleteGradeFromDatabase();
		$this->deleteLinkedItems($_GET['ID']);
		TableMng::getDb()->autocommit(true);
		$this->_interface->dieMsg(_g('The Grade was successfully deleted'));
	}

	/**
	 * Deletes the Entry in the Grades-Table from the Database
	 *
	 * Dies on Error
	 */
	protected function deleteGradeFromDatabase() {

		try {
			TableMng::query("DELETE FROM SystemGrades WHERE ID = $_GET[ID]");

		} catch (Exception $e) {
			$this->_interface->dieError(
				_g('Error deleting the Grade with the ID %1$s!', $_GET['ID']));
		}
	}

	/**
	 * deletes a link between a Grade and other objects in database
	 *
	 * Dies when Error occured while deleting the Items
	 *
	 * @param int $gradeId The ID of the grade
	 */
	protected function deleteLinkedItems($gradeId) {

		try {
			TableMng::query("DELETE FROM SystemAttendances
				WHERE gradeId = $gradeId");

		} catch (Exception $e) {
			$this->_interface->dieError(_g('Could not delete the Joins of the GradeId "%1$s"', $gradeId));
		}
	}

	/**
	 * Fetches and returns the Data for the Grade with the given ID
	 *
	 * Dies when the Grade could not be fetched
	 *
	 * @param  int $gradeId The GradeID of the Grade to fetch
	 * @return array The grade-Data
	 */
	protected function getGrade($gradeId) {

		try {
			$stmt = $this->_pdo->prepare(
				'SELECT * FROM SystemGrades WHERE ID = ?');
			$stmt->execute(array($_GET['ID']));
			return $stmt->fetch();

		} catch (PDOException $e) {
			$this->_logger->log('Could not fetch the Grade', 'error', NULL,
				json_encode(array('error' => $e->getMessage())));
			$this->_interface->dieError(_g('Could not fetch the Grade'));
		}

		return $grade;
	}

	/**
	 * Handles the Changing of the Grade the User selected
	 *
	 * Dies on everything
	 */
	protected function submoduleChangeGradeExecute() {

		if (isset($_POST['gradelabel'], $_POST['gradelevel'])) {

			$this->gradeInputPreprocess();
			$this->changeGradeInDatabase();
			$this->_interface->dieMsg(
				_g('The Grade was successfully changed'));
		}
		else {
			$this->showChangeGrade();
		}
	}

	/**
	 * Changes the Entry in the Grades-Table
	 *
	 * Dies when Grade could not be changed
	 */
	protected function changeGradeInDatabase() {

		$schooltypeId = (!empty($_POST['schooltype']))
			? $_POST['schooltype'] : 0;

		try {
			$stmt = $this->_pdo->prepare('UPDATE SystemGrades SET label = ?,
				gradelevel = ?, schooltypeId = ? WHERE ID = ?');

			$stmt->execute(array($_POST['gradelabel'], $_POST['gradelevel'],
				$schooltypeId, $_GET['ID']));

		} catch (PDOException $e) {
			$this->_logger->log('Error changing a Grade', 'error', Null,
				json_encode(array('error' => $e->getMessage())));
			$this->_interface->dieError(_g('Could not change the Grade!'));
		}
	}

	/**
	 * Displays a dialog in which the User can change the data of the Grade
	 */
	protected function showChangeGrade() {

		$grade = $this->getGrade($_GET['ID']);
		$schooltypes = $this->fetchAllSchooltypes();
		$this->_interface->displayChangeGrade($grade, $schooltypes);
	}

	/**
	 * Fetches all Schooltypes in the database and returns them
	 *
	 * Dies if Error occured while fetching the Schooltypes
	 *
	 * @return Array
	 */
	protected function fetchAllSchooltypes() {

		try {
			$data = TableMng::query('SELECT * FROM SystemSchooltypes');

		} catch (Exception $e) {
			$this->_interface->dieError('Konnte die Schultypen nicht abrufen');

		}

		return $data;
	}

	///////////////////////////////////////////////////////////////////////
	//Attributes
	///////////////////////////////////////////////////////////////////////

	/**
	 * @var DataContainer
	 */
	protected $_dataContainer;

	protected $_subExecPath;
}

?>
