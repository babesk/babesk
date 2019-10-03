<?php

namespace administrator\System\User;

require_once 'User.php';

/**
 * Changes the userdata based on input by the admin
 */
class Change extends \User
{
	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);
		$this->_userId = $_POST['ID'];

		$this->preInputCheck();
		$this->inputCheck();
		$this->inputParse();
		$this->upload();

		die(json_encode(array(
			'value' => 'success',
			'message' => "Der Benutzer mit der ID '{$this->_userId}' wurde " .
			'erfolgreich geändert'
		)));
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	/**
	 * Executed before the input-check
	 */
	protected function preInputCheck() {

		//Add Password to the Inputcheck if user wants it to be changed
		if($_POST['passwordChange'] == 'true') {
			$this->_changeRules['password'] = array('min_len,3|max_len,64', '', 'Passwort');
		}
	}

	/**
	 * Validates the input of the admin
	 */
	protected function inputCheck() {

		require_once PATH_INCLUDE . '/gump.php';

		$gump = new \GUMP();

		try {
			$gump->rules($this->_changeRules);

			//Set none-filled-out formelements to be at least a void string,
			//for easier processing
			// $_POST = $gump->voidVarsToStringByRuleset(
			// 	$_POST, self::$registerRules);

			//validate the elements
			if(!$gump->run($_POST)) {
				die(json_encode(array(
					'value' => 'error',
					'message' => $gump->get_readable_string_errors(false)
				)));
			}
		} catch(\Exception $e) {
			$this->_logger->log('error checking input',
				'error', Null, json_encode(array(
					'message' => $e->getMessage()
			)));
			die(json_encode(array(
				'value' => 'error',
				'message' => array('Konnte die Eingaben nicht überprüfen!')
			)));
		}

		if(!empty($_POST['cardnumber'])) {
			$this->cardnumberDuplicatedCheck($_POST['cardnumber']);
		}
	}

	private function cardnumberDuplicatedCheck($cardnumber) {

		$cards = $this->_em->getRepository('DM:BabeskCards')
			->findByCardnumber($cardnumber);
		if(count($cards) == 0) {
			return;
		}
		else if(
			count($cards) == 1 && $cards[0]->getUid() == $this->_userId
		) {
			return;
		}
		else {
			die(json_encode(array(
				'value' => 'error',
				'message' => 'Die Kartennummer existiert im System bereits!'
			)));
		}
	}

	/**
	 * Parses some input so it can be uploaded to the Db
	 */
	protected function inputParse() {

		if(!isset($_POST['pricegroupId']) ||
			isBlank($_POST['pricegroupId'])) {
			$_POST['pricegroupId'] = 0;
		}

		$_POST['isSoli'] = (isset($_POST['isSoli']) &&
			$_POST['isSoli'] == 'true') ? 1 : 0;

		$_POST['accountLocked'] = ($_POST['accountLocked'] == 'true') ? 1 : 0;

		if(isset($_POST['credits'])) {
			$_POST['credits'] = str_replace(',', '.', $_POST['credits']);
		}
		else {
			$_POST['credits'] = 0;
		}
	}

	/**
	 * Uploads the input to the Database
	 */
	protected function upload() {

		$this->_pdo->beginTransaction();

		try {
			$this->_userBeforeChange = $this->userGet($this->_userId);
			$this->groupsChange();
			$this->schoolyearsAndGradesChange();
			$this->kuwasysDataChange();
			$this->babeskDataChange();
			$this->userChange();

			$this->_pdo->commit();

		} catch (\PDOException $e) {
			$this->_pdo->rollback();
			$this->_logger->log(
				'error uploading an user-change', 'error', Null,
				json_encode(array('message' => $e->getMessage()))
			);
			die(json_encode(array(
				'value' => 'error', 'message' => _g('Error changing the user.')
			)));
		}
	}

	protected function userChange() {

		$passwordQuery = $this->passwordQueryCreate($this->_userId);

		try {
			$userQuery = "UPDATE SystemUsers
				SET `forename` = ?, `name` = ?, `username` = ?, `email` = ?,
				$passwordQuery `telephone` = ?, `birthday` = ?, `locked` = ?
				WHERE `ID` = ?";

			$stmtu = $this->_pdo->prepare($userQuery);
			$stmtu->execute(array(
				$_POST['forename'], $_POST['name'], $_POST['username'],
				$_POST['email'], $_POST['telephone'], $_POST['birthday'],
				$_POST['accountLocked'], $this->_userId
			));

		} catch (PDOException $e) {
			$this->_logger->log('Could not change the user: ' .
				'error changing the usertable-data', 'Notice', Null,
				json_encode(array('message' => $e->getMessage())));
			throw $e;
		}
	}

	/**
	 * Creates a Query changing the Password of a User depending on
	 * the Userinput given from the Change-User-Dialog
	 * @return String The Query that changes the Data in the Database
	 */
	protected function passwordQueryCreate($uid) {

		$query = '';

		if($_POST['passwordChange'] == 'true') {
			if(!empty($_POST['password'])) {
				$query = sprintf('password = "%s",', hash_password($_POST['password']));
			}
			else {
				$query = sprintf('password = "%s",', hash_password(''));
			}
		}

		return $query;
	}

	/**
	 * Uploads the group-changes the admin has made
	 */
	protected function groupsChange() {

		$existingGroups = $this->groupsOfUserGet($this->_userId);

		$addStmt = $this->_pdo->prepare(
			'INSERT INTO SystemUsersInGroups (userId, groupId)
				VALUES (?, ?);
		');
		$deleteStmt = $this->_pdo->prepare(
			'DELETE FROM SystemUsersInGroups WHERE userId = ?
					AND groupId = ?;
		');

		if(!empty($_POST['groups'])) {
			foreach($_POST['groups'] as $group) {
				if(array_search($group, $existingGroups) === false) {
					$addStmt->execute(array($this->_userId, $group));
				}
			}
		}
		if(!empty($existingGroups)) {
			foreach($existingGroups as $exGroup) {
				if(array_search($exGroup, $_POST['groups']) === false) {
					$deleteStmt->execute(array($this->_userId, $exGroup));
				}
			}
		}
	}

	/**
	 * Changes in which schoolyears and grades the user is based on the input
	 */
	protected function schoolyearsAndGradesChange() {

		$existingRows = $this->gradeAndSchoolyearDataOfUserGet($this->_userId);
		if(!$existingRows) {
			$existingRows = array();
		}
		$requestedRows = (isset($_POST['schoolyearAndGradeData']))
			? $_POST['schoolyearAndGradeData'] : array();


		if(count($requestedRows)) {
			$addStmt = $this->_pdo->prepare(
				'INSERT INTO SystemAttendances
					(userId, gradeId, schoolyearId) VALUES (?, ?, ?)
			');
			foreach($requestedRows as $requestedRow) {
				if(!in_array($requestedRow, $existingRows)) {
					$addStmt->execute(array(
						$this->_userId,
						$requestedRow['gradeId'],
						$requestedRow['schoolyearId']
					));
				}
			}
		}
		if(count($existingRows)) {
			$deleteStmt = $this->_pdo->prepare(
				'DELETE FROM SystemAttendances
					WHERE userId = ? AND gradeId = ? AND schoolyearId = ?;
			');
			foreach($existingRows as $existingRow) {
				if(!in_array($existingRow, $requestedRows)) {
					$deleteStmt->execute(array(
						$this->_userId,
						$existingRow['gradeId'],
						$existingRow['schoolyearId']
					));
				}
			}
		}
	}

	/*===============================================
	=            Module-specific changes            =
	===============================================*/


	/*==========  Changes to Kuwasys-module  ==========*/

	/**
	 * Changes kuwasys-specific data
	 */
	protected function kuwasysDataChange() {

		if($this->_acl->moduleGet('root/administrator/Kuwasys')) {
			$this->kuwasysClassesChange();
		}
	}

	/**
	 * Changes the classes of the user based on the input
	 */
	protected function kuwasysClassesChange() {

		if(
			isset($_POST['schoolyearAndClassData']) &&
			count($_POST['schoolyearAndClassData'])
		) {
			//Check for correct data
			foreach($_POST['schoolyearAndClassData'] as $key1 => $class1) {
				foreach($_POST['schoolyearAndClassData'] as $key2 =>$class2) {
					if($class1['classId'] == $class2['classId'] &&
						$key1 !== $key2) {
						die(json_encode(array('value' => 'error',
							'message' => 'Der Benutzer kann nicht zweimal ' .
							'gleichzeitig in derselben Klasse sein!'
						)));
					}
				}
			}

			$classes = $this->classesOfUserGet($userId);
			$flatClasses = ArrayFunctions::arrayColumn(
				$classes, 'unitId', 'ID'
			);
			//Upload changes
			$this->classesDeleteDeleted($userId, $flatClasses);
			$this->classesAddMissing($userId, $flatClasses);
		}
	}

	/**
	 * Adds the Classes that were added in the Change-User-dialog
	 *
	 * @param  string $userId          The User-ID
	 * @param  array  $existingClasses A flattened array of classes that the
	 * User already has
	 */
	protected function classesAddMissing($userId, $existingClasses) {

		if(!isset($_POST['schoolyearAndClassData']) ||
				!count($_POST['schoolyearAndClassData'])) {
			return;
		}

		$stmtAdd = $this->_pdo->prepare('INSERT INTO
			KuwasysUsersInClasses (UserID, ClassID, statusId) VALUES
			(:id, :classId, :statusId)');

		foreach($_POST['schoolyearAndClassData'] as $join) {
			if(!isset($existingClasses[$join['classId']]) ||
				$existingClasses[$join['classId']] != $join['statusId']) {
				$stmtAdd->execute(array(
					':id' => $userId,
					':classId' => $join['classId'],
					':statusId' => $join['statusId']
				));
			}
		}
	}

	/**
	 * Deletes the Classes that were removed in the Change-User-dialog
	 *
	 * @param  string $userId          The User-ID
	 * @param  array  $existingClasses A flattened array of classes that the
	 * User already has
	 */
	protected function classesDeleteDeleted($userId, $existingClasses) {

		if(isset($_POST['schoolyearAndClassData'])) {
			$flatClassInput = ArrayFunctions::arrayColumn(
				$_POST['schoolyearAndClassData'], 'statusId', 'classId');
		}
		else {
			$flatClassInput = array();
		}

		$stmtDelete = $this->_pdo->prepare('DELETE FROM
			KuwasysUsersInClasses WHERE UserID = :id AND ClassID = :classId');

		foreach($existingClasses as $exClassId => $exStatusId) {
			if(!isset($flatClassInput[$exClassId]) ||
				$flatClassInput[$exClassId] != $exStatusId) {
				$stmtDelete->execute(array(
					':id' => $userId,
					':classId' => $exClassId
				));
			}
		}
	}

	/*==========  Changes to Babesk-module  ==========*/

	/**
	 * Changes data specific to the babesk-module
	 */
	protected function babeskDataChange() {

		if($this->_acl->moduleGet('root/administrator/Babesk')) {
			$this->cardChange();

			$stmt = $this->_pdo->prepare(
				'UPDATE SystemUsers SET GID = ?, credit = ?, soli = ?
					WHERE ID = ?
			');
			$stmt->execute(array(
				$_POST['pricegroupId'],
				$_POST['credits'],
				$_POST['isSoli'],
				$this->_userId
			));
		}
	}

	/**
	 * Changes the users cardnumber based on the input
	 */
	protected function cardChange() {

		$existingCard = $this->cardOfUserFetch($this->_userId);
		if(!empty($_POST['cardnumber'])) {
			$query = '';

			if(!$existingCard) {
				$query = 'INSERT INTO BabeskCards (cardnumber, UID)
					VALUES (:cardnumber, :userId)';
			}
			else if($existingCard['cardnumber'] == $_POST['cardnumber']) {
				return;
			}
			else {
				$query = 'UPDATE BabeskCards SET cardnumber = :cardnumber,
					changed_cardID = changed_cardID + 1
					WHERE UID = :userId';
			}
			if(!empty($query)) {
				$stmt = $this->_pdo->prepare($query);
				$stmt->execute(array(
					'userId' => $this->_userId,
					'cardnumber' => $_POST['cardnumber']
				));
			}
		}
		else {
			if($existingCard) {
				$stmt = $this->_pdo->prepare(
					'DELETE FROM BabeskCards
					WHERE cardnumber = :cardnumber AND UID = :userId
				');
				$stmt->execute(array(
					'userId' => $this->_userId,
					'cardnumber' => $existingCard['cardnumber']
				));
			}
			else {
				return;
			}
		}
	}

	/**
	 * Fetches the card of the user
	 * @param  int    $userId The Id of the user
	 * @return array          The card-data
	 */
	protected function cardOfUserFetch($userId) {

		$stmt = $this->_pdo->prepare(
			'SELECT * FROM BabeskCards WHERE UID = ?;
		');
		$stmt->execute(array($userId));
		return $stmt->fetch();
	}

	/*-----  End of Module-specific changes  ------*/

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	private $_userId;

	private $_userBeforeChange;

	protected $_changeRules = array(
		'ID' => array(
			'required|numeric|min_len,1|max_len,10',
			'sql_escape',
			'ID'),
		'forename' => array(
			'required|min_len,2|max_len,64',
			'sql_escape',
			'Vorname'),
		'name' => array(
			'required|min_len,3|max_len,64',
			'sql_escape',
			'Nachname'),
		'username' => array(
			'min_len,3|max_len,64',
			'sql_escape',
			'Benutzername'),
		'email' => array(
			'valid_email|min_len,3|max_len,64',
			'sql_escape',
			'Email'),
		'telephone' => array(
			'min_len,3|max_len,64',
			'sql_escape',
			'Telefonnummer'),
		'birthday' => array(
			'isodate|max_len,10',
			'sql_escape',
			'Geburtstag'),
		'pricegroupId' => array(
			'numeric',
			'sql_escape',
			'PreisgruppenId'),
		'cardnumber' => array(
			'min_len,4|max_len,10',
			'sql_escape',
			'Kartennummer'),
		'credits' => array(
			'numeric|min_len,1|max_len,5',
			'sql_escape',
			'Guthaben'),
		'isSoli' => array(
			'boolean',
			'sql_escape',
			'ist-Soli-Benutzer')
	);
}

?>