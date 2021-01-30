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
	    $currentSel = $this->isElawaEnabled();

		if($currentSel == $areSelEnabled) {
			//Nothing changed
			die(json_encode($currentSel));
		}
		else {
			$newSel = ($areSelEnabled) ? '1' : '0';
            $this->_pdo->prepare("UPDATE SystemGlobalSettings SET value = ? WHERE name = 'elawaSelectionsEnabled'")->execute(array($areSelEnabled));
			die(json_encode($newSel));
		}
	}


	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////
}

?>