<?php

namespace administrator\Schbas\BookAssignments\View;

require_once PATH_ADMIN . '/Schbas/BookAssignments/BookAssignments.php';

class View extends \administrator\Schbas\BookAssignments\BookAssignments {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);
		$schoolyearId = filter_input(INPUT_GET, 'schoolyearId');
		if(isset($_GET['jsonData'])) {
			$this->bookDataSend($schoolyearId);
		}
		else {
			$this->displayTpl('main.tpl');
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function entryPoint($dataContainer) {

		parent::entryPoint($dataContainer);
		$this->moduleTemplatePathSet();
	}

	protected function bookDataSend($schoolyearId) {

		try {
			$schoolyears = $this->schoolyearDataGet($schoolyearId);
		}
		catch(Exception $e) {
			$this->_logger->logO('Could not fetch the schoolyears', [
				'sev' => 'error', 'moreJson' => $e->getMessage()]);
			dieHttp('Konnte die Schuljahre nicht abrufen', 500);
		}
		try {
			if(!$schoolyearId) {
				foreach($schoolyears as $schoolyear) {
					if($schoolyear['active']) {
						$schoolyearId = $schoolyear['id'];
					}
				}
			}
			$books = $this->bookDataGet($schoolyearId);
		}
		catch(Exception $e) {
			$this->_logger->logO('Could not fetch the books', [
				'sev' => 'error', 'moreJson' => $e->getMessage()]);
			dieHttp('Konnte die Buchzuweisungen nicht abrufen', 500);
		}
		dieJson([
			'schoolyears' => $schoolyears,
			'books' => $books
		]);
	}

	protected function schoolyearDataGet($schoolyearId) {

		$stmt = $this->_pdo->query("SELECT * FROM SystemSchoolyears");
		$schoolyears = $stmt->fetchAll();

		$stmt = $this->_pdo->query("SELECT * FROM SystemGlobalSettings WHERE name = 'schbasPreparationSchoolyearId'");
		$syPrepEntry = $stmt->fetch();

		$jsonData = [];
		foreach($schoolyears as $schoolyear) {
			// The schoolyear to be first displayed is the schoolyear thats
			// active in schbasPreparationSchoolyear if no schoolyearId is
			// given
			$isActive = (
				($schoolyearId && $schoolyear['ID'] == $schoolyearId) ||
				(
					!$schoolyearId &&
					$schoolyear['ID'] == $syPrepEntry['value']
				)
			);
			$jsonData[] = [
				'id' => $schoolyear['ID'],
				'name' => $schoolyear['label'],
				'active' => $isActive
			];
		}
		return $jsonData;
	}

	protected function bookDataGet($schoolyearId) {

		$stmt = $this->_pdo->prepare(
			'SELECT b.id AS bookId, b.title AS bookName, g.ID AS gradeId,
				g.label AS gradeLabel, g.gradelevel AS gradelevel,
				COUNT(a.id) AS userCount
			FROM SchbasUsersShouldLendBooks usb
			INNER JOIN SchbasBooks b ON b.id = usb.bookId
			-- We dont need any user-data, so directly fetch attendances
			INNER JOIN SystemAttendances a ON a.userId = usb.userId
			INNER JOIN SystemSchoolyears sy ON sy.ID = a.schoolyearId
				AND sy.ID = :schoolyearId AND sy.ID = usb.schoolyearId
			INNER JOIN SystemGrades g ON g.ID = a.gradeId
			WHERE usb.schoolyearId = :schoolyearId
			GROUP BY b.id, g.ID
			ORDER BY b.subjectId, g.gradelevel
		');
		$stmt->execute(['schoolyearId' => $schoolyearId]);
		$data = $stmt->fetchAll();
		$sort = [];
		// Pre-sort the data so that it will be easier to put it in its final
		// data-format
		foreach($data as $row) {
			$bookId = $row['bookId'];
			$gradelevel = $row['gradelevel'];
			$gradeId = $row['gradeId'];
			if(!isset($sort[$bookId])) {
				// Create new book-entry by row
				$sort[$bookId] = [
					'id' => $bookId,
					'name' => $row['bookName'],
					'gradelevels' => [
						$gradelevel => [
							'level' => $gradelevel,
							'grades' => [
								$gradeId => [
									'id' => $gradeId,
									'label' => $row['gradeLabel'],
									'usersAssigned' => $row['userCount']
								]
							]
						]
					]
				];
			}
			else if(!isset($sort[$bookId]['gradelevels'][$gradelevel])) {
				// Book entry exists, add the gradelevel by row
				$sort[$bookId]['gradelevels'][$gradelevel] = [
					'level' => $gradelevel,
					'grades' => [
						$gradeId => [
							'id' => $gradeId,
							'label' => $row['gradeLabel'],
							'usersAssigned' => $row['userCount']
						]
					]
				];
			}
			else {
				// Book & Gradelevel entry exists, add the grade by row
				$sort[$bookId]['gradelevels'][$gradelevel]['grades']
					[$gradeId] = [
					'id' => $gradeId,
					'label' => $row['gradeLabel'],
					'usersAssigned' => $row['userCount']
				];
			}
		}
		// Remove the index-key from gradelevels and grades so that lists
		// instead of objects get written into the json
		foreach($sort as &$book) {
			foreach($book['gradelevels'] as &$gradelevel) {
				$gradelevel['grades'] = array_values($gradelevel['grades']);
			}
			$book['gradelevels'] = array_values($book['gradelevels']);
		}
		$sort = array_values($sort);
		return $sort;
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

}

?>