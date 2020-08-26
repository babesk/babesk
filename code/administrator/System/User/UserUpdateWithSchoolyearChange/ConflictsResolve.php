<?php

namespace administrator\System\User\UserUpdateWithSchoolyearChange;

require_once 'UserUpdateWithSchoolyearChange.php';

/**
 * Allows the user to resolve the conflicts
 **/
class ConflictsResolve extends \administrator\System\User\UserUpdateWithSchoolyearChange {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);
		if(isset($_POST['change'])) {
			$this->conflictsResolveByInput();
		}
		else if(isset($_POST['cancel'])) {
			//Now execute the SessionMenu-Module
			$mod = new \ModuleExecutionCommand('root/administrator/System/' .
				'User/UserUpdateWithSchoolyearChange/SessionMenu');
			$this->_dataContainer->getAcl()->moduleExecute(
				$mod, $this->_dataContainer
			);
		}
		else if(isset($_GET['search'])) {
			if(empty($_GET['username']) || empty($_GET['conflictType'])) {
				dieHttp('Parameter fehlen', 400);
			}
			$this->searchForUsernameInConflictsOfType(
				$_GET['username'], $_GET['conflictType']
			);
		}
		else {
			$this->conflictResolveFormDisplay();
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function entryPoint($dataContainer) {

		parent::entryPoint($dataContainer);
	}

	/**
	 * Fetches conflicts that are to resolve
	 * Dies displaying a message on error
	 * @return array  the conflicts
	 */
	private function conflictsToResolveGet() {

		try {
			$res = $this->_pdo->query(
				'SELECT tc.ID as conflictId, tc.tempUserId AS userId,
					tc.type AS type,
					IFNULL(u.forename, tu.forename) AS forename,
					IFNULL(u.name, tu.name) AS name,
					IFNULL(tu.birthday, u.birthday) AS birthday,
					CONCAT(g.gradelevel, "-", g.label) AS origGrade,
					CONCAT(tu.gradelevel, "-", tu.label) AS newGrade
				FROM UserUpdateTempConflicts tc
					LEFT JOIN UserUpdateTempUsers tu ON tu.ID = tc.tempUserId
					LEFT JOIN SystemUsers u ON u.ID = tc.origUserId
					LEFT JOIN SystemAttendances uigs
						ON u.ID = uigs.userId
						AND uigs.schoolyearId = @activeSchoolyear
					LEFT JOIN SystemGrades g ON uigs.gradeId = g.ID
				WHERE solved = 0
					ORDER BY type LIMIT 10'
			);
			$data = $res->fetchAll(\PDO::FETCH_ASSOC);
			return $data;

		} catch (\PDOException $e) {
			$this->_logger->log('Error fetching conflicts to resolve',
				'Notice', Null, json_encode(array('msg' => $e->getMessage())));
			$this->_interface->dieError(_g('Could not fetch the data!'));
		}
	}

	private function conflictResolveFormDisplay() {

		$this->_smarty->assign('conflicts', $this->conflictsToResolveGet());
		$this->displayTpl('conflictResolve.tpl');
	}

	/**
	 * Parses the input of the user to resolve the conflicts
	 */
	private function conflictsResolveByInput() {

		if(empty($_POST['conflict'])) {
			$this->_interface->backlink('administrator|System|User|UserUpdateWithSchoolyearChange|SessionMenu|ConflictsResolve');
			$this->_interface->dieError(_g('Please answer the questions given to resolve the conflicts.'));
		}

		$this->resolveSqlStatementsPrepare();
		$conflicts = $this->conflictDataAddToIdArray($_POST['conflict']);

		foreach($conflicts as $conflict) {
			if($conflict['isSolved']) {
				$this->_interface->backlink('administrator|System|User' .
					'|UserUpdateWithSchoolyearChange|SessionMenu');
				$this->_interface->dieError(
					'Mindestens ein Konflikt wurde bereits behoben! Bitte ' .
					'gehen sie zurück und starten sie nochmal die Konflikte' .
					'lösen Funktion.'
				);
			}
		}

		foreach($conflicts as $conflict) {
			$this->resolveByConflictType($conflict);
		}

		$this->conflictResolveFormDisplay();
	}

	/**
	 * Fetches the types of conflicts for the given ids
	 * Dies displaying a message on error
	 * @param  array  $ids '<id>' => [...]
	 * @return array       the conflicts '<id>' => ['type' => '<type>', ...]
	 */
	private function conflictTypesAddToByIdArray(array $ids) {

		try {
			$stmt = $this->_pdo->prepare(
				'SELECT type FROM UserUpdateTempConflicts WHERE ID = ?'
			);
			foreach($ids as $id => $stuff) {
				$stmt->execute(array($id));
				$res = $stmt->fetchColumn();
				$ids[$id]['type'] = $res;
			}
			$stmt->closeCursor();
			return $ids;

		} catch (\PDOException $e) {
			$this->_logger->log('Could not fetch the conflict types by array',
			'Notice', Null, json_encode(array('msg' => $e->getMessage())));
			$this->_interface->dieError(_g('Error fetching the data!'));
		}
	}

	/**
	 * Sets prepared Statements so that they can be used later on
	 * Dies displaying a message on error
	 */
	private function resolveSqlStatementsPrepare() {

		try {
			$conn = $this->_em->getConnection();
			$this->_userSolveStmt = $conn->prepare(
				'INSERT INTO UserUpdateTempSolvedUsers
					(origUserId, forename, name, newUsername, newTelephone,
						newEmail, gradelevel, gradelabel, birthday)
					VALUES (
						:origUserId, :forename, :name, :newUsername,
						:newTelephone, :newEmail, :gradelevel, :gradelabel,
						:birthday
					)'
			);
			$this->_conflictResolveStmt = $conn->prepare(
				'UPDATE UserUpdateTempConflicts SET solved = 1 WHERE ID = :id'
			);
			$this->_conflictDataStmt = $conn->prepare(
				'SELECT tc.ID as conflictId, tc.tempUserId AS tempUserId,
					tc.origUserId AS origUserId,
					tc.tempUserId AS tempUserId,
					IFNULL(tu.birthday, u.birthday) AS birthday,
					tu.newUsername AS newUsername,
					tu.newTelephone AS newTelephone,
					tu.newEmail AS newEmail,
					tc.type AS type,
					tc.solved AS isSolved,
					IFNULL(u.forename, tu.forename) AS forename,
					IFNULL(u.name, tu.name) AS name,
					g.gradelevel AS origGradelevel,
					g.label AS origGradelabel,
					tu.gradelevel AS newGradelevel,
					tu.label AS newGradelabel
				FROM UserUpdateTempConflicts tc
				LEFT JOIN UserUpdateTempUsers tu ON tu.ID = tc.tempUserId
				LEFT JOIN SystemUsers u ON u.ID = tc.origUserId
				LEFT JOIN SystemAttendances uigs
					ON u.ID = uigs.userId
					AND uigs.schoolyearId = @activeSchoolyear
				LEFT JOIN SystemGrades g ON uigs.gradeId = g.ID
				WHERE tc.ID = :id
			');

		} catch (\PDOException $e) {
			$this->_logger->log('Could not set the prepared statements',
				'Notice', Null, json_encode(array('msg' => $e->getMessage())));
			$this->_interface->dieError(_g('Could not upload the data!'));
		}
	}

	/**
	 * Checks what method needs to be executed for the conflict-type
	 * Dies displaying a message on wrong type
	 * @param  array  $conflict An array containing the conflict-data
	 */
	private function resolveByConflictType($conflict) {

		try {
			switch($conflict['type']) {
				case 'CsvOnlyConflict':
					$this->csvOnlyResolve($conflict);
					break;
				case 'DbOnlyConflict':
					$this->dbOnlyResolve($conflict);
					break;
				case 'GradelevelConflict':
					$this->gradelevelResolve($conflict);
					break;
				default:
					$this->_interface->dieError(_g('Wrong type?!'));
			}

		} catch (\PDOException $e) {
			$this->_logger->log('Error uploading the conflict resolve',
				'Notice', Null, json_encode(array('msg' => $e->getMessage())));
			$this->_interface->dieError(_g('Could not upload the data!'));
		}
	}

	/**
	 * Adds the data of the conflicts to the given id-Array
	 * @param  array  $ids '<conflictId>' => ['status' => '<status>']
	 * @return array       '<conflictId>' => [
	 *                         'status' => '<status>',
	 *                         'origUserId' => '<original UserId'>,
	 *                         ...
	 *                     ]
	 */
	private function conflictDataAddToIdArray(array $ids) {

		try {
			foreach($ids as $id => $stuff) {
				$this->_conflictDataStmt->execute(['id' => $id]);
				$res = $this->_conflictDataStmt->fetch(\PDO::FETCH_ASSOC);
				$ids[$id] = array_merge($ids[$id], $res);
			}

			$this->_conflictDataStmt->closeCursor();
			return $ids;

		} catch (\PDOException $e) {
			$this->_logger->log('Could not fetch the conflict-data by array',
			'Notice', Null, json_encode(array('msg' => $e->getMessage())));
			$this->_interface->dieError(_g('Error fetching the data!'));
		}
	}

	private function dbOnlyResolve($conflict) {

		if($conflict['status'] == 'confirmed') {
			//User is not in the new schoolyear
			$this->_conflictResolveStmt->execute(
				array(':id' => $conflict['conflictId'])
			);
		}
		else if($conflict['status'] == 'mergeConflicts') {
			$conflictPost = $_POST['conflict'][$conflict['conflictId']];
			$secondConflictId = $conflictPost['mergeSecondConflictId'];
			if($conflictPost['conflictDataUseSelect'] == 'alternative') {
				$useSecondConflictData = true;
			}
			else {
				$useSecondConflictData = false;
			}
			$this->mergeConflictsOfSameUser(
				$conflict, $secondConflictId, $useSecondConflictData
			);

		}
	}

	private function csvOnlyResolve($conflict) {

		$data = array(
			'origUserId' => 0,
			'forename' => $conflict['forename'],
			'name' => $conflict['name'],
			'newUsername' => $conflict['newUsername'],
			'newTelephone' => $conflict['newTelephone'],
			'newEmail' => $conflict['newEmail'],
			'gradelevel' => $conflict['newGradelevel'],
			'gradelabel' => $conflict['newGradelabel'],
			'birthday' => $conflict['birthday']
		);
		if(empty($conflict['birthday'])) {
			$conflict['birthday'] = NULL;
		}
		if($conflict['status'] == 'confirmed') {
			$this->_userSolveStmt->execute($data);
			$this->_conflictResolveStmt->execute(
				array(':id' => $conflict['conflictId'])
			);
		}
		else if($conflict['status'] == 'mergeConflicts') {
			$conflictPost = $_POST['conflict'][$conflict['conflictId']];
			$secondConflictId = $conflictPost['mergeSecondConflictId'];
			if($conflictPost['conflictDataUseSelect'] == 'alternative') {
				$useSecondConflictData = true;
			}
			else {
				$useSecondConflictData = false;
			}
			$this->mergeConflictsOfSameUser(
				$conflict, $secondConflictId, $useSecondConflictData
			);
		}
	}

	private function gradelevelResolve($conflict) {

		if($conflict['status'] == 'confirmed') {
			if(empty($conflict['birthday'])) {
				$conflict['birthday'] = NULL;
			}
			$data = array(
				'origUserId' => $conflict['origUserId'],
				'forename' => $conflict['forename'],
				'name' => $conflict['name'],
				'newUsername' => $conflict['newUsername'],
				'newTelephone' => $conflict['newTelephone'],
				'newEmail' => $conflict['newEmail'],
				'gradelevel' => $conflict['newGradelevel'],
				'gradelabel' => $conflict['newGradelabel'],
				'birthday' => $conflict['birthday']
			);
			$this->_userSolveStmt->execute($data);
			$this->_conflictResolveStmt->execute(
				array(':id' => $conflict['conflictId'])
			);
		}
		else if($conflict['status'] == 'correctedGrade') {
			$this->_interface->dieError(_g('Not implemented yet!'));
		}
	}

	/**
	 * Search for a username in all users with a specific conflict type
	 * It searches with the good ol' Levenshtein-method, so be easy on it.
	 * Rendering a 200 with json on success, a 204 if no users found or an
	 * error on error.
	 * @param  string $username The username to search for
	 * @param  string $type     The conflict-type. Has to be one of
	 *                          CsvOnlyConflict and DbOnlyConflict
	 */
	private function searchForUsernameInConflictsOfType($username, $type) {

		$limit = 30;
		if($type == 'CsvOnlyConflict') {
			$joinQuery = 'INNER JOIN UserUpdateTempUsers u ' .
				'ON u.ID = c.tempUserId';
		}
		else if($type == 'DbOnlyConflict') {
			$joinQuery = 'INNER JOIN SystemUsers u ON u.ID = c.origUserId';
		}
		else {
			$this->_logger->logO('Type not recognized', ['sev' => 'warning',
				['moreJson'] => ['type' => $type]]);
			$this->_interface->dieError('Ein Fehler ist aufgetreten');
		}
		$query = "SELECT c.ID as conflictId, u.birthday AS userBirthday,
					CONCAT(u.forename, ' ', u.name) AS username
				FROM UserUpdateTempConflicts c
				$joinQuery
				WHERE c.type = :conflictType
				ORDER BY LEVENSHTEIN_RATIO(username, :username) DESC, u.ID
				LIMIT :limit";
		try {
			$stmt = $this->_em->getConnection()->prepare($query);
			$stmt->bindParam('conflictType', $type);
			$stmt->bindParam('username', $username);
			$stmt->bindParam('limit', $limit, \PDO::PARAM_INT);
			$stmt->execute();
			$result = $stmt->fetchAll();
		}
		catch(\Exception $e) {
			$this->_logger->logO('Could not search usernames for conflicttype',
				['sev' => 'error', 'moreJson' => ['msg' => $e->getMessage(),
					'conflicttype' => $type]]);
			dieHttp('Fehler beim Suchen der ähnlichen Benutzer', 500);
		}
		if(count($result)) {
			$conflicts = [];
			foreach($result as $row) {
				$formattedBirthday = date(
					'd.m.Y', strtotime($row['userBirthday'])
				);
				$conflicts[] = [
					'id' => $row['conflictId'],
					'label' => $row['username'] . " ($formattedBirthday)"
				];
			}
			dieJson($conflicts);
		}
		else {
			dieHttp('Keine ähnlichen Benutzer gefunden.', 204);
		}
	}

	/**
	 * Merges and solves a DbOnlyConflict and a CsvOnlyConflict
	 * The conflicts origin from the same user, but one of the representations
	 * had outdated data / was misspelled, thus two conflicts were created.
	 * @param  array  $conflict              The conflict that was submitted
	 * @param  int    $secondConflictId      The other conflict that was
	 *                                       selected for merge
	 * @param  bool   $useSecondConflictData If the data of the first or second
	 *                                       conflict should be copied over to
	 *                                       the solved user
	 */
	private function mergeConflictsOfSameUser(
		$conflict, $secondConflictId, $useSecondConflictData
	) {
		try {
			$this->_conflictDataStmt->execute(['id' => $secondConflictId]);
			$secondConflict = $this->_conflictDataStmt->fetch();
			$this->_conflictDataStmt->closeCursor();
		} catch(\Exception $e) {
			$this->_logger->logO('Error fetching the second conflict',
				['sev' => 'error', 'moreJson' => $e->getMessage()]);
			$this->_interface->dieError('Ein interner Fehler ist aufgetreten');
		}
		$this->mergeConflictsOfSameUserCheckInput($conflict, $secondConflict);
		// The CsvOnlyConflict contains the data of the user to be updated
		if($conflict['type'] == 'CsvOnlyConflict') {
			$csvOnlyConflict = $conflict;
			$dbOnlyConflict = $secondConflict;
		}
		else if($secondConflict['type'] == 'CsvOnlyConflict') {
			$csvOnlyConflict = $secondConflict;
			$dbOnlyConflict = $conflict;
		}
		// Since the main data of the conflicts differ, we need to pick the
		// conflict with the correct data
		if(!$useSecondConflictData) {
			$conflictForData = $conflict;
		}
		else {
			$conflictForData = $secondConflict;
		}
		$data = [
			'origUserId' => $dbOnlyConflict['origUserId'],
			'forename' => $conflictForData['forename'],
			'name' => $conflictForData['name'],
			'newUsername' => $csvOnlyConflict['newUsername'],
			'newTelephone' => $csvOnlyConflict['newTelephone'],
			'newEmail' => $csvOnlyConflict['newEmail'],
			'gradelevel' => $csvOnlyConflict['newGradelevel'],
			'gradelabel' => $csvOnlyConflict['newGradelabel'],
			'birthday' => $conflictForData['birthday']
		];
		$this->_userSolveStmt->execute($data);
		$this->_conflictResolveStmt->execute(
			[':id' => $conflict['conflictId']]
		);
		$this->_conflictResolveStmt->execute(
			[':id' => $secondConflict['conflictId']]
		);
	}

	/**
	 * Be defensive with user-input for merging
	 */
	private function mergeConflictsOfSameUserCheckInput($conf1, $conf2) {

		if(!$conf2) {
			$this->_interface->dieError('Anderer Konflikt nicht gefunden');
		}
		if(!(
				$conf1['type'] === 'CsvOnlyConflict' &&
				$conf2['type'] === 'DbOnlyConflict'
			) && !(
				$conf1['type'] === 'DbOnlyConflict' &&
				$conf2['type'] === 'CsvOnlyConflict'
			)
		) {
			$this->_logger->logO('wrong conflict-types given to merge',
				['sev' => 'notice', 'moreJson' => [$conf1, $conf2]]);
			$this->_interface->dieError('Falsche Konflikttypen übergeben.');
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	private $_userSolveStmt;

	private $_conflictResolveStmt;

	private $_conflictDataStmt;

}

?>