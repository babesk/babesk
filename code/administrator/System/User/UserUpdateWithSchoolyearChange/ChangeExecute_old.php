<?php

namespace administrator\System\User\UserUpdateWithSchoolyearChange;

require_once 'UserUpdateWithSchoolyearChange.php';

/**
 * Executes the changes made beforehand.
 */
class ChangeExecute extends \administrator\System\User\UserUpdateWithSchoolyearChange {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);
		$this->_pdo = $this->_em->getConnection();
		try {
			if($this->conflictsSolvedCheck()) {
				$this->_existingGrades = $this->gradesFetch();
				$this->_pdo->beginTransaction();
				$this->userChangesCommit();
				$this->usersNewCommit();
				// Dont switch the schoolyear automatically, the new schoolyear
				// could be created ahead of time
				// $this->schoolyearNewSwitchTo();
				$this->_pdo->commit();
				$this->_interface->backlink('administrator|System|User');
				$this->_interface->dieSuccess(_g(
					'The userdata were changed successfully.'
				));
			}
			else {
				$this->_interface->backlink(
					'administrator|System|User|UserUpdateWithSchoolyearChange'.
					'|SessionMenu'
				);
				$this->_interface->dieError(_g('Please resolve the ' .
					'conflicts before comitting the changes'));
			}

		} catch (Exception $e) {
			$this->_pdo->rollback();
			$this->_logger->log('Error executing UserUpdate-Changes',
				'Notice', Null, json_encode(array('msg' => $e->getMessage())));
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	/**
	 * Checks if conflicts exist that are not solved yet
	 * @return bool  true if all conflicts are solved
	 */
	private function conflictsSolvedCheck() {

		try {
			$res = $this->_pdo->query(
				'SELECT COUNT(*) FROM UserUpdateTempConflicts WHERE solved = 0
			');
			return $res->fetchColumn() == 0;

		} catch (\PDOException $e) {
			$this->_logger->log('Error checking for not solved conflicts',
				'Notice', Null, json_encode(array('msg' => $e->getMessage())));
			$this->_interface->dieError(_g('Could not upload the data!'));
		}
	}

	/**
	 * Commits the changes to the users to the real users-table
	 * Dies displaying a message on error
	 */
	private function userChangesCommit() {

		$this->usersToChangeCheckGrades();

		try {
			$queryJoints = 'INSERT INTO SystemAttendances (
					userId, gradeId, schoolyearId
				) SELECT su.origUserId, g.ID,
					(SELECT value FROM SystemGlobalSettings
						WHERE name =
							"userUpdateWithSchoolyearChangeNewSchoolyearId"
					) AS schoolyear
				FROM UserUpdateTempSolvedUsers su
					LEFT JOIN SystemGrades g ON g.gradelevel = su.gradelevel AND
						g.label = su.gradelabel
					WHERE su.origUserId <> 0
			';
			//Update user-entries if data is given
			$queryUsers = 'UPDATE SystemUsers u
				LEFT JOIN UserUpdateTempSolvedUsers su ON u.ID = su.origUserId
				SET
					u.forename = IFNULL(su.forename, u.forename),
					u.name = IFNULL(su.name, u.name),
					u.birthday = IFNULL(su.birthday, u.birthday),
					u.email = IFNULL(su.newEmail, u.email),
					u.telephone = IFNULL(su.newTelephone, u.telephone),
					u.username = IFNULL(su.newUsername, u.username),
					u.religion = IFNULL(su.religion, u.religion),
					u.foreign_language = IFNULL(
						su.foreign_language, u.foreign_language
					),
					u.special_course = IFNULL(
						su.special_course, u.special_course
					)
			';

			$this->_pdo->query($queryJoints);
			$this->_pdo->query($queryUsers);

		} catch (\PDOException $e) {
			$this->_pdo->rollback();
			$this->_logger->log('Could not commit the user Changes',
				'Notice', Null, json_encode(array('msg' => $e->getMessage())));
			$this->_interface->dieError(_g(
				'Could not upload the userchanges!')
			);
		}
	}

	/**
	 * Checks if new grades should be added and adds them
	 * Adds the missing grades so that when the users get changed, the new
	 * grades exist and can be assigned to their users
	 */
	private function usersToChangeCheckGrades() {

		$grades = $this->usersToChangeGradeIdsCommitFetch();

		foreach($grades as $grade) {
			$existingGradeId = array_search(
				$grade['gradelevel'] . $grade['gradelabel'],
				$this->_existingGrades
			);
			if($existingGradeId === FALSE) {
				$this->gradeAdd($grade['gradelevel'], $grade['gradelabel']);
				$this->_existingGrades[$grade['gradeId']] =
					$grade['gradelevel'] . $grade['gradelabel'];
			}
		}
	}

	/**
	 * Fetches the grades already existing in the Database
	 * @return array '<gradeId>' => '<gradelevel.gradelabel>'
	 */
	private function gradesFetch() {

		try {
			$stmt = $this->_pdo->query(
				'SELECT ID, CONCAT(gradelevel, label) AS name
				FROM SystemGrades WHERE 1'
			);
			return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

		} catch (\PDOException $e) {
			$this->_logger->log('Could not fetch the grades',
				'Notice', Null, json_encode(array('msg' => $e->getMessage())));
			$this->_interface->dieError(_g('Could not fetch the grades!'));
		}
	}

	/**
	 * Adds the users that are new to the database (were in csv but not in db)
	 * Dies displaying a message on error.
	 */
	private function usersNewCommit() {

		try {
			$users = $this->usersNewToCommitFetch();
			$groupId = $this->groupIdToAddNewUsersToGet();

			if(empty($users) || !count($users)) {
				return;
			}

			$stmtu = $this->_pdo->prepare(
				'INSERT INTO SystemUsers
					(forename, name, username, password, email, telephone,
						last_login, locked, GID, credit, soli, birthday,
						religion, foreign_language, special_course)
				VALUES (?, ?, IFNULL(?, CONCAT(forename, ".", name)), "",
					IFNULL(?, ""), IFNULL(?, ""), "", 0, 0, 0, 0, ?,
					IFNULL(?, ""), IFNULL(?, ""), IFNULL(?, ""))'
			);
			$stmtg = $this->_pdo->prepare(
				'INSERT INTO SystemAttendances (
					userId, gradeId, schoolyearId
				) VALUES (? ,?, (SELECT value FROM SystemGlobalSettings
					WHERE name =
						"userUpdateWithSchoolyearChangeNewSchoolyearId"
				))'
			);
			$stmtgroups = $this->_pdo->prepare(
				'INSERT INTO SystemUsersInGroups (userId, groupId) VALUES
				(?, ?)'
			);

			$newUserIds = [];
			foreach($users as $user) {
				if(empty($user['birthday'])) {
					$user['birthday'] = '';
				}
				$user = $this->userNewGradeCheckAndAdd($user);
				$stmtu->execute(array(
					$user['forename'], $user['name'], $user['newUsername'],
					$user['newEmail'], $user['newTelephone'],
					$user['birthday'], $user['religion'],
					$user['foreign_language'], $user['special_course']
				));
				$userId = $this->_pdo->lastInsertId();
				$newUserIds[] = $userId;
				$stmtg->execute(array($userId, $user['gradeId']));
				if($groupId != 0) {
					$stmtgroups->execute(array($userId, $groupId));
				}
			}
			$schbasAssignmentsEntry = $this->_em
				->getRepository('DM:SystemGlobalSettings')
				->findOneByName(
					'userUpdateWithSchoolyearChangeSchbasAssignmentsGenerate'
				);
			if(!$schbasAssignmentsEntry) {
				$this->_logger->logO('schbas assignments entry not found', [
					'sev' => 'warning']);
			}
			else {
				if($schbasAssignmentsEntry->getValue()) {
					$this->schbasAssignmentsGenerate($newUserIds);
				}
			}

		} catch (\PDOException $e) {
			$this->_logger->log('Could not commit the new users',
				'Notice', Null, json_encode(array('msg' => $e->getMessage())));
			$this->_interface->dieError(_g('Could not commit the new users!' . $e->getMessage()));
		}
	}

	/**
	 * Fetches the id of the group to assign the newly added users to
	 * @return int    The groupId
	 */
	private function groupIdToAddNewUsersToGet() {

		try {
			$res = $this->_pdo->query(
				'SELECT value FROM SystemGlobalSettings WHERE
					name = "UserUpdateWithSchoolyearChangeGroupOfNewUser"'
			);
			$data = $res->fetchColumn();
			if(isset($data) && $data !== FALSE) {
				return (int)$data;
			}
			else {
				throw new \Exception('globalSettings-Entry not existing!');
			}

		} catch (\PDOException $e) {
			$this->_logger->log('Error fetching the group-id for the newly ' .
				'imported users','Notice', Null,
				json_encode(array('msg' => $e->getMessage())));
			$this->_interface->dieError(_g('Could not get the group for the ' .
				'new users!'));
		}
	}

	private function schbasAssignmentsGenerate($userIds) {

		require_once PATH_INCLUDE . '/Schbas/ShouldLendGeneration.php';
		$syId = $this->_em->getRepository('DM:SystemGlobalSettings')
			->getSetting('userUpdateWithSchoolyearChangeNewSchoolyearId');
		$schoolyear = $this->_em->getReference('DM:SystemSchoolyears', $syId);
		$generator = new \Babesk\Schbas\ShouldLendGeneration(
			$this->_dataContainer
		);
		$users = array_map(function($userId) {
			return $this->_em->getReference('DM:SystemUsers', $userId);
		}, $userIds);
		if(count($users)) {
			$generator->generate(
				['onlyForUsers' => $users, 'schoolyear' => $schoolyear]
			);
		}
	}

	/**
	 * Checks if any grades should be added and adds them
	 */
	private function userNewGradeCheckAndAdd($user) {

		if(empty($user['gradeId'])) {
			//Check if the new Grade has already been added by another
			//userentry
			$existingGradeId = array_search(
				$user['gradelevel'] . $user['gradelabel'],
				$this->_existingGrades
			);
			if($existingGradeId === FALSE) {
				$gradeId = $this->gradeAdd(
					$user['gradelevel'], $user['gradelabel']
				);
				$user['gradeId'] = $gradeId;
				$this->_existingGrades[$gradeId] =
					$user['gradelevel'] . $user['gradelabel'];
			}
			else {
				$user['gradeId'] = $existingGradeId;
			}
		}
		return $user;
	}

	/**
	 * Adds a grade and returns its new Id
	 * Dies displaying a message on error
	 * @param  int    $level The gradelevel
	 * @param  string $label The gradelabel
	 * @return int           The Id of the new grade
	 */
	private function gradeAdd($level, $label) {

		try {
			if(empty($this->_gradeStmt)) {
				$this->_gradeStmt = $this->_pdo->prepare(
					'INSERT INTO SystemGrades (label, gradelevel, schooltypeId) VALUES (?,?, 0)'
				);
			}
			$this->_gradeStmt->execute(array($label, $level));
			return $this->_pdo->lastInsertId();

		} catch (\PDOException $e) {
			$this->_logger->log('Error adding the grade', 'Notice', Null,
				json_encode(array(
					'msg' => $e->getMessage(),
					'level' => $level,
					'label' => $label))
			);
			$this->_interface->dieError(_g('Could not add the grade!') . $e->getMessage());
		}
	}

	/**
	 * Fetches the users that will be added when comitting
	 * @return array  the users to add
	 */
	private function usersNewToCommitFetch() {

		try {
			$res = $this->_pdo->query('SELECT su.*, g.ID AS gradeId
				FROM UserUpdateTempSolvedUsers su
				LEFT JOIN SystemGrades g ON su.gradelevel = g.gradelevel AND
					su.gradelabel = g.label
				WHERE su.origUserId = 0');
			$users = $res->fetchAll(\PDO::FETCH_ASSOC);

			return $users;

		} catch (\PDOException $e) {
			$this->_logger->log('Error fetching the new users to commit',
				'Notice', Null, json_encode(array('msg' => $e->getMessage())));
			$this->_interface->dieError(_g('Could not fetch the new users!'));
		}
	}

	/**
	 * Fetches the users that will be changed when comitting
	 * @return array  '<index>' => [userId, gradeStuff]
	 */
	private function usersToChangeGradeIdsCommitFetch() {

		try {
			$res = $this->_pdo->query('SELECT su.ID, g.ID AS gradeId,
					su.gradelevel AS gradelevel, su.gradelabel AS gradelabel
				FROM UserUpdateTempSolvedUsers su
				LEFT JOIN SystemGrades g ON su.gradelevel = g.gradelevel AND
					su.gradelabel = g.label
				WHERE su.origUserId <> 0');
			$users = $res->fetchAll(\PDO::FETCH_ASSOC);

			return $users;

		} catch (\PDOException $e) {
			$this->_logger->log('Error fetching the new users to commit',
				'Notice', Null, json_encode(array('msg' => $e->getMessage())));
			$this->_interface->dieError(_g('Could not fetch the new users!'));
		}
	}

	/**
	 * Switches to the new schoolyear
	 * Dies displaying a message on error
	 */
	private function schoolyearNewSwitchTo() {

		try {
			$this->_pdo->query(
				'UPDATE SystemSchoolyears SET active = 0 WHERE active = 1;
				UPDATE SystemSchoolyears SET active = 1 WHERE ID = (
					SELECT value FROM SystemGlobalSettings
						WHERE name =
							"userUpdateWithSchoolyearChangeNewSchoolyearId")'
			);

		} catch (\PDOException $e) {
			$this->_logger->log('Could not switch to the new Schoolyear!',
				'Notice', Null, json_encode(array('msg' => $e->getMessage())));
			$this->_interface->dieError(_g('Could not switch to the new Schoolyear!'));
		}
	}



	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	/**
	 * Use a prepared statement, so if multiple grades are added, its faster
	 * @var PDOStatement
	 */
	private $_gradeStmt;

	private $_existingGrades;
}

?>