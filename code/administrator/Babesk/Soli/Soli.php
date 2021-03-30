<?php

require_once PATH_INCLUDE . '/Module.php';
require_once 'CopyOldOrdersToSoli.php';
require_once PATH_ADMIN . '/Babesk/Babesk.php';

/**
 * This Class is partially refactored, so some things might look odd/duplicated
 */
class Soli extends Babesk {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct ($name, $display_name, $path) {
		parent::__construct($name, $display_name, $path);
	}


	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute ($dataContainer) {

		defined('_AEXEC') or die('Access denied');
		require_once 'AdminSoliInterface.php';
		require_once 'AdminSoliProcessing.php';

		parent::entryPoint($dataContainer);
		parent::initSmartyVariables($dataContainer);
		$this->_interface = $dataContainer->getInterface();
		$soliInterface = new AdminSoliInterface($this->relPath);
		$soliProcessing = new AdminSoliProcessing($soliInterface);

		if (isset($_GET['action'])) {
			$action = $_GET['action'];
			switch ($action) {
				case 1: //add coupon
					if (isset($_POST['UID']) && isset($_POST['startDate']))
						$soliProcessing->AddCoupon(
								date('Y-m-d', strtotime($_POST['startDate'])),
								date('Y-m-d', strtotime($_POST['endDate'])),
								$_POST['UID']);
					else
						$soliProcessing->AddCoupon(NULL, NULL, NULL);
					break;
				case 2: //show coupons
					$soliProcessing->ShowCoupons();
					break;
				case 3: //show Soliusers
					$soliProcessing->ShowUsers();
					break;
				case 4: //show SoliOrders for specific User and Week
					if (isset($_POST['ordering_kw']) && isset($_POST['user_id']))
						$soliProcessing->ShowSoliOrdersByDate($_POST['ordering_kw'], $_POST['user_id']);
					else
						$soliProcessing->ShowSoliOrdersByDate(false, false);
					break;
				case 5: //delete coupon
					if (isset($_POST['delete']))
						$soliProcessing->DeleteCoupon($_GET['ID'], true);
					else if (isset($_POST['not_delete']))
						$soliProcessing->ShowCoupons();
					else
						$soliProcessing->DeleteCoupon($_GET['ID'], false);
					break;
				case 6: //Change Soli-Settings
					if (isset($_POST['user_id']))
						$soliProcessing->ChangeSettings($_POST['soli_price']);
					else
						$soliProcessing->ChangeSettings(NULL);
					break;
				case 7: //copy old orders to soli
					if (isset($_POST['copy'])) {
						// $soliProcessing->CopyOldOrdersToSoli();
						CopyOldOrdersToSoli::init($soliInterface, $this->_pdo);
						CopyOldOrdersToSoli::execute();
					}
					else if (isset($_POST['dont_copy']))
						$soliInterface->ShowInitialMenu();
					else
						$soliInterface->AskCopyOldOrdersToSoli();
					break;
				case 8: //Download SoliOrders of all Users for a specific week/month
					if(isset($_POST['ordering_kw']) && isset($_POST['Year']))
						$soliProcessing->AllSoliOrdersToPDFByWeekOrMonth($dataContainer, $_POST['ordering_kw'], $_POST['Year'], true);
					elseif (isset($_POST['ordering_month']) && isset($_POST['Year']))
						$soliProcessing->AllSoliOrdersToPDFByWeekOrMonth($dataContainer, $_POST['ordering_month'], $_POST['Year'], false);
					else 
						$soliInterface->AskWeekForPdf();
					break;
				case 9: //Show Orders of the user while the Coupon was active
					if(isset($_GET['ID'])){
						$soliProcessing->showOrdersByCoupon($_GET['ID']);
					}
			}

		}
		else {
			if(parent::execPathHasSubmoduleLevel(
				1, $this->_submoduleExecutionpath)) {

				$this->submoduleExecuteAsMethod(
					$this->_submoduleExecutionpath);
			}
			else {
				$soliInterface->ShowInitialMenu();
			}
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	/**==========================================**
	 * Allows the User to change the SoliSettings *
	 **==========================================**/
	protected function submoduleSettingsExecute() {

		if(isset($_POST['soliprice'])) {
			$this->soliSettingsDataChange();
			$this->_interface->dieSuccess(
				_g('The Settings were successfully changed'));
		}
		else {
			$this->soliSettingsFormDisplay();
		}
	}

	/**
	 * Changes the SoliSettings by the submitted form
	 */
	protected function soliSettingsDataChange() {

		$this->_pdo->beginTransaction();
		$this->solipriceEnabledSet(isset($_POST['solipriceEnabled']));
		$this->seperatePricesEnabledSet(isset($_POST['toggleSeperatePrices']));
		if(isset($_POST['toggleSeperatePrices'])){
			$this->seperatePricesSet($_POST);
		}else{
			$this->solipriceSet($_POST['soliprice']);
		}
		$this->_pdo->commit();
	}


	/**
	 * Displays the form allowing the User to Edit the Soli-Settings
	 */
	protected function soliSettingsFormDisplay() {

		$data = $this->soliSettingsDataFetch();
		$data = $this->soliSettingsDataAddIfNonexistend($data);
		$this->_smarty->assign('soliprice', $data[0]['soli_price']);
		$this->_smarty->assign(
			'solipriceEnabled', $data[0]['solipriceEnabled']);
		$this->_smarty->assign('seperate', $data[0]['seperateSoliPrices']);
		$this->_smarty->assign('priceclasses', $data[1]);
		$this->displayTpl('show_settings.tpl');
	}

	/**
	 * Fetches the Data for the Soli-Settings Form
	 *
	 * @return Array The fetched data
	 */
	protected function soliSettingsDataFetch() {

		try {
			$stmt = $this->_pdo->query('SELECT `name`, `value`
				FROM SystemGlobalSettings
				WHERE `name` IN("soli_price", "solipriceEnabled", "seperateSoliPrices");');

			$data[0] = ArrayFunctions::arrayColumn(
				$stmt->fetchAll(), 'value', 'name');
			
			$stmt = $this->_pdo->query('SELECT * FROM BabeskPriceClasses GROUP BY pc_id');
			$data[1] = $stmt->fetchAll();

		} catch (PDOException $e) {
			$this->_interface->dieError(_g('Could not fetch the Data!'));
		}

		return $data;
	}

	/**
	 * Adds Soli-Setting-Rows to the SystemGlobalSettings-Table if Not existend
	 * @param  array  $data The data fetched from the Server to check for if
	 * the Rows Exist
	 * @return array        the data but with the Missing Values added
	 */
	protected function soliSettingsDataAddIfNonexistend($data) {

		if(!isset($data[0]['soli_price'])) {
			$data['soli_price'] = $this->solipriceInit();
		}
		if(!isset($data[0]['solipriceEnabled'])) {
			$data['solipriceEnabled'] = $this->solipriceEnabledInit();
		}

		return $data;
	}

	/**
	 * Inserts the soliEnabled-Variable into the SystemGlobalSettings
	 *
	 * Dies displaying a Message on Error
	 *
	 * @return bool   The value of the newly inserted soliEnabled-Row
	 */
	// protected function soliEnabledInit() {

	// 	$default = 0;

	// 	try {
	// 		$this->_pdo->exec("INSERT INTO SystemGlobalSettings (`name`, `value`)
	// 			VALUES ('soliEnabled', {$default})");

	// 	} catch (PDOException $e) {
	// 		$this->_interface->dieError(_g('Could not initialize if the ' .
	// 			'Soli-Module is enabled or not'));
	// 	}

	// 	return $default;
	// }

	/**
	 * Sets the Soli-Enabled-Value
	 *
	 * Dies diesplaying a Message on Error
	 *
	 * @param  bool   $isEnabled If the Soli is enabled or not
	 */
	// protected function soliEnabledSet($isEnabled) {

	// 	$val = ($isEnabled) 1 : 0;

	// 	try {
	// 		$this->_pdp->exec("UPDATE SystemGlobalSettings SET `value` = {$val}
	// 			WHERE `name` = 'soliEnabled';");

	// 	} catch (PDOException $e) {
	// 		$this->_interface->dieError(
	// 			_g('Could not set if the Soli is enabled'));
	// 	}
	// }

	/**
	 * Creates the Soliprice in the GlobalSettings-Table with defaultvalue
	 *
	 * @return float The Default of the Soliprice
	 */
	protected function solipriceInit() {

		$default = 1.0;

		try {
			$this->_pdo->exec(
				"INSERT INTO `SystemGlobalSettings` (`name`, `value`) VALUES
					('soli_price', {$default});
			");

		} catch (PDOException $e) {
			$this->_interface->dieError(_g(
				'Could not initialize the Soliprice!'));
		}

		return $default;
	}

	/**
	 * Changes the Price for the Solis
	 *
	 * @param  float  $value The new Price
	 */
	protected function solipriceSet($value) {

		$value = str_replace(',', '.', $value);

		try {
			$stmt = $this->_pdo->prepare("UPDATE SystemGlobalSettings
				SET value = :value WHERE name = 'soli_price'");

			$stmt->execute(array('value' => $value));

		} catch (PDOException $e) {
			$this->_interface->dieError(_g('Could not change the Soliprice!'));
		}
	}
	
	/**
	 * Changes the Price for the Solis
	 *
	 * @param  float  $value The new Price
	 */
	protected function seperatePricesSet($post) {
		
		foreach ($post as $key => $value){
			if(substr($key, 0, 9) == 'soliprice'){
		$id = substr($key, 9);
		$value = str_replace(',', '.', $value);
	
		try {
			$stmt = $this->_pdo->prepare("UPDATE BabeskPriceClasses
				SET soliPrice = :value WHERE pc_id = :id");
	
			$stmt->execute(array('value' => $value, 'id' => $id));
	
		} catch (PDOException $e) {
			$this->_interface->dieError(_g('Could not change the Soliprice!'));
		}}}
	}

	/**
	 * Inserts the solipriceEnabled-Row into SystemGlobalSettings-Table
	 *
	 * Default is that the soliprice is not enabled
	 * Dies displaying a Message on Error
	 *
	 * @return bool  The Value of the newly inserted solipriceEnabled-Row
	 */
	protected function solipriceEnabledInit() {

		$default = 0;

		try {
			$this->_pdo->exec("INSERT INTO SystemGlobalSettings (`name`, `value`)
				VALUES ('solipriceEnabled', '{$default}')");

		} catch (PDOException $e) {
			$this->_interface->dieError(
				_g('Could not Initialize the isSolipriceEnabled-Variable'));
		}

		return (bool)$default;
	}

	/**
	 * Changes the Value of solipriceEnabled
	 *
	 * Dies displaying a Message on Error
	 *
	 * @param  bool   $isEnabled If the Soliprice is enabled or not
	 */
	protected function solipriceEnabledSet($isEnabled) {

		try {
			$stmt = $this->_pdo->prepare('UPDATE SystemGlobalSettings
				SET `value` = :val WHERE `name` = "solipriceEnabled"');

			$val = ($isEnabled) ? 1 : 0;

			$stmt->execute(array('val' => $val));

		} catch (PDOException $e) {
			$this->_interface->dieError(
				_g('Could not set if the Soliprice is enabled or not'));
		}
	}
	
	/**
	 * 
	 */
	protected function seperatePricesEnabledSet($isEnabled) {
	
		try {
			$stmt = $this->_pdo->prepare('UPDATE SystemGlobalSettings
				SET `value` = :val WHERE `name` = "seperateSoliPrices"');
	
			$val = ($isEnabled) ? 1 : 0;
	
			$stmt->execute(array('val' => $val));
	
		} catch (PDOException $e) {
			$this->_interface->dieError(
					_g('Could not set if the Soliprice is enabled or not'));
		}
	}

	/**
	 * Change Settings for Solis
	 * Enter description here ...
	 */
	function changeSettings($soli_price) {

		require_once PATH_ACCESS . '/GlobalSettingsManager.php';

		$gbManager = new GlobalSettingsManager();

		if ($soli_price !== NULL) {
			try {
				try {//inputcheck
					inputcheck($_POST['soli_price'], 'credits');
				} catch (Exception $e) {
					die_error(SOLI_ERR_INP_PRICE);
					$this->soliInterface->dieError($this->msg['SOLI_ERR_INP_PRICE']);
				}
				$gbManager->changeSoliPrice($_POST['soli_price']);
			} catch (Exception $e) {
				die_error(SOLI_ERR_CHANGE_PRICE . ':' . $e->getMessage());
				$this->soliInterface->dieError($this->msg['SOLI_ERR_CHANGE_PRICE '] . ':' . $e->getMessage());
			}
			$this->soliInterface->dieMsg($this->msg['SOLI_FIN_CHANGE']);
		} else {
			try {
				$soli_price = $gbManager->getSoliPrice();
			} catch (Exception $e) {
				$this->soliInterface->dieError($this->msg['SOLI_ERR_PRICE']);
			}
			$this->soliInterface->ChangeSettings($soli_price);
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	/**
	 * The Interface for this Module
	 * @var AdminInterface
	 */
	protected $_interface;
}

?>
