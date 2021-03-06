<?php

namespace administrator\System\Grades\Search;

require_once PATH_ADMIN . '/System/Grades/Grades.php';

class Search extends \administrator\System\Grades\Grades {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);
		$gradename = filter_input(INPUT_GET, 'gradename');
		if($gradename) {
			dieJson($this->searchByGradename($gradename, 20));
		}
		else {
			dieHttp('Such-parameter fehlt', 400);
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function searchByGradename($gradename, $entryCount) {

		try {
			$query = $this->_em->createQuery(
				'SELECT g FROM DM:SystemGrades g
				WHERE CONCAT(g.gradelevel, g.label) LIKE :gradename
			');
			$query->setParameter('gradename', "%$gradename%");
			$query->setMaxResults($entryCount);
			$grades = $query->getResult();
			$gradeArray = [];
			if(count($grades)) {
				foreach($grades as $grade) {
					$gradeArray[] = [
						'id' => $grade->getId(),
						'gradename' => $grade->getGradelevel() .
							$grade->getLabel()
					];
				}
			}
			return $gradeArray;
		}
		catch(\Exception $e) {
			$this->_logger->logO('Could not search the grades by gradename ', [
				'sev' => 'error', 'moreJson' => ['gradename' => $gradename,
				'msg' => $e->getMessage()]]);
			dieHttp('Konnte nicht nach der Klasse suchen', 500);
		}
	}


	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////
}
?>