<?php

require_once PATH_INCLUDE . '/Module.php';

class Cancel extends Module {

	////////////////////////////////////////////////////////////////////////////////
	//Attributes
	private $smartyPath;

	////////////////////////////////////////////////////////////////////////////////
	//Constructor
	public function __construct($name, $display_name, $path) {
		parent::__construct($name, $display_name, $path);
		$this->smartyPath = PATH_SMARTY . '/templates/web' . $path;
	}

	////////////////////////////////////////////////////////////////////////////////
	//Methods
	public function execute($dataContainer) {
		error_reporting(E_ALL);
		global $smarty;
		global $logger;

		require_once PATH_ACCESS . '/GlobalSettingsManager.php';
		require_once PATH_ACCESS . '/SoliCouponsManager.php';
		require_once PATH_ACCESS . '/SoliOrderManager.php';
		require_once PATH_ACCESS . '/MealManager.php';
		require_once PATH_ACCESS . '/UserManager.php';
		require_once PATH_ACCESS . '/OrderManager.php';
		require_once PATH_ACCESS . '/PriceClassManager.php';

		$orderManager = new OrderManager();
		$priceClassManager = new PriceClassManager();
		$gbManager = new GlobalSettingsManager();
		$userManager = new UserManager();
		$soliCouponManager = new SoliCouponsManager();
		$soliOrderManager = new SoliOrderManager();

		try {
			$orderData = $orderManager->getEntryData($_GET['id'], 'MID');
			$mid = $orderData['MID'];
			$price = $priceClassManager->getPrice($_SESSION['uid'], $mid);
		} catch (Exception $e) {
			$logger->log('WEB|mod_cancel', 'MODERATE',
					sprintf('Error at ID %s, canceling MID %s: %s', $_GET['id'], $orderData['MID'], $e->getMessage()));
			die('<p class="error">Ein Fehler ist aufgetreten!</p>');
		}

		//"repay", add the price for the menu to the users account
		try {
			if($soliOrderManager->isExisting($_GET['id'])) {
				//It is a soliOrder
				if (!$userManager->changeBalance($_SESSION['uid'], $gbManager->getSoliPrice())) {
					$smarty->display($this->smartyPath . "failed.tpl");
					die();
				}
				//The ID of the soli_order is the same as the ID of the order;
				//Soliorders have another entry in soli_orders, delete it too
				$soliOrderManager->delEntry($_GET['id']);
			}
			else {
				//It is a normal Order
				if (!$userManager->changeBalance($_SESSION['uid'], $priceClassManager->getPrice($_SESSION['uid'], $mid))) {
					$smarty->display($this->smartyPath . "failed.tpl");
					die();
				}
			}
		} catch (Exception $e) {
			$logger->log('WEB|mod_cancel', 'MODERATE',
					sprintf('Error at ID %s, canceling MID %s: %s', $_GET['id'], $orderData['MID'], $e->getMessage()));
			die('<p class="error">Ein Fehler ist aufgetreten!</p>');
		}

		$orderManager->delEntry($_GET['id']);
		$smarty->display($this->smartyPath . "cancel.tpl");
	}
}
?>