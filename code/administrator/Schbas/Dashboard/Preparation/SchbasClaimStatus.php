<?php

namespace administrator\Schbas\Dashboard\Preparation;

require_once PATH_ADMIN . '/Schbas/Dashboard/Preparation/Preparation.php';

class SchbasClaimStatus
	extends \administrator\Schbas\Dashboard\Preparation\Preparation {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);
		$status = filter_input(
			INPUT_GET, 'newStatus', FILTER_VALIDATE_BOOLEAN,
			['flags' => FILTER_NULL_ON_FAILURE]
		);
		if($status !== Null) {
			$this->changeStatus($status);
		}
		else {
			dieHttp('Parameter fehlen', 400);
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function changeStatus($newStatus) {

	    $stmt = $this->_pdo->prepare("SELECT * FROM SystemGlobalSettings WHERE name = ?");
	    $stmt->execute(array('isSchbasClaimEnabled'));
	    $statusEntry = $stmt->fetch();

		if(!$statusEntry) {
			$this->_logger->logO('Could not find isSchbasClaimEnabled',
				['sev' => 'error']);
			dieHttp('Konnte Einstellung nicht finden', 500);
		}
		if($statusEntry['value'] != $newStatus) {
			$val = ($newStatus) ? 1 : 0;
			$stmt = $this->_pdo->prepare("UPDATE SystemGlobalSettings SET value = ? WHERE id = ?");
			$stmt->execute(array($val, $statusEntry['id']));
			die('Status wurde erfolgreich verändert');
		}
		else {
			die('Status hat gleichen Wert. Er wurde nicht verändert.');
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

}

?>