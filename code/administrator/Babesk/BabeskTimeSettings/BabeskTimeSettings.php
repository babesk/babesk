<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_ADMIN . '/Babesk/Babesk.php';

class BabeskTimeSettings extends Babesk {

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

		$execReq = $dataContainer->getExecutionCommand()->pathGet();
		if($this->submoduleCountGet($execReq)) {
			$this->submoduleExecuteAsMethod($execReq);
		}
		else {
			$this->changeDataForm();
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	/**
	 * The entryPoint when this Module is executed, sets up the Variables
	 *
	 * @param  dataContainer $dataContainer Contains data needed by the Module
	 */
	protected function entryPoint($dataContainer) {

		$this->_templateDir = PATH_SMARTY_TPL . '/administrator' . $this->relPath;
		$this->_interface = $dataContainer->getInterface();
		$this->_acl = $dataContainer->getAcl();
		$this->_smarty = $dataContainer->getSmarty();
		$this->_smarty->assign('header', $this->_templateDir . 'header.tpl');
	}

	/**
	 * Displays a form to the User in which he can change the Data
	 */
	protected function changeDataForm() {

		$data = $this->dataFetch();
		if(!count($data)) {
			$this->dataDefaultAddToDb();
			$data = $this->dataFetch();
		}
		$this->changeDataFormDisplay($data);
	}

	/**
	 * Fetches the Data from the Database, containing the Time-Modifications
	 *
	 * @return Array The parsed data
	 */
	protected function dataFetch() {

		try {
			$data = TableMng::query('SELECT * FROM SystemGlobalSettings
				WHERE name = "displayMealsStartdate" OR
					name = "displayMealsEnddate" OR
					name = "orderEnddate" OR
					name = "ordercancelEnddate";');

		} catch (Exception $e) {
			throw new Exception('Could not fetch the timing-Data!');
		}

		return $this->dataParse($data);
	}

	/**
	 * Parses the fetched Data from the Server to be more accessible
	 *
	 * @param  array $data The Data to Parse
	 * @return array The parsed data
	 */
	protected function dataParse($data) {

		$newData = array();

		foreach($data as $row) {
			$newData[$row['name']] = $row['value'];
		}

		return $newData;
	}

	/**
	 * Adds default-values of the  TimeModifications to the Database
	 */
	protected function dataDefaultAddToDb() {

		try {
			TableMng::query('INSERT INTO SystemGlobalSettings
				(name, value) VALUES
				("displayMealsStartdate", "last Monday"),
					("displayMealsEnddate", "this Friday +1 weeks"),
					("orderEnddate", "now +8 Hours"),
					("ordercancelEnddate", "now +8 Hours");');

		} catch (Exception $e) {
			throw new Exception('Could not insert default-data!');
		}
	}

	/**
	 * Displays the Form for changing the TimeModifications
	 *
	 * @param  array $data The Data that is atm in the Db
	 */
	protected function changeDataFormDisplay($data) {

		$this->_interface->showWarning('Verändern Sie die folgenden Daten nur, wenn Sie wissen, was Sie tun! Bei einer ungültigen Eingabe sind keine Bestellungen mehr möglich.');

		$this->_smarty->assign("displayMealsStartdate",
			$data['displayMealsStartdate']);
		$this->_smarty->assign("displayMealsEnddate",
			$data['displayMealsEnddate']);
		$this->_smarty->assign("orderEnddate",
			$data['orderEnddate']);
		$this->_smarty->assign("ordercancelEnddate",
			$data['ordercancelEnddate']);

		$this->_smarty->display($this->_templateDir . 'changeDataForm.tpl');
	}

	/**
	 * Uploads the changes of the TimeModification-Strings to the Database
	 */
	protected function submoduleChangeExecute() {

		TableMng::sqlEscapeByArray($_POST);

		if($this->inputDataCheck($_POST)) {
			$this->changeDataToDb($_POST);
		}
		else {
			$this->_interface->dieError('Not enough Data given');
		}

		$this->_interface->dieMsg('Die Daten wurden erfolgreich verändert.');
	}

	/**
	 * Checks for the given input describing the changes to be made
	 *
	 * @param  Array $data The data containing the TimeModifications
	 * @return boolean True of the given Input is usable, else false
	 */
	protected function inputDataCheck($data) {

		return isset($data['displayMealsStartdate'],
			$data['displayMealsEnddate'],
			$data['orderEnddate'],
			$data['ordercancelEnddate']);
	}

	/**
	 * Pushes the given data to the Database
	 *
	 * @param  Array $data An Array with the changed TimeModification-Strings
	 */
	protected function changeDataToDb($data) {

		try {
			TableMng::queryMultiple(
				"UPDATE SystemGlobalSettings
					SET value = '$data[displayMealsStartdate]'
					WHERE name = 'displayMealsStartdate';
				UPDATE SystemGlobalSettings
					SET value = '$data[displayMealsEnddate]'
					WHERE name = 'displayMealsEnddate';
				UPDATE SystemGlobalSettings
					SET value = '$data[orderEnddate]'
					WHERE name = 'orderEnddate';
				UPDATE SystemGlobalSettings
					SET value = '$data[ordercancelEnddate]'
					WHERE name = 'ordercancelEnddate';");

		} catch (Exception $e) {
			$this->_interface->dieError('Konnte die Daten nicht verändern!' . $e->getMessage());
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	protected $_interface;

	protected $_smarty;

	protected $_templateDir;
}

?>
