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
		$settingsRep = $this->_em->getRepository('DM:SystemGlobalSettings');
		$groupsRep = $this->_em->getRepository('DM:SystemGroups');
		if(isset($_GET['action']) && $_GET['action'] == 1){ // changeHostGroup
				$settingsRep->setSetting('elawaHostGroupId', $_POST['host-group-select']);
		}
		
		$hostGroupId = $settingsRep->findOneByName('elawaHostGroupId');
		$selectionsEnabledObj = $settingsRep->findOneByName(
			'elawaSelectionsEnabled'
		);
		if($selectionsEnabledObj) {
			$selectionsEnabled = $selectionsEnabledObj->getValue() != "0";
		}
		else {
			$selectionsEnabled = false;
		}
		
		$groups = $groupsRep->findAll();
		$groupArr = array();
		foreach ($groups as &$group){
			$row['ID'] = $group->getId();
			$row['name'] = $group->getName();
			if($group->getId() == $hostGroupId->getValue())
				$row['selected'] = true;
			else 
				$row['selected'] = false;
			$groupArr[] = $row;
		}
		$groupArr = json_encode($groupArr);
		
		$group = $this->_em->find('DM:SystemGroups', $hostGroupId->getValue());
		$this->_smarty->assign('allGroups', $groupArr);
		$this->_smarty->assign('selectionsEnabled', $selectionsEnabled);
		$this->_smarty->assign('hostGroup', $group);
		$this->displayTpl('mainMenu.tpl');
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////
}

?>