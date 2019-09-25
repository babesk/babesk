<?php
/**
 *@file meals.php handles all parts of the mealsmodule and combines them with the needed sourcefiles outside (like Database-functions)
 */

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_ADMIN . '/Babesk/Babesk.php';

class Meals extends Babesk {

	///////////////////////////////////////////////////////////////////////
	//Constructor
	///////////////////////////////////////////////////////////////////////

	public function __construct($name, $display_name, $path) {
		parent::__construct($name, $display_name, $path);
	}

	///////////////////////////////////////////////////////////////////////
	//Methods
	///////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {
		// No direct access
		defined('_AEXEC') or die("Access denied");

		require_once 'AdminMealProcessing.php';
		require_once 'AdminMealInterface.php';

		$mealInterface = new AdminMealInterface($this->relPath);
		$mealProcessing = new AdminMealProcessing(
			$mealInterface, $dataContainer
		);

		parent::entryPoint($dataContainer);
		parent::initSmartyVariables();
		$this->_interface = $mealInterface;

		if (isset($_GET["action"])) {

			switch ($_GET["action"]) {
				case 1:
					$mealProcessing->CreateMeal();
					break;
				case 2:
					$mealProcessing->ShowMeals();
					break;
				case 3:
					$mealProcessing->ShowOrders();
					break;
				case 4:
					$mealProcessing->DeleteOldMealsAndOrders();
					break;
				case 5:
					$mealProcessing->DeleteMeal($_GET['id'], true);
					break;
				case 6:
					$mealProcessing->EditInfotext();
					break;
				case 8:
					$mealProcessing->DuplicateMeal($_POST['name'], $_POST['description'], $_POST['pcID'], $_POST['date'], $_POST['max_orders']);
					break;
				case 9:
					$mealProcessing->PrintOrders();
			}
		} else {

			//Check if new-style Submoduleexecution is used
			$execReq = $dataContainer->getExecutionCommand()->pathGet();
			if($this->submoduleCountGet($execReq)) {
				$this->submoduleExecuteAsMethod($execReq);
				die();
			}
			$mealInterface->Menu();
		}
	}

	///////////////////////////////////////////////////////////////////////
	//Implements
	///////////////////////////////////////////////////////////////////////

	protected function submoduleMaxOrderAmountExecute() {

		if(isset($_POST['maxOrderAmount'])) {
			$this->maxOrderAmountChange();
		}
		else {
			$this->maxOrderAmountSettingDisplay();
		}
	}

	protected function maxOrderAmountChange() {

		$this->maxOrderAmountInputCheck();
		$this->maxOrderAmountUpload($_POST['maxOrderAmount']);
		$this->_interface->dieSuccess(_g('The value for the maximum amount of orders per Day for a User was successfully changed'));
	}

	/**
	 * Checks the Userinput changing the Amount of Max Orders per Day and User
	 *
	 * Dies displaying an Error when Userinput not correct
	 * Uses $_POST['maxOrderAmount']
	 */
	protected function maxOrderAmountInputCheck() {

		require_once PATH_INCLUDE . '/gump.php';
		$gump = new GUMP();

		$rules = array(
			'maxOrderAmount' => array(
				'required|min_len,1|max_len,2|numeric',
				'',
				_g('Maximum number of orders per day')
			)
		);
		$gump->rules($rules);

		if(!$gump->run($_POST)) {
			$this->_interface->dieError(
				$gump->get_readable_string_errors(true));
		}
	}

	/**
	 * Commits the Change to the Max Amount of Orders per Day to the Db
	 *
	 * Dies displaying an Errormessage on Error
	 *
	 * @param  int $amount The Amount of Orders per Day
	 */
	protected function maxOrderAmountUpload($amount) {

		try {
			if($this->maxCountOfOrdersPerDayPerUserGet() !== false) {
				TableMng::query("UPDATE SystemGlobalSettings SET value = '$amount'
					WHERE name = 'maxCountOfOrdersPerDayPerUser';");
			}
			else {
				TableMng::query("INSERT INTO SystemGlobalSettings (name, value)
					VALUES ('maxCountOfOrdersPerDayPerUser', '$amount');");
			}
		} catch (Exception $e) {
			$this->_interface->dieError(_g('Could not change the Max Amount of Orders per day per User.') . $e->getMessage());
		}
	}

	/**
	 * Displays the Form to set the maxOrderAmount per Day and User
	 *
	 * Dies displaying the form or Error
	 */
	protected function maxOrderAmountSettingDisplay() {

		if(($data = $this->maxCountOfOrdersPerDayPerUserGet()) !== false) {
			$amount = (int)$data;
		}
		else {
			$amount = 0;
		}
		$this->_interface->maxOrderAmountSetting($amount);
	}

	/**
	 * Fetches and returns the Allowed maximum of Orders per day and User
	 *
	 * @return string The Maximum of Orders or false if setting not found
	 */
	protected function maxCountOfOrdersPerDayPerUserGet() {

		$data = TableMng::querySingleEntry(
			'SELECT * FROM SystemGlobalSettings
				WHERE name = "maxCountOfOrdersPerDayPerUser"');

		if(!count($data)) {
			return false;
		}
		else {
			return $data['value'];
		}
	}

	/**==========================================**
	 * Allows the User to edit the Menu-Infotexts *
	 **==========================================**/
	protected function submoduleEditMenuInfotextsExecute() {

		if(!isset($_POST['infotext1'], $_POST['infotext2'])) {
			$this->menuInfotextsEditDisplay();
		}
		else {
			$this->menuInfotextsChange(
				$_POST['infotext1'], $_POST['infotext2']);
		}
	}

	/**
	 * Fetches the Infotexts from the Database
	 *
	 * @return Array The Infotexts in the Format '<name>' => '<value>'
	 */
	protected function menuInfotextsFetch() {

		try {
			$stmt = $this->_pdo->query(
				'SELECT name, value FROM SystemGlobalSettings
					WHERE name IN("menu_text1", "menu_text2")');

			$data = $stmt->fetchAll();
			return ArrayFunctions::arrayColumn($data, 'value', 'name');

		} catch (PDOException $e) {
			$this->_interface->dieError(
				_g('An Error occured while fetching the infotexts!'));
		}
	}

	/**
	 * Displays the Infotexts for the Meals-Menu and allows to edit them
	 */
	protected function menuInfotextsEditDisplay() {

		$infotexts = $this->menuInfotextsFetch();

		if(!isset($infotexts['menu_text1'])) {
			$infotexts['menu_text1'] = '';
		}
		if(!isset($infotexts['menu_text2'])) {
			$infotexts['menu_text2'] = '';
		}
		$this->_smarty->assign('infotexts', $infotexts);
		$this->displayTpl('edit_infotext.tpl');
	}

	/**
	 * Changes the MenuInfotexts
	 *
	 * @param  string $text1 The Value of the first Infotext
	 * @param  string $text2 The Valie of the second Infotext
	 */
	protected function menuInfotextsChange($text1, $text2) {

		$infotexts = $this->menuInfotextsFetch();

		$this->menuInfotextChangeConsideringEntryExistence(
			$infotexts, 'menu_text1', $text1);
		$this->menuInfotextChangeConsideringEntryExistence(
			$infotexts, 'menu_text2', $text2);

		$this->_interface->dieSuccess(
			_g('The Infotexts where successfully changed!'));
	}

	/**
	 * Changes or adds the MenuInfotexts to the Database
	 *
	 * Checks if the infotext-Entry exists, if not adds it
	 *
	 * @param  array  $infotexts     The existing infotexts fetched from the
	 * Database, allows for checking if they exist or not
	 * @param  string $infotextName  The name of the infotext
	 * @param  string $infotextValue The Infotext itself
	 */
	protected function menuInfotextChangeConsideringEntryExistence(
		$infotexts, $infotextName, $infotextValue) {

		if(isset($infotexts[$infotextName])) {
			$this->menuInfotextChange($infotextName, $infotextValue);
		}
		else {
			$this->menuInfotextAdd($infotextName, $infotextValue);
		}
	}

	/**
	 * Adds a MenuInfotext to the SystemGlobalSettings-Table
	 *
	 * Dies displaying a Message on Error
	 *
	 * @param  string $name     The Name of the Infotext
	 * @param  string $infotext The actual Infotext
	 */
	protected function menuInfotextAdd($name, $infotext) {

		try {
			$stmt = $this->_pdo->prepare(
				'INSERT INTO SystemGlobalSettings (name, value) VALUES
					(:name, :infotext)');

			$stmt->execute(array('name' => $name, 'infotext' => $infotext));

		} catch (PDOException $e) {
			$this->_interface->dieError(_g('Could not change the Infotext!'));
		}
	}

	/**
	 * Changes a MenuInfotext in the SystemGlobalSettings-Table
	 *
	 * Dies displaying a Message on Error
	 *
	 * @param  string $name     The Name of the Infotext
	 * @param  string $infotext The actual Infotext
	 */
	protected function menuInfotextChange($name, $infotext) {

		try {
			$stmt = $this->_pdo->prepare(
				'UPDATE SystemGlobalSettings SET
					value = :infotext WHERE name = :name');

			$stmt->execute(array('name' => $name, 'infotext' => $infotext));

		} catch (PDOException $e) {
			$this->_interface->dieError(_g('Could not change the Infotext!'));
		}
	}

	///////////////////////////////////////////////////////////////////////
	//Attributes
	///////////////////////////////////////////////////////////////////////

	protected $_interface;
}


?>
