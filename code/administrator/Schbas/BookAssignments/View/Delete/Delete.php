<?php

namespace administrator\Schbas\BookAssignments\View\Delete;

require_once PATH_ADMIN . '/Schbas/BookAssignments/View/View.php';

class Delete extends \administrator\Schbas\BookAssignments\View\View {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);
		$delEntity = filter_input(INPUT_GET, 'deleteEntity');
		$bookId = filter_input(INPUT_GET, 'bookId');
		$entityId = filter_input(INPUT_GET, 'entityId');
		$schoolyearId = filter_input(INPUT_GET, 'schoolyearId');
		if(
			$delEntity && $entityId && $bookId && $schoolyearId &&
			in_array($delEntity, $this->_validDeleteEntities)
		) {
			try {
				$count = $this->assignmentsDeleteFor(
					$delEntity, $bookId, $entityId, $schoolyearId
				);
				die("Es wurden $count Zuweisungen gelöscht.");
			}
			catch(\Exception $e) {
				$this->_logger->logO('Could not delete book-assignments',
					['sev' => 'error', 'moreJson' => ['entityId' => $entityId,
						'bookId' => $bookId, 'msg' => $e->getMessage()]]);
				dieHttp($e->getMessage(), 500);
			}
		}
		else {
			dieHttp('Parameter fehlen', 400);
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function entryPoint($dataContainer) {

		parent::entryPoint($dataContainer);
	}

	protected function assignmentsDeleteFor($delEntity, $bookId, $entityId, $schoolyearId) {

		// DQL does not support delete with joins, so select them first
		// and delete them after that
        $query = "DELETE FROM SchbasUsersShouldLendBooks WHERE bookId = :bid AND schoolyearId = :syID ";
		switch($delEntity) {
			case 'book':
				// We want to delete all assignments for the book, no filtering
				// necessary
                $stmt = $this->_pdo->prepare($query);
                $stmt->execute(array(
                    'bid' => $bookId,
                    'syID' => $schoolyearId
                ));
                return $stmt->rowCount();
				break;
			case 'gradelevel':
			    $query .= "AND userId IN (SELECT userId FROM SystemAttendances a JOIN SystemGrades g ON (a.gradeId=g.ID) WHERE g.gradelevel = :entity AND a.schoolyearId = :syID)";
				break;
			case 'grade':
                $query .= "AND userId IN (SELECT userId FROM SystemAttendances WHERE schoolyearId = :syID AND gradeId = :entity)";
				break;
			case 'user':
			    $query .= "AND userId = :entity";
				break;
		}
		$stmt = $this->_pdo->prepare($query);
		$stmt->execute(array(
		    'bid' => $bookId,
            'syID' => $schoolyearId,
            'entity' => $entityId
        ));
		return $stmt->rowCount();
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	protected $_validDeleteEntities = ['book', 'gradelevel', 'grade', 'user'];
}

?>