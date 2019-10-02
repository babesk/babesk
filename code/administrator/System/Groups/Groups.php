<?php
/**
 * @file groups.php
 * The module offers an interface to the Admin so that he can change the group-table data
 * without having to use programs such as phpmyadmin
 */

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_ADMIN . '/System/System.php';

class Groups extends System {

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

		require_once 'AdminGroupProcessing.php';
		require_once 'AdminGroupInterface.php';

		$groupInterface = new AdminGroupInterface($this->relPath);
		$groupProcessing = new AdminGroupProcessing($groupInterface);

		if (isset($_GET['action'])) {

			$action = $_GET['action'];
			switch ($action) {
				case '1':
					$groupProcessing->NewGroup();
					break;
				case '2':
					$groupProcessing->ShowGroups();
					break;
				case '3':
					$groupProcessing->DeleteGroup($_GET['where']);
					break;
				case '4':
					$groupProcessing->ChangeGroup($_GET['where']);
					break;
				default:
					$groupInterface->dieError('Wrong value of GET-variable action!');
					break;
			}
		}
		else {
			$groupInterface->Menu();
		}
	}
}

?>
