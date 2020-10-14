<?php

namespace administrator\Schbas\BookAssignments\Delete;

require_once PATH_ADMIN . '/Schbas/BookAssignments/BookAssignments.php';

class Delete extends \administrator\Schbas\BookAssignments\BookAssignments {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);
		$schoolyearId = filter_input(INPUT_GET, 'schoolyearId');
		$bookAssignmentId = filter_input(INPUT_POST, 'bookAssignmentId');
		if($schoolyearId) {
			$this->deleteAssignmentsOfSchoolyear($schoolyearId);
		}
		else if($bookAssignmentId) {
			$this->deleteSingleAssignment($bookAssignmentId);
		}
		else {
			dieHttp('Fehlende Parameter', 400);
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function deleteAssignmentsOfSchoolyear($schoolyear) {

		try {
			$query = $this->_pdo->prepare('DELETE FROM SchbasUsersShouldLendBooks WHERE schoolyearId = ?');
			$query->execute(array($schoolyear));
			die('Die Buchzuweisungen wurden erfolgreich gelöscht');
		}
		catch(Exception $e) {
			$this->_logger->logO('Could not delete assignments of schoolyear',
				['sev' => 'error', 'moreJson' => ['msg' => $e->getMessage(),
					'id' => $schoolyear]]);
			dieHttp('Konnte die Buchzuweisungen nicht löschen', 500);
		}
	}

	protected function deleteSingleAssignment($bookAssignmentId) {

		try {
		    $query = $this->_pdo->prepare("DELETE FROM SchbasUsersShouldLendBooks WHERE id = ?");
		    $query->execute(array($bookAssignmentId));
			die('Buchzuweisung erfolgreich gelöscht.');

		} catch(\Exception $e) {
			$this->_logger->logO('Could not delete a single book-assignment',
				['sev' => 'error', 'moreJson' => $e->getMessage()]);
			dieHttp('Konnte die Buchzuweisung nicht löschen', 500);
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

}

?>