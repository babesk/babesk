<?php

namespace administrator\Schbas\Dashboard\Preparation;

require_once PATH_ADMIN . '/Schbas/Dashboard/Dashboard.php';

class Preparation extends \administrator\Schbas\Dashboard\Dashboard {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);
		$data = $this->fetchData();
		dieJson($data);
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function fetchData() {

		$prepSchoolyearData = $this->fetchPreparationSchoolyearData();
		$schbasClaimStatus = $this->fetchSchbasClaimStatus();
		return [
			'prepSchoolyear' => $prepSchoolyearData,
			'schbasClaimStatus' => $schbasClaimStatus,
			'deadlines' => $this->fetchDeadlines()
		];
	}

	protected function fetchPreparationSchoolyearData() {

	    $stmt = $this->_pdo->prepare("SELECT * FROM SystemGlobalSettings WHERE name = ?");
	    $stmt->execute(array('schbasPreparationSchoolyearId'));
	    $preparationSchoolyearId = $stmt->fetch()['value'];
		$stmt = $this->_pdo->prepare(
			'SELECT sy.ID AS id, sy.label AS label,
				COUNT(usb.id) as assignmentCount
			FROM SystemSchoolyears sy
			LEFT JOIN SchbasUsersShouldLendBooks usb
				ON sy.ID = usb.schoolyearId
			GROUP BY sy.ID
		');
		$stmt->execute();
		$schoolyears = $stmt->fetchAll();
		$prepData = array();
		foreach($schoolyears as $schoolyear) {
			if($schoolyear['id'] == $preparationSchoolyearId) {
				$prepData['active'] = [
					'id' => $schoolyear['id'],
					'name' => $schoolyear['label'],
					'entriesExist' => ($schoolyear['assignmentCount'] > 0)
				];
			}
			else {
				$prepData['alternatives'][] = [
					'id' => $schoolyear['id'],
					'name' => $schoolyear['label'],
					'entriesExist' => ($schoolyear['assignmentCount'] > 0)
				];
			}
		}
		return $prepData;
	}

	protected function fetchSchbasClaimStatus() {

        $stmt = $this->_pdo->prepare("SELECT * FROM SystemGlobalSettings WHERE name = ?");
        $stmt->execute(array('isSchbasClaimEnabled'));
        $status = $stmt->fetch()['value'];
		return $status != 0;
	}

	protected function fetchDeadlines() {

        $stmt = $this->_pdo->prepare("SELECT * FROM SystemGlobalSettings WHERE name = ?");

        $stmt->execute(array('schbasDeadlineClaim'));
        $claim = $stmt->fetch()['value'];

        $stmt->execute(array('schbasDeadlineTransfer'));
        $trans = $stmt->fetch()['value'];


		// Format to ISO-time
		$claim = date('Y-m-d', strtotime($claim));
		$trans = date('Y-m-d', strtotime($trans));
		return [
			'schbasDeadlineClaim' => $claim,
			'schbasDeadlineTransfer' => $trans
		];
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

}

?>