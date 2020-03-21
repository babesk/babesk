<?php

require_once PATH_INCLUDE . '/Module.php';
require_once 'MessageAuthorInterface.php';
require_once PATH_ADMIN . '/Messages/Messages.php';

class MessageAuthor extends Messages {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct ($name, $display_name, $path) {
		parent::__construct($name, $display_name, $path);
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);

		if(isset($_GET['action'])) {
			switch($_GET['action']) {
				case 'changeAuthorGroup':
					$this->authorGroupChange();
					break;
				default:
					die('Wrong action-value');
					break;
			}
		}
		else {
			$this->mainMenu();
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function entryPoint($dataContainer) {
		defined('_AEXEC') or die('Access denied');
		$this->_dataContainer = $dataContainer;
		$this->_interface = new MessageAuthorInterface($this->relPath, $this->_dataContainer->getSmarty());
	}

	protected function mainMenu() {

		if(count($groups = $this->groupsFetchAll())) {
			$authorGroupId = $this->authorGroupIdFetch();
			$authorGroup = $this->authorGroupSearchInGroups($authorGroupId,
				$groups);

			$this->_interface->mainMenu(json_encode($groups), $authorGroup);

		}
		else {
			$this->_interface->dieError('Bitte fügen sie zuerst Gruppen hinzu,
				deren Mitglieder das Nachrichtenversenden erlaubt werden kann!
				Es sind noch keine Gruppen vorhanden.');
		}
	}

	/**
	 * Fetches all groups from the database
	 *
	 * Shows an error if the connection to the database failed (does not die)
	 *
	 * @return array() An Array of Array-Elements describing the groups
	 */
	protected function groupsFetchAll() {

		$data = array();

		try {
			$data = TableMng::query('SELECT `ID`, `name` FROM SystemGroups;');

		} catch (MySQLVoidDataException $e) {
			return array();

		} catch (Exception $e) {
			$this->_interface->showError('Ein Fehler ist beim Abrufen der Gruppen entstanden!');
		}

		return $data;
	}

	/**
	 * Fetches the ID of the group that allowes its members to write Messages
	 *
	 * dies with an error if a problem occured
	 *
	 * @return int the ID of the group, or false if no entry exists yet
	 */
	protected function authorGroupIdFetch() {

		$data = array();

		try {
			$data = TableMng::query('SELECT value AS groupId
				FROM SystemGlobalSettings WHERE name = "messageEditGroupId";');

		} catch (MySQLVoidDataException $e) {
			return false;

		} catch (Exception $e) {
			$this->_interface->dieError('Ein Fehler ist beim abrufen der Autorengruppe entstanden!');
		}

		return $data[0]['groupId'];
	}

	/**
	 * Searches through an array and returns the matching Group
	 *
	 * If the ID of the group and the value of the authorgGroupId is the same,
	 * it returns the data of the group.
	 *
	 * @param  int $authorGroupId
	 * @param  array() $groups
	 * @return array() or, if not found, false
	 */
	protected function authorGroupSearchInGroups($authorGroupId, $groups) {

		foreach($groups as $group) {
			if($group['ID'] == $authorGroupId) {
				return $group;
			}
		}

		return false;
	}

	protected function authorGroupChange() {

		if(isset($_POST['group'])) {
			$groupId = TableMng::getDb()->real_escape_string($_POST['group']);

			if($this->groupExists($groupId)) {
				$this->authorGroupChangeCommit($groupId);
			}
			else {
				$this->_interface->dieError('Die Gruppe existiert nicht!');
			}
		}
		else {
			$this->_interface->dieError('Bitte wählen Sie eine Gruppe aus!');
		}
		$this->mainMenu();
	}

	/**
	 * Checks if the Group with the groupId exists
	 *
	 * @param int $id the ID of the group to check its existence
	 * @return true if the group exists, else false
	 */
	protected function groupExists($id) {

		$data = array();

		try {
			$data = TableMng::query(sprintf(
				'SELECT COUNT(*) AS count
				FROM SystemGroups WHERE `ID` = "%s"', $id));

		} catch (Exception $e) {
			$this->_interface->dieError('Konnte nicht überprüfen ob die Gruppe existiert'.$e);
		}

		return $data[0]['count'];
	}

	/**
	 * Changes the global Setting messageEditGroupId to the $newGroupId
	 *
	 * dies when error occured while changing the group
	 *
	 * @param  int $newGroupId the Id of the new group thats allowed to edit
	 * Messages
	 */
	protected function authorGroupChangeCommit($newGroupId) {

		try {
			TableMng::query(sprintf(
				'UPDATE SystemGlobalSettings
				SET `value` = "%s"
				WHERE `name` = "messageEditGroupId"', $newGroupId));

			if(TableMng::getDb()->affected_rows == 0) {
				TableMng::query(sprintf(
					'INSERT INTO SystemGlobalSettings (`value`, `name`)
					VALUES ("%s", "messageEditGroupId")', $newGroupId));
			}

		} catch (Exception $e) {
			$this->_interface->dieError('Konnte die Gruppe nicht verändern');
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	protected $_dataContainer;

	protected $_interface;

}

?>
