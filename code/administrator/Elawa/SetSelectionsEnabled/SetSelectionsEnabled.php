<?php

namespace administrator\Elawa\SetSelectionsEnabled;

require_once PATH_ADMIN . '/Elawa/Elawa.php';

class SetSelectionsEnabled extends \administrator\Elawa\Elawa {

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		parent::entryPoint($dataContainer);
		if(
			isset($_POST['areSelectionsEnabled']) &&
			!isBlank($_POST['areSelectionsEnabled'])
		) {
			$areSelEnabled = $_POST['areSelectionsEnabled'] == 'true';
			$this->setSelectionsEnabled($areSelEnabled);
		}
		else {
			http_response_code(400);
			$this->_logger->log('Correct data not send by client.',
				'Notice', Null, json_encode(array('postData' => $_POST)));
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function setSelectionsEnabled($areSelEnabled) {

		$selection = $this->_em->getRepository('DM:SystemGlobalSettings')
			->findOneByName('elawaSelectionsEnabled');
		if(!$selection) {
			$selection = $this->createSelectionEntry($areSelEnabled);
		}
		$currentSel = ($selection->getValue() != '0');
		if($currentSel == $areSelEnabled) {
			//Nothing changed
			die(json_encode($currentSel));
		}
		else {
			$newSel = ($areSelEnabled) ? '1' : '0';
			$selection->setValue($newSel);
			$this->_em->persist($selection);
			$this->_em->flush();
			die(json_encode($newSel));
		}
	}

	protected function createSelectionEntry($areSelEnabled) {

		$sel = ($areSelEnabled) ? '1' : '0';
		$selection = new \Babesk\ORM\SystemGlobalSettings();
		$selection->setName('elawaSelectionsEnabled')
			->setValue($sel);
		$this->_em->persist($value);
		$this->_em->flush();
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////
}

?>