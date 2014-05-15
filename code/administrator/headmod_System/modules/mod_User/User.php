<?php

require_once 'AdminUserInterface.php';
require_once 'AdminUserProcessing.php';
require_once 'UserDelete.php';
require_once 'UserDisplayAll.php';
require_once 'UsernameAutoCreator.php';
require_once PATH_ACCESS . '/CardManager.php';
require_once PATH_ACCESS . '/UserManager.php';
require_once PATH_INCLUDE . '/Module.php';
require_once PATH_INCLUDE . '/ArrayFunctions.php';
require_once PATH_ADMIN . '/headmod_System/System.php';
require_once PATH_INCLUDE . '/System/UserGroupsManager.php';

class User extends System {

	///////////////////////////////////////////////////////////////////////
	//Constructor
	///////////////////////////////////////////////////////////////////////

	public function __construct($name, $display_name, $path) {
		parent::__construct($name, $display_name, $path);
	}

	///////////////////////////////////////////////////////////////////////
	//Getters and Setters
	///////////////////////////////////////////////////////////////////////

	///////////////////////////////////////////////////////////////////////
	//Methods
	///////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);

		//hotfix, to be reworked
		if(isset($_GET['showPdfOfDeletedUser'])) {
			TableMng::sqlEscape($_GET['pdfId']);
			$fileId = $_GET['pdfId'];
			$deleter = new UserDelete();
			$deleter->showPdfOfDeletedUser($fileId);
			die();
		}

