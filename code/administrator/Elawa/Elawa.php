<?php

namespace administrator\Elawa;

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_INCLUDE . '/exception_def.php';

class Elawa extends \Module {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct ($name, $display_name,$headmod_menu) {

		parent::__construct($name, $display_name,$headmod_menu);
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {
		$defaultMod = new \ModuleExecutionCommand(
			'root/administrator/Elawa/MainMenu'
		);
		$dataContainer->getAcl()->moduleExecute($defaultMod, $dataContainer);
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function entryPoint($dataContainer) {

		parent::entryPoint($dataContainer);
		parent::moduleTemplatePathSet();
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
}

?>