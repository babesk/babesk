<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_ADMIN . '/System/System.php';

class Help extends System {

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

		defined('_AEXEC') or die("Access denied");

		require_once 'AdminHelpProcessing.php';
		require_once 'AdminHelpInterface.php';
		require_once PATH_ACCESS . '/GlobalSettingsManager.php';

		$gbManager = new GlobalSettingsManager();
		$helpInterface = new AdminHelpInterface($this->relPath);
		$helpProcessing = new AdminHelpProcessing($helpInterface);

		if (isset($_GET['action'])) {
			try {
				switch ($_GET['action']) {
					case 1:
						//show the Help-Text
						try {
							$helptext = $gbManager->getHelpText();
						} catch (MySQLVoidDataException $e) {
							$helptext = '&nbsp;';
						}
						$helpInterface->ShowHelp($helptext);
						break;

					case 2:
						//edit the Help-Text
						if (isset($_POST['helptext'])) {
							$helpProcessing->change_help($_POST['helptext']);
						} else {
							try {
								$helptext = $gbManager->getHelpText();
							} catch (MySQLVoidDataException $e) {
								$helptext = '&nbsp;';
							}
							$helpInterface->EditHelp($helptext);
						}
						break;
				}
			} catch (Exception $e) {
				die_error($e->getMessage());
			}
		} else {
			$helpInterface->IndexMenu();
		}
	}
}
?>