		$execReq = $dataContainer->getExecutionCommand()->pathGet();
		if($this->submoduleCountGet($execReq)) {
			$this->submoduleExecuteAsMethod($execReq);
		}
		else {
			// $this->actionSwitch();
			$this->userInterface->ShowSelectionFunctionality();
		}
	}
	///////////////////////////////////////////////////////////////////////
	//Implementations
	///////////////////////////////////////////////////////////////////////

	protected function entryPoint($dataContainer) {

		defined('_AEXEC') or die('Access denied');
		parent::entryPoint($dataContainer);
		$this->userManager = new UserManager();
		$this->userInterface = new AdminUserInterface($this->relPath);
		$this->_interface = $this->userInterface;
		$this->userProcessing = new AdminUserProcessing($this->userInterface);
		$this->messages = array('error' => array(
			'no_id' => 'ID nicht gefunden.'));
		parent::initSmartyVariables();
		$this->_dataContainer = $dataContainer;
	}

	protected function submoduleDisplayAllExecute() {

		$displayer = new UserDisplayAll($this->_dataContainer);
		$displayer->displayAll();
	}

	protected function submoduleFetchUserdataExecute() {

		$displayer = new UserDisplayAll($this->_dataContainer);
		$displayer->fetchUsersOrganized();
	}

	protected function submoduleFetchUsercolumnsExecute() {

		$displayer = new UserDisplayAll($this->_dataContainer);
		$displayer->fetchShowableColumns();
	}

	protected function submoduleDeleteExecute() {

		$deleter = new UserDelete($this->_smarty);
		$deleter->deleteFromDb();
	}

	/**
	 * Registers a user. Requests should come from Ajax
	 *
	 * Either shows the Register-form or, if POST-Data send, tries to add the
	 * Data-input to the Database as a new User
	 */
	protected function submoduleRegisterExecute() {

		if (isset($_POST['forename'], $_POST['lastname'])) {
			$_POST['birthday'] = date('Y-m-d', strtotime($_POST['birthday']));
			$this->registerCheck(); //Form filled out
			$this->registerUpload();
			die(json_encode(array('value' => 'success',
				'message' => array("Der Benutzer $_POST[forename] " .
					"$_POST[lastname] wurde erfolgreich hinzugefügt"))));
		}
		else { //show Form
			$this->registerForm();
		}
	}

	/**
	 * Displays the Register-a-User-Form to the Administrator
	 */
	protected function registerForm() {

		try {
			//---fetch data
			try { //Babesk-specific, dont crash when table not exist
				$priceGroups = $this->arrayGetFlattened(
					'SELECT ID, name FROM BabeskPriceGroups');

			} catch (Exception $e) {
				$priceGroups = array();
			}

			//---display
			try {
				$this->_smarty->assign(
					'priceGroups', $this->pricegroupsFetch()
				);
			} catch (PDOException $e) {
				//Pricegroups is Babesk-specific, dont crash when table not
				//exists
				$this->_smarty->assign('priceGroups', array());
			}
			$this->_smarty->assign(
				'schoolyears', $this->schoolyearsGetAllFlattened()
			);
			$this->_smarty->assign('grades', $this->gradesGetAllFlattened());
			$this->_smarty->assign('usergroups', $this->usergroupsGet());

			$this->displayTpl('register.tpl');

		} catch (Exception $e) {
			$this->_logger->log('error fetching data for user-register-form',
				'Notice', Null, json_encode(array('msg' => $e->getMessage())));
			$this->_interface->dieError('Ein Fehler ist beim Abrufen der Daten aufgetreten!');
		}
	}

	/**
	 * Returns all existing usergroups
	 * @return Group  The root-user-group with all other groups as childs
	 */
	protected function usergroupsGet() {

		$gMng = new \Babesk\System\UserGroupsManager(
			$this->_pdo, $this->_logger
		);
		$gMng->groupsLoad();
		return $gMng->userGroupGet();
	}

	/**
	 * Fetches and returns all existing pricegroups
	 * @return array  The pricegroups
	 */
	protected function pricegroupsFetch() {

		$res = $this->_pdo->query(
			'SELECT ID, name FROM BabeskPriceGroups'
		);
		$pricegroups = $res->fetchAll(PDO::FETCH_KEY_PAIR);
		return $pricegroups;
	}

	/**
	 * Checks the Inputdata of the registerform for correct Format and stuff
	 */
	protected function registerCheck() {

		require_once PATH_INCLUDE . '/gump.php';

		$gump = new GUMP();

		$_POST['isSoli'] = (isset($_POST['isSoli'])
			&& $_POST['isSoli'] == 'true');

		try {
			$gump->rules(self::$registerRules);
			// $_POST = $gump->input_preprocess_by_ruleset($_POST,
				// self::$registerRules);
			//Set none-filled-out formelements to be at least a void string,
			//for easier processing
			$gump->voidVarsToStringByRuleset($_POST, self::$registerRules);

			//validate and MySQL-Escape the elements
			if($gump->run($_POST)) {

			}
			else {
				die(json_encode(array(
					'value' => 'inputError',
					'message' => $gump->get_readable_string_errors(false)
					)));
			}
		} catch (Exception $e) {
			die(json_encode(array(
				'value' => 'inputError',
				'message' => array('Konnte die Eingaben nicht überprüfen!'))));
		}
	}

	/**
	 * Registers a new user by the data given as post-variables
	 * Dies displaying something, uses Ajax
	 */
	protected function registerUpload() {

		try {
			$this->_pdo->beginTransaction();
			$userId = $this->registerUserUpload();
			$this->registerUserInGradesAndSchoolyearsUpload($userId);
			$this->registerUsergroupsUpload($userId);
			$this->registerCardnumberUpload($userId);
			$this->_pdo->commit();

		} catch (\PDOException $e) {
			$this->_logger->log('Error adding a new user',
				'Notice', Null, json_encode(array(
					'msg' => $e->getMessage(),
					'post' => var_export($_POST, true)
			)));
			die(json_encode(array('value' => $e->getMessage())));
		}
	}

	/**
	 * Adds a new user to the table by post-variables
	 * @return int    The id of the newly created user
	 */
	protected function registerUserUpload() {

		\ArrayFunctions::setOnBlank($_POST, 'credits', 0);
		\ArrayFunctions::setOnBlank($_POST, 'isSoli', 0);
		\ArrayFunctions::setOnBlank($_POST, 'pricegroupId', 0);

		//Password-specific, hashes it
		if(isset($_POST['presetPasswordToggle']) &&
			$_POST['presetPasswordToggle'] == 'true'
		) {
			$_POST['password'] = $this->presetPasswordGet();
		}
		else {
			if(!empty($_POST['password'])) {
				$_POST['password'] = hash_password($_POST['password']);
			}
			else {
				$_POST['password'] = '';
			}
		}

		$first_passwd = ($this->isFirstPasswordEnabled()) ? 1 : 0;

		$stmt = $this->_pdo->prepare(
			'INSERT INTO SystemUsers (
				forename, name, username, password, email, telephone,
				birthday, login_tries, last_login, first_passwd, locked,
				GID, credit, soli
				)
				VALUES (
					:forename, :name, :username, :password, :email,
					:telephone, :birthday, :login_tries, :last_login,
					:first_passwd, :locked, :GID, :credit, :soli );
		');

		$stmt->execute(array(
			'forename' => $_POST['forename'],
			'name' => $_POST['lastname'],
			'username' => $_POST['username'],
			'password' => $_POST['password'],
			'email' => $_POST['email'],
			'telephone' => $_POST['telephone'],
			'birthday' => $_POST['birthday'],
			'login_tries' => 0,
			'last_login' => 0,
			'first_passwd' => $first_passwd,
			'locked' => 0,
			'GID' => $_POST['pricegroupId'],
			'credit' => $_POST['credits'],
			'soli' => $_POST['isSoli']
		));

		return $this->_pdo->lastInsertId();
	}

	/**
	 * Adds the selected grades and schoolyears to the newly created user
	 * @param  int    $newUserId The id of the new user
	 */
	protected function registerUserInGradesAndSchoolyearsUpload($newUserId) {

		if(!empty($_POST['schoolyearAndGradeData'])) {
			$stmt = $this->_pdo->prepare(
				'INSERT INTO SystemUsersInGradesAndSchoolyears (
						userId, gradeId, schoolyearId
					) VALUES (
						:userId, :gradeId, :schoolyearId
					);
			');
			foreach($_POST['schoolyearAndGradeData'] as $el) {
				$stmt->execute(array(
					'userId' => $newUserId,
					'gradeId' => $el['gradeId'],
					'schoolyearId' => $el['schoolyearId']
				));
			}
		}

	}

	/**
	 * Adds the newly created user to the selected usergroups
	 * @param  int    $newUserId The id of the newly created user
	 */
	protected function registerUsergroupsUpload($newUserId) {

		if(!empty($_POST['groups'])) {
			$stmt = $this->_pdo->prepare(
				'INSERT INTO SystemUsersInGroups (userId, groupId)
					VALUES(:userId, :groupId);
			');
			foreach($_POST['groups'] as $groupId) {
				$stmt->execute(array(
					'userId' => $newUserId,
					'groupId' => $groupId
				));
			}
		}
	}

	/**
	 * Adds a card to the newly created user if input given
	 * @param  int    $newUserId The id of the newly created user
	 */
	protected function registerCardnumberUpload($newUserId) {

		if(!empty($_POST['cardnumber'])) {
			$stmt = $this->_pdo->prepare(
				'INSERT INTO BabeskCards (cardnumber, UID) VALUES (
					:cardnumber, :userId
				);
			');
			$stmt->execute(array(
				'cardnumber' => $_POST['cardnumber'],
				'userId' => $newUserId
			));
		}
	}

	/**
	 * Checks if First Password in GlobalSettings enabled
	 *
	 * Dies when Error occured during fetching
	 *
	 * @return boolean If the User should input a new Password on First Login
	 */
	protected function isFirstPasswordEnabled() {

		try {
			$data = TableMng::querySingleEntry('SELECT value
				FROM SystemGlobalSettings
				WHERE name = "firstLoginChangePassword"');

		} catch (Exception $e) {
			$this->_interface->dieError(_g('Could not check if first ' .
				'Password on Login is enabled!'));
		}

		if(!count($data)) {
			return false;
		}
		else {
			return (boolean) $data['value'];
		}
	}

	/**
	 * Fetches the presetPassword set in GlobalSettings
	 *
	 * @return string The hashed Password or a void string if no
	 *                PresetPassword is set or it could not be fetched
	 */
	protected function presetPasswordGet() {

		try {
			$stmt = $this->_pdo->query(
				'SELECT value FROM SystemGlobalSettings
				WHERE name = "presetPassword"');
			$stmt->execute();
			$res = $stmt->fetchColumn();

		} catch (PDOException $e) {
			$this->_logger->log(
				'Could not fetch the Preset Password! ' . __METHOD__);
			return '';
		}

		if(empty($res)) {
			return '';
		}
		else {
			return $res;
		}
	}

	/**
	 * Fetches data from the Database and rearranges them
	 *
	 * This function executed the Query given and rearranges the Elements into
	 * a flat key => value-Array
	 *
	 * @param  String $query The SQL-Query to execute
	 * @param  String $key (Standard: "ID") the column-name of the element of
	 * each row that should be the key for the new array-element
	 * @param  String $value (Standard: "name") the column-name of the element
	 * of each row that should be the value for the rearranged Array-Element
	 * @return Array The rearranged Array or a void array if SQL-Query returned
	 * nothing
	 */
	protected function arrayGetFlattened($query, $key = 'ID',
		$value = 'name') {

		$rows = TableMng::query($query);
		return ArrayFunctions::arrayColumn($rows, $value, $key);
	}

	protected function submoduleCreateUsernamesExecute() {
		if (isset($_POST['confirmed'])) {
			$creator = new UsernameAutoCreator();
			$scheme = new UsernameScheme();
			$scheme->templateAdd(UsernameScheme::Forename);
			$scheme->stringAdd('.');
			$scheme->templateAdd(UsernameScheme::Name);
			$creator->usersSet($this->userManager->getAllUsers());
			$creator->schemeSet($scheme);
			$users = $creator->usernameCreateAll();
			foreach ($users as $user) {
				///@todo: PURE EVIL DOOM LOOP OF LOOPING SQL-USE. Kill it with fire.
				$this->userManager->alterUsername ($user ['ID'], $user ['username']);
			}
			$this->userInterface->dieMsg ('Die Benutzernamen wurden erfolgreich geÃ¤ndert');
		}
		else {
			$this->userInterface->showConfirmAutoChangeUsernames ();
		}
	}

	protected function submoduleRemoveSpecialCharsFromUsernamesExecute () {

		if (isset ($_POST ['removeSpecialChars'])) {
			try {
				$users = $this->usersGetAll();
				$this->_pdo->beginTransaction();
				$stmt = $this->_pdo->prepare(
					'UPDATE SystemUsers SET username = ? WHERE ID = ?'
				);
				foreach($users as $user) {
					$stmt->execute(array(
						$this->specialCharsRemove($user['username']),
						$user['ID']
					));
				}
				$this->_pdo->commit();

			} catch (\PDOException $e) {
				$this->_pdo->rollback();
				$this->_logger->log(
					'Error removing special characters from usernames',
					'Notice', Null, json_encode(array(
						'msg' => $e->getMessage()
				)));
				$this->_interface->dieError(
					_g('Could not remove the special characters!')
				);
			}
			$this->_interface->dieSuccess(
				_g('The special characters were successfully removed!')
			);
		}
		else {
			$this->userInterface->showRemoveSpecialCharsFromUsername();
		}
	}

	/**
	 * Fetches all of the users from the database and returns them
	 *
	 * @return array(array(...)) An Array of Users, each one represented by
	 * another array
	 */
	protected function usersGetAll () {

		try {
			$data = TableMng::query(
				'SELECT u.*,
				(SELECT CONCAT(g.gradelevel, g.label) AS KuwasysClasses
					FROM SystemUsersInGradesAndSchoolyears uigs
					LEFT JOIN SystemGrades g ON uigs.gradeId = g.ID
					WHERE uigs.userId = u.ID AND
						uigs.schoolyearId = @activeSchoolyear) AS KuwasysClasses
				FROM SystemUsers u');

		} catch (Exception $e) {
			$this->userInterface->dieError ('Konnte die Benutzer nicht abrufen');
		}

		return $data;
	}

	protected function specialCharsRemove ($str) {
		$str = str_replace(array_keys (self::$invalid), array_values (self::$invalid), $str);
		return $str;
	}

	/**
	 * This function prepares and shows the ChangeUser-Form
	 *
	 * @param string (numeric) $uid The ID of the User
	 */
	protected function submoduleDisplayChangeExecute() {

		$uid = $_GET['ID'];
		TableMng::sqlEscape($uid);

		try {
			list($user,
				$cardnumber,
				$priceGroups,
				$grades,
				$schoolyears,
				$gradesAndSchoolyears,
				$userInGroups,
				$groups,
				$modsActivated) = $this->changeDisplayDataFetch($uid);

			$classes = array();
			$statuses = array();
			$classesOfUser = array();
			if($modsActivated['Kuwasys']) {
				list($classes,
					$statuses,
					$classesOfUser
					) = $this->userChangeDisplayKuwasysDataFetch($uid);
			}

		} catch (Exception $e) {
			$this->userInterface->dieError($e->getMessage());
		}

		$this->userInterface->ShowChangeUser(
			$user,
			$cardnumber,
			$priceGroups,
			$grades,
			$schoolyears,
			$gradesAndSchoolyears,
			$groups,
			$modsActivated,
			$classes,
			$statuses,
			$classesOfUser);
	}

	protected function changeDisplayDataFetch($userId) {

		$user = $this->userGet($userId);
		$gradeAndSchoolyears = $this->gradeAndSchoolyearDataOfUserGet(
			$userId);
		$grades = $this->gradesGetAllFlattened();
		$schoolyears = $this->schoolyearsGetAllFlattened();

		$userInGroups = $this->groupsOfUserGet($userId);
		$gMng = new \Babesk\System\UserGroupsManager(
			$this->_pdo, $this->_logger
		);
		$gMng->groupsLoad();
		$usergroups = $gMng->userGroupGet();

		$modsActivated = $this->userChangeModuleActivationGet();

		if($modsActivated['Babesk']) {
			$priceGroups = $this->arrayGetFlattened(
				'SELECT ID, name FROM BabeskPriceGroups');
			$cardnumber = $this->cardnumberGetByUserId($userId);
			$cardnumber = (!empty($cardnumber)) ? $cardnumber : '';

		}
		else {
			$cardnumber = '';
			$priceGroups = array();
		}

		return array($user,
			$cardnumber,
			$priceGroups,
			$grades,
			$schoolyears,
			$gradeAndSchoolyears,
			$userInGroups,
			$usergroups,
			$modsActivated);
	}

	/**
	 * Fetches data specific to the Kuwasys Headmodule
	 *
	 * @return array The Data needed to change the User for Kuwasys
	 */
	protected function userChangeDisplayKuwasysDataFetch($uid) {

		$classes = $this->classesGetAll();
		$statuses = $this->usersInClassStatusGetAll();
		$classesOfUser = $this->classesOfUserGet($uid);

		return array($classes, $statuses, $classesOfUser);
	}

	/**
	 * Fetches and returns all Classes in the Database
	 *
	 * @return Array The Classes as an Array
	 */
	protected function classesGetAll() {

		try {
			$data = TableMng::query('SELECT * FROM KuwasysClasses');

		} catch (Exception $e) {
			$this->_interface->dieError(_g('Could not fetch the Classes!'));
		}

		return $data;
	}

	/**
	 * Fetches all Statuses of a User-in-Class-Registration and returns them
	 *
	 * @return array The Statuses as an Array
	 */
	protected function usersInClassStatusGetAll() {

		try {
			$data = TableMng::query('SELECT * FROM KuwasysUsersInClassStatuses');

		} catch (Exception $e) {
			$this->_interface->dieError(
				_g('Could not fetch the User-in-Class-Statuses!'));
		}

		return $data;
	}

	/**
	 * Checks if Headmodules are active to display / hide Input-fields
	 *
	 * @return array An Array containing if the Headmodules are activated or
	 * not
	 */
	protected function userChangeModuleActivationGet() {

		$modsActivated = array();
		$modsActivated['Kuwasys'] =
			(boolean) $this->_acl->moduleGet('root/administrator/Kuwasys');

		$modsActivated['Babesk'] =
			(boolean) $this->_acl->moduleGet('root/administrator/Babesk');

		return $modsActivated;
	}

	/**
	 * Fetches all Classes of a User
	 *
	 * @return array The Classes of a user of all Schoolyears
	 */
	protected function classesOfUserGet($id) {

		try {
			$data = TableMng::query("SELECT c.*, uic.statusId AS statusId
				FROM KuwasysClasses c
				JOIN KuwasysUsersInClasses uic ON c.ID = uic.ClassID
				WHERE uic.UserID = '$id'");

		} catch (Exception $e) {
			$this->_interface->dieError(
				_g('Could not fetch the Classes of the User'));
		}

		return $data;
	}

	/**
	 * Returns the data of the user with the id $userId
	 * Throws PDOException if something has gone wrong
	 * @param  int    $userId The Id of the user to search for
	 * @return array          The userdata or false if not found
	 */
	protected function userGet($userId) {

		$stmt = $this->_pdo->prepare(
			'SELECT * FROM SystemUsers WHERE ID = ?
		');
		$stmt->execute(array($userId));
		return $stmt->fetch();
	}

	protected function gradeAndSchoolyearDataOfUserGet($uid) {

		$stmt = $this->_pdo->prepare(
			'SELECT gradeId, schoolyearId
				FROM SystemUsersInGradesAndSchoolyears
				WHERE userId = ?
		');
		$stmt->execute(array($uid));
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	protected function cardnumberGetByUserId($userId) {

		$stmt = $this->_pdo->prepare(
			'SELECT cardnumber FROM BabeskCards WHERE UID = ?'
		);
		$stmt->execute(array($userId));
		return $stmt->fetchColumn();
	}

	protected function gradesGetAllFlattened() {

		$stmt = $this->_pdo->query(
			'SELECT ID, CONCAT(gradelevel, "-", label) AS name
				FROM SystemGrades;
		');
		return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
	}

	protected function schoolyearsGetAllFlattened() {

		$stmt = $this->_pdo->query(
			'SELECT ID, label AS name FROM SystemSchoolyears'
		);
		return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
	}

	/**
	 * Fetches all Usergroups from the Database
	 *
	 * @return array  The Usergroups as an Array: '<ID>' => '<name>'
	 */
	protected function usergroupsGetAllFlattened() {

		try {
			$stmt = $this->_pdo->query('SELECT ID, name FROM SystemGroups');

			return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

		} catch (PDOException $e) {
			$this->_interface->dieError(_g('Error fetching the Usergroups'));
		}
	}

	protected function schoolyearsGetAllWithCheckIsUserIn($userId) {

		$schoolyears = TableMng::query(
			"SELECT ID, label AS name, (
				SELECT COUNT(*) AS count FROM SystemUsersInGradesAndSchoolyears uigs
				WHERE sy.ID = uigs.schoolyearId AND uigs.userId = $userId
			) AS isUserIn
			FROM SystemSchoolyears sy
			ORDER BY active DESC;");

		return $schoolyears;
	}

	protected function groupsGetAllWithCheckIsUserIn($userId) {

		$groups = TableMng::query(
			"SELECT ID, name,
			(SELECT COUNT(*) AS count FROM SystemUsersInGroups uig
				WHERE g.ID = uig.groupId AND uig.userId = $userId)
					AS isUserIn
			FROM SystemGroups g");

		return $groups;
	}

	/**
	 * Fetches the Groups of one User and returns them
	 *
	 * @param  integer $userId
	 * @return Array
	 */
	protected function groupsOfUserGet($userId) {

		$stmt = $this->_pdo->prepare(
			'SELECT g.ID FROM SystemGroups g
				JOIN SystemUsersInGroups uig ON g.ID = uig.groupId
				WHERE uig.userId = ?
		');
		$stmt->execute(array($userId));
		return $stmt->fetchAll(PDO::FETCH_COLUMN);
	}

	/**
	 * Allows the User to Import the Csv-Files of a User
	 */
	protected function submoduleUserCsvImportExecute() {

		require_once 'UserCsvImport.php';

		if(count($_FILES)) {
			$importer = new UserCsvImport();
			$importer->execute($this->_dataContainer);
		}
		else {
			$this->displayTpl('importCsvFile.tpl');
		}
	}

	/**===============================================================**
	 * Searches for the given username and gives a list of suggestions *
	 **===============================================================**/
	protected function submoduleJsSearchForUsernameExecute() {

		$limit = 5;
		try {
			$data = array();
			$stmt = $this->_pdo->prepare('SELECT u.username AS username
				FROM SystemUsers u
				JOIN SystemUsersInGradesAndSchoolyears uigs ON u.ID = uigs.userId
				WHERE uigs.schoolyearId = @activeSchoolyear
					AND u.username LIKE :name
				LIMIT 0, 10');
			$stmt->execute(array('name' => '%' .$_GET['term'] . '%'));

			while($el = $stmt->fetchColumn()) {
				$data[] = $el;
			}

		} catch (Exception $e) {
			$this->_logger->log('Error searching for username',
				'Notice', Null,json_encode(array('msg' => $e->getMessage())));
			die(json_encode(array($e->getMessage())));
		}

		die(json_encode($data));
	}

	///////////////////////////////////////////////////////////////////////
	//Attributes
	///////////////////////////////////////////////////////////////////////

	protected $userManager;
	protected $userInterface;
	protected $userProcessing;
	protected $messages;
	protected $_interface;
	protected $_dataContainer;
	protected $_pdo;

	protected static $invalid = array('Š'=>'S', 'š'=>'s', 'Đ'=>'D', 'đ'=>'d',
		'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c', 'À'=>'A',
		'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C',
		'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
		'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O',
		'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'à'=>'a',
		'á'=>'a', 'â'=>'a', 'ã'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e',
		'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i',
		'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ø'=>'o',
		'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y',
		'Ŕ'=>'R', 'ŕ'=>'r');

	protected static $registerRules = array(
		'forename' => array(
			'required|min_len,2|max_len,64',
			'sql_escape',
			'Vorname'),
		'lastname' => array(
			'required|min_len,3|max_len,64',
			'sql_escape',
			'Nachname'),
		'username' => array(
			'min_len,3|max_len,64',
			'sql_escape',
			'Benutzername'),
		'password' => array(
			'min_len,3|max_len,64',
			'sql_escape',
			'Passwort'),
		'email' => array(
			'valid_email|min_len,3|max_len,64',
			'sql_escape',
			'Email'),
		'telephone' => array(
			'min_len,3|max_len,64',
			'sql_escape',
			'Telefonnummer'),
		'birthday' => array(
			'max_len,10|isodate',
			'sql_escape',
			'Geburtstag'),
		'pricegroupId' => array(
			'numeric',
			'sql_escape',
			'PreisgruppenId'),
		'schoolyearId' => array(
			'numeric',
			'sql_escape',
			'SchuljahrId'),
		'gradeId' => array(
			'numeric',
			'sql_escape',
			'KlassenId'),
		'cardnumber' => array(
			'exact_len,10',
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
