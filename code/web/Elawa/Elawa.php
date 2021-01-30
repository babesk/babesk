<?php

namespace web\Elawa;

require_once PATH_INCLUDE . '/Module.php';

class Elawa extends \Module {

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);
		$this->displayOverview();
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function entryPoint($dataContainer) {

		parent::entryPoint($dataContainer);
		parent::moduleTemplatePathSet();
	}

	protected function displayOverview() {


		$query = $this->_pdo->prepare("SELECT m.*, r.name as roomname, c.name as catname, u.name, u.forename FROM ElawaMeetings m
                                                LEFT JOIN SystemRooms r ON (r.id = m.roomId)
                                                JOIN ElawaCategories c ON (c.id = m.categoryId)
                                                JOIN SystemUsers u ON (u.ID = m.hostId)
                                                WHERE m.visitorId = ?");
		$query->execute(array($_SESSION['uid']));
		$meetings = $query->fetchAll();
		$this->_smarty->assign('meetings', $meetings);
		$this->displayTpl('overview.tpl');
	}

    protected function isElawaEnabled(){
        return $this->_pdo->query("SELECT value FROM SystemGlobalSettings WHERE name = 'elawaSelectionsEnabled'")->fetch()[0];
    }

    protected function getHostGroup() {

        $hostGroup = $this->_pdo->query("SELECT value FROM SystemGlobalSettings WHERE name = 'elawaHostGroupId'")->fetch();
        if($hostGroup) {
            return $hostGroup['value'];
        }
        else {
            $this->_interface->dieError('Keine Hostgroup definiert!');
        }
    }

    protected function getHosts() {

        $group = $this->getHostGroup();
        $query = $this->_pdo->prepare("SELECT u.*,r.* FROM SystemUsers u 
                                                JOIN SystemUsersInGroups g ON (u.ID = g.userId)
                                                LEFT JOIN ElawaDefaultMeetingRooms r ON (u.ID = r.hostId)
                                                WHERE g.groupId = ?
                                                ORDER BY u.name");
        $query->execute(array($group));
        $users = $query->fetchAll();
        if($users && count($users)) {
            return $users;
        }
        else {
            $this->_interface->dieMsg(
                'Keine Benutzer für die Hostgroup gefunden.'
            );
        }
    }
	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////
}

?>