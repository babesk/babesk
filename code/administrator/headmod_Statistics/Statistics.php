<?php

require_once PATH_INCLUDE . '/HeadModule.php';


/**
 * class for Interface administrator
 * @author Pascal Ernst <pascal.cc.ernst@googlemail.com>
 *
 */
class Statistics extends HeadModule {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct($name, $display_name,$headmod_menu) {
		parent::__construct($name, $display_name,$headmod_menu);
		defined('PCHART_PATH')
			OR define('PCHART_PATH', PATH_INCLUDE . '/pChart');
		defined('PATH_STATISTICS_CHART')
			OR define('PATH_STATISTICS_CHART',realpath(dirname(__FILE__)));

	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($moduleManager, $dataContainer) {
		//function not needed, javascript is doing everything
	}

	public function executeModule($mod_name, $dataContainer) {

		parent::executeModule($mod_name, $dataContainer);
	}

}
?>