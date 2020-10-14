<?php

namespace administrator\Schbas\BookAssignments\Generate;

require_once PATH_ADMIN .  '/Schbas/BookAssignments/BookAssignments.php';
require_once PATH_INCLUDE . '/Schbas/Loan.php';
require_once PATH_INCLUDE . '/Schbas/ShouldLendGeneration.php';

class Generate extends \administrator\Schbas\BookAssignments\BookAssignments {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);
		if(isset($_GET['overview-infos'])) {
			$this->overviewInfosSend();
		}
		else if(isset($_POST['userId'])) {
			$userId = filter_input(INPUT_POST, 'userId');
			$this->assignmentsForSingleUserCreate($userId);
		}
		else if(isset($_POST['data'])) {
			$this->assignmentsCreate($_POST['data']);
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
		parent::moduleTemplatePathSet();
	}


	/*==========  Overview-infos  ==========*/

	/**
	 * Sends infos about the automatic assignments to the client
	 * Dies with a json-string
	 * {
	 *     'schoolyear': '<schoolyearName>',
	 *     'assignmentsForSchoolyearExist': <boolean>,
	 *     'bookAssignmentsForGrades': [
	 *         [ { 'gradelevel': '<gradelevel>',
	 *             'books':      [{'id': '<bookId>', 'name': '<bookName>'}]
	 *         } ]
	 *     ]
	 * }
	 */
	protected function overviewInfosSend() {

		$loanBookMan = new \Babesk\Schbas\Loan($this->_dataContainer);
		$schoolyearID = $loanBookMan->schbasPreparationSchoolyearGet();
		$stmt = $this->_pdo->prepare("SELECT * FROM SystemSchoolyears WHERE ID = ?");
		$stmt->execute(array($schoolyearID));
		$schoolyear = $stmt->fetch();
		$assignmentsExist = $this->assignmentsForSchoolyearExistCheck($schoolyearID		);
		$booksInGradelevels = $this->booksAssignedToGradelevelsGet();
		die(json_encode(array(
			'schoolyear' => $schoolyear['label'],
			'assignmentsForSchoolyearExist' => $assignmentsExist,
			'bookAssignmentsForGrades' => $booksInGradelevels
		)));
	}

	/**
	 * Checks if assignments for a schoolyear exist
	 * @param  DM:Object $schoolyear The schoolyear-entry to check for
	 * @return bool                  true if assignments exist, else false
	 */
	protected function assignmentsForSchoolyearExistCheck($schoolyear) {

		try {
		    $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM SchbasUsersShouldLendBooks WHERE schoolyearId = ?");
		    $stmt->execute(array($schoolyear));
		    $res = $stmt->fetch()[0];
			return (int)$res > 0;

		} catch(\Exception $e) {
			$this->_logger->logO('Could not check for existing assignments ' .
				'in a schoolyear', ['sev' => 'error',
					'moreJson' => ['syId' => $schoolyear->getId()]
			]);
			return false;
		}
	}

	/**
	 * Fetches which books are assigned to which gradelevels
	 * @return array of gradelevels that contain the books
	 */
	protected function booksAssignedToGradelevelsGet() {

		$loanBookMan = new \Babesk\Schbas\Loan($this->_dataContainer);
		$books = $loanBookMan->booksAssignedToGradelevelsGet();
		// Sort the array by Gradelevel
		// { '<gradelevel>': [{'id': '<bookId>', 'name': '<bookName>'}]}
		$booksInGradelevels = array();
		foreach($books as $data) {
			$book = $data['book'];
			$gradelevels = $data['gradelevels'];
			foreach($gradelevels as $gradelevel) {
				$booksInGradelevels[$gradelevel][] = array(
					'id' => $book['id'],
					'name' => $book['title']
				);
			}
		}
		// [ { 'gradelevel': '<gradelevel>',
		//     'books':      [{'id': '<bookId>', 'name': '<bookName>'}]
		// } ]
		$booksInGradelevelsWithoutKeys = array();
		foreach($booksInGradelevels as $gradelevel => $books) {
			$booksInGradelevelsWithoutKeys[] = array(
				'gradelevel' => $gradelevel,
				'books' => $books
			);
		}
		return $booksInGradelevelsWithoutKeys;
	}


	/*==========  Automatic Assignments  ==========*/


	protected function assignmentsForSingleUserCreate($userId) {

		$user = $this->_em->find('DM:SystemUsers', $userId);
		if(!$user) { dieJson('Benutzer nicht gefunden', 400); }
		$loanHelper = new \Babesk\Schbas\Loan($this->_dataContainer);
		$loanGenerator = new \Babesk\Schbas\ShouldLendGeneration(
			$this->_dataContainer
		);
		$schoolyear = $loanHelper->schbasPreparationSchoolyearGet();
		$bookAssignments = $this->_em
			->getRepository('DM:SchbasUserShouldLendBook')
			->findBy(['user' => $user, 'schoolyear' => $schoolyear]);
		if(count($bookAssignments)) {
			foreach($bookAssignments as $bookAssignment) {
				$this->_em->remove($bookAssignment);
			}
			$this->_em->flush();
		}
		$res = $loanGenerator->generate(
			['onlyForUsers' => [$user], 'schoolyear' => $schoolyear]
		);
		if($res) {
			die('Die Zuweisungen wurden erfolgreich erstellt.');
		}
		else {
			$this->_logger->log('Could not create the assignments', 'error');
			dieHttp('Konnte die Zuweisungen nicht erstellen!', 500);
		}
	}

	/**
	 * Automatically create the assignments.
	 * Dies with json.
	 *
	 * @param  array  $data An array containing options:
	 *                      {
	 *                          'existingAssignmentsAction': '<action>',
	 *                          'addGradelevelToUsers': '<gradelevelIncrease?>'
	 *                      }
	 */
	protected function assignmentsCreate($data) {

		if(
			empty($data) || !isset($data['existingAssignmentsAction'])
		) {
			$this->_logger->logO('missing parameters for ' . __METHOD__,
				['sev' => 'warning']);
		}

		$loanBookMan = new \Babesk\Schbas\Loan($this->_dataContainer);
		$loanGenerator = new \Babesk\Schbas\ShouldLendGeneration(
			$this->_dataContainer
		);
		$sy = $loanBookMan->schbasPreparationSchoolyearGet();
		$assignmentsExist = $this->assignmentsForSchoolyearExistCheck($sy);
		if(
			$assignmentsExist &&
			$data['existingAssignmentsAction'] == 'delete-existing'
		) {
			$this->deleteExistingAssignmentsForSchoolyear($sy);
		}
		$res = $loanGenerator->generate();
		if($res) {
			dieJson('Die Zuweisungen wurden erfolgreich erstellt.');
		}
		else {
			$this->_logger->log('Could not create the assignments', 'error');
			http_response_code(500);
			dieJson('Konnte die Zuweisungen nicht erstellen!');
		}
	}

	/**
	 * Delete all existing assignments with the schoolyear.
	 * @param  Object $schoolyear The schoolyear in which the assignments to
	 *                            delete are.
	 */
	protected function deleteExistingAssignmentsForSchoolyear($schoolyear) {

		try {
		    $stmt = $this->_pdo->prepare("DELETE FROM SchbasUsersShouldLendBooks WHERE schoolyearId = ?");
		    $stmt->execute(array($schoolyear));

		} catch(Exception $e) {
			$this->_logger->logO('Could not delete existing assignments for ' .
				'a schoolyear', ['sev' => 'error',
					'moreJson' => ['msg' => $e->getMessage()]]);
			http_response_code(500);
			die('Konnte die existierenden Zuweisungen nicht löschen.');
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

}

?>