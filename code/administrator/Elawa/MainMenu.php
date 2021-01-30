<?php

namespace administrator\Elawa;

require_once 'Elawa.php';

class MainMenu extends Elawa {

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);
		$this->display();
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function entryPoint($dataContainer) {

		parent::entryPoint($dataContainer);
		parent::moduleTemplatePathSet();
	}

	private function display() {
		if(isset($_GET['action']) && $_GET['action'] == 1){ // changeHostGroup
            $query = $this->_pdo->prepare("UPDATE SystemGlobalSettings SET value = ? WHERE name = 'elawaHostGroupId'");
            $query->execute(array($_POST['host-group-select']));
		}
		$hostGroup = $this->_pdo->query("SELECT ID, name FROM SystemGroups WHERE id = 
                                                    (SELECT value FROM SystemGlobalSettings WHERE name = 'elawaHostGroupId')
                                                    ")->fetch();
        $selectionsEnabledObj = $this->_pdo->query("SELECT value FROM SystemGlobalSettings WHERE name = 'elawaSelectionsEnabled'")->fetch()[0];
		if($selectionsEnabledObj) {
			$selectionsEnabled = $selectionsEnabledObj != "0";
		}
		else {
			$selectionsEnabled = false;
		}

		$groups = $this->_pdo->query("SELECT ID, name FROM SystemGroups")->fetchAll();
		$groupArr = array();
		foreach ($groups as &$group){
			$row['ID'] = $group['ID'];
			$row['name'] = $group['name'];
			if($group['ID'] == $hostGroup['ID'])
				$row['selected'] = true;
			else 
				$row['selected'] = false;
			$groupArr[] = $row;
		}
		$groupArr = json_encode($groupArr);
		
		//$group = $this->_em->find('DM:SystemGroups', $hostGroupId);
		$this->_smarty->assign('allGroups', $groupArr);
		$this->_smarty->assign('selectionsEnabled', $selectionsEnabled);
		$this->_smarty->assign('hostGroup', $hostGroup);
		$this->displayTpl('mainMenu.tpl');
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////
}

?>