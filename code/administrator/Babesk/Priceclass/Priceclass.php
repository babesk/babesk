<?php

/**
 * @file priceclass.php
 * Adding, changing and deleting the priceclass-mySQLtable in an easy
 * way for the admin (with forms etc)
 */

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_ADMIN . '/Babesk/Babesk.php';

class Priceclass extends Babesk {

	////////////////////////////////////////////////////////////////////////////////
	//Attributes

	////////////////////////////////////////////////////////////////////////////////
	//Constructor
	public function __construct ($name, $display_name, $path) {
		parent::__construct($name, $display_name, $path);
	}

	////////////////////////////////////////////////////////////////////////////////
	//Methods
	public function execute ($dataContainer) {
		//no direct access
		defined('_AEXEC') or die("Access denied");

		require_once 'AdminPriceclassInterface.php';
		require_once 'AdminPriceclassProcessing.php';

		$pcInterface = new AdminPriceclassInterface($this->relPath);
		$pcProcessing = new AdminPriceclassProcessing($pcInterface);

			switch ($_GET['action']) {
				case 1:
					$pcProcessing->NewPriceclass();
					break;
				case 2:
					$pcProcessing->ShowPriceclasses();
					break;
				case 3:
					$pcProcessing->DeletePriceclass($_GET['where']);
					break;
				case 4:
					$pcProcessing->ChangePriceclass($_GET['where']);
					break;
				default:
                    $pcInterface->Menu();
			}
	}
}

?>
