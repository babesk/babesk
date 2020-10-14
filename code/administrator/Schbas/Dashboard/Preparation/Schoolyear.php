<?php

namespace administrator\Schbas\Dashboard\Preparation;

require_once PATH_ADMIN . '/Schbas/Dashboard/Preparation/Preparation.php';

/**
 * Handles the ajax-requests for the SchbasPreparationSchoolyear
 */
class Schoolyear
	extends \administrator\Schbas\Dashboard\Preparation\Preparation {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);

		if(!isset($_GET['schoolyearId']) || !isset($_GET['action'])) {
			dieHttp('Parameter fehlen', 400);
		}

		switch($_GET['action']) {
			case 'change':
				$this->preparationSchoolyearChange($_GET['schoolyearId']);
			default:
				dieHttp('Unbekannte Action-value', 400);
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function preparationSchoolyearChange($id) {
	    $stmt = $this->_pdo->prepare("SELECT * FROM SystemSchoolyears WHERE ID = ?");
	    $stmt->execute(array($id));
	    $schoolyear = $stmt->fetch();

		if(!$schoolyear) {
			$this->_logger->log('Could not find the schoolyear',
				['sev' => 'error', 'moreJson' => ['id' => $id]]);
			dieHttp('Das Schuljahr wurde nicht gefunden', 422);
		}

		$stmt = $this->_pdo->prepare("UPDATE SystemGlobalSettings SET value = ? WHERE name = ?");
		$stmt->execute(array($schoolyear['ID'], 'schbasPreparationSchoolyearId'));
		die('Schuljahr erfolgreich verändert.');
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

}

?>