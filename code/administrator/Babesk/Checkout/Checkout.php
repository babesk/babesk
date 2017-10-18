<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_ADMIN . '/Babesk/Babesk.php';

class Checkout extends Babesk {

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
		//no direct access
		defined('_AEXEC') or die("Access denied");

		require_once 'AdminCheckoutProcessing.php';
		require_once 'AdminCheckoutInterface.php';

		parent::entryPoint($dataContainer);
		parent::initSmartyVariables($dataContainer);
		$this->_interface = $dataContainer->getInterface();

		$checkoutInterface = new AdminCheckoutInterface($this->relPath);
		$checkoutProcessing = new AdminCheckoutProcessing($checkoutInterface);

		if (isset($_GET['action'])){
			$action = $_GET['action'];
			switch ($action){
				case 1: //Checkout
					if(isset($_POST['card_ID'])) {
						$checkoutProcessing->Checkout($_POST['card_ID']);
					}else{
						$checkoutProcessing->Checkout(null);
					}
					break;
				case 2: //Settings anzeigen
					$checkoutProcessing->ShowSettings();
					break;
				case 3: //Settings ändern
					$checkoutProcessing->SaveSettings($_POST['count_last_meals']);
					break;
				case 4: //Farbeinstellungen anzeigen
					$checkoutProcessing->ShowColorSettings();
					break;
				case 5: //Farbeinstellungen ändern
					$checkoutProcessing->SaveColorSettings($_POST);
					break;
			}
		}else {
			if(parent::execPathHasSubmoduleLevel(
				1, $this->_submoduleExecutionpath)) {

				$this->submoduleExecuteAsMethod(
					$this->_submoduleExecutionpath);
			}
			else {
				$checkoutInterface->ShowInitialMenu();
			}
		}
	}
}

?>
