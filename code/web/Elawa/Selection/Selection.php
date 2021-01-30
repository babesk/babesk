<?php

namespace web\Elawa\Selection;

require_once PATH_WEB . '/Elawa/Elawa.php';

class Selection extends \web\Elawa\Elawa {

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		parent::entryPoint($dataContainer);
		if($this->checkIsSelectionGloballyEnabled()) {
			if(isset($_POST['meetingId'])) {
				$this->registerSelection($_POST['meetingId']);
			}
			else if(isset($_GET['hostId'])) {
				$this->displaySelection($_GET['hostId']);
			}
			else {
				$this->displayHostSelection();
			}
		}
		else {
			$this->_interface->dieError(
				'Die Wahlen finden momentan nicht statt.'
			);
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function displaySelection($hostId) {
        $query = $this->_pdo->prepare("SELECT m.*, c.name as CategoryName FROM ElawaMeetings m 
                                                JOIN ElawaCategories c ON (m.categoryId = c.id)
                                                LEFT JOIN SystemRooms r ON (m.roomId = r.id)
                                                WHERE m.hostId = ?
                                                ORDER BY m.time ASC");
        $query->execute(array($hostId));
		$meetings = $query->fetchAll();

		$query = $this->_pdo->prepare("SELECT ID, name, forename FROM SystemUsers WHERE ID = ?");
		$query->execute(array($hostId));
		$host = $query->fetch();

		$this->_smarty->assign('meetings', $meetings);
		$this->_smarty->assign('host', $host);
		$this->displayTpl('selection.tpl');
	}

	protected function registerSelection($meetingId) {

		$query = $this->_pdo->prepare("SELECT * FROM ElawaMeetings WHERE id = ?");
		$query->execute(array($meetingId));
		$meeting = $query->fetch();

		$this->_interface->moduleBacklink('web|Elawa');
		if(!$meeting) {
			$this->_interface->dieError('Diese Sprechzeit existiert nicht!');
		}
		$this->checkRegisterSelectionValid($meeting, $_SESSION['uid']);

		$query = $this->_pdo->prepare("UPDATE ElawaMeetings SET visitorId = ? WHERE id = ?");
		$query->execute(array($_SESSION['uid'], $meetingId));

		$this->_interface->dieSuccess(
			'Die Sprechzeit wurde erfolgreich angemeldet'
		);
	}

	protected function checkRegisterSelectionValid($meeting, $user) {

		$countQuery = $this->_pdo->prepare("SELECT COUNT(id) FROM ElawaMeetings WHERE visitorId = ? AND hostId = ?");
		$countQuery->execute(array($user, $meeting['hostId']));
		$count = $countQuery->fetch()[0];
		if($count) {
			$this->_interface->dieError(
				'Sie sind bereits bei dieser Person angemeldet!'
			);
		}
		if($meeting['isDisabled']) {
			$this->_interface->dieError('Diese Sprechzeit ist deaktiviert!');
		}
		if($meeting['visitorId'] != 0) {
			$this->_interface->dieError(
				'Diese Sprechzeit ist leider schon vergeben. '
			);
		}
	}

	/**
	 * Displays a list of hosts to choose from
	 */
	protected function displayHostSelection() {


		$hosts = $this->getHosts();
		//Get all hosts for which the user already has made a selection
		$votedHostsQuery = $this->_pdo->prepare("SELECT m.* FROM ElawaMeetings m WHERE m.visitorId = ? GROUP BY hostId");
		$votedHostsQuery->execute(array($_SESSION['uid']));
		$meetingsOfVotedHosts = $votedHostsQuery->fetchAll();

		$hostsAr = array();
		foreach($hosts as $host) {
			$status = "";
			$selectable = true;
			if($host['ID'] == $_SESSION['uid']) {
				$status = "Du selbst";
				$selectable = false;
			}
			foreach($meetingsOfVotedHosts as $meetingOfVotedHost) {
				if($host['ID'] == $meetingOfVotedHost['hostId']) {
					$status = "Bereits Termin gewählt";
					$selectable = false;
				}
				continue;
			}
			$hostsAr[] = array(
				'statusText' => $status,
				'selectable' => $selectable,
				'host' => $host
			);
		}
		$this->_smarty->assign('hosts', $hostsAr);
		$this->displayTpl('host_selection.tpl');
	}

	/**
	 * Checks if the selections are globally enabled
	 * @return boolean Returns true if selections are enabled, else false
	 */
	protected function checkIsSelectionGloballyEnabled() {

		$enabledRow = $this->_pdo->query("SELECT value FROM SystemGlobalSettings WHERE name = 'elawaSelectionsEnabled'")->fetch()[0];
		if($enabledRow) {
			if($enabledRow != '0') {
				return true;
			}
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////
}

?>