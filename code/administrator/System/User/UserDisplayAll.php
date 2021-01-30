<?php

/**
 * Shows the display-User-Dialog and handles the data
 */
class UserDisplayAll {
	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct($dataContainer) {

		$this->_pdo = $dataContainer->getPdo();
		$this->_smarty = $dataContainer->getSmarty();
		$this->_acl = $dataContainer->getAcl();
		$this->_logger = $dataContainer->getLogger();
		$this->activeModulesAddSelectableData();
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	/**
	 * Displays all of the Users
	 */
	public function displayAll() {
		$this->_smarty->display(PATH_SMARTY_TPL . '/administrator' .
			'/System/User/display-all.tpl');
	}

	/**
	 * Fetches the appropriate Userdata neede to Display all of them
	 *
	 * This function gets invoked by an Javascript-Script. It parses the data
	 * and fetches userdata in one Query. It sends JSON-encoded data back.
	 *
	 * @uses  $_POST['pagenumber'] The pagenumber to be displayed
	 * @uses  $_POST['usersPerPage'] How many Users are displayed per Page
	 * @uses  $_POST['sortFor'] What column should be sorted
	 * @uses  $_POST['filterForCol'] What Column should be filtered
	 * @uses  $_POST['filterForVal'] The value to filter for
	 */
	public function fetchUsersOrganized() {


		$pagenumber = $_POST['pagenumber'];
		$usersPerPage = $_POST['usersPerPage'];
		$sortFor = $_POST['sortFor'];
		$filterForVal = $_POST['filterForVal'];
		$toEscape = array(
			&$pagenumber, &$usersPerPage, &$sortFor, &$filterForVal
		);
		TableMng::sqlEscapeByArray($toEscape);
		$userToStart = ($pagenumber - 1) * $usersPerPage;

		if(empty($_POST['columnsToFetch'])) {
			$columnsToFetch = array();
		}
		else {
			$columnsToFetch = $_POST['columnsToFetch'];
			foreach($columnsToFetch as &$col) {
				TableMng::sqlEscape($col);
			}
		}

		$filterForColumns = array();
		if(!empty($_POST['filterForColumns'])) {
			foreach($_POST['filterForColumns'] as &$col) {
				TableMng::sqlEscape($col);
			}
			$filterForColumns = $_POST['filterForColumns'];
		}

		//When joining multiple tables, we have multiple IDs
		if($filterForVal == 'ID') {
			$filterForVal = 'u.ID';
		}

		//When user didnt select anything to sort For, default to Id
		if(empty($sortFor)) {
			$sortFor = 'ID';
		}

		//Set the method of sorting
		$sortMethod = ($_POST['sortMethod'] == 'ASC') ? 'ASC' : 'DESC';

		try {
			$queryCreator = new UserDisplayAllQueryCreator(
				$this->_pdo,
				$filterForColumns,
				$sortFor,
				$sortMethod,
				$userToStart,
				$usersPerPage
			);
			$query = $queryCreator->createQuery(
				$columnsToFetch, $sortFor, $filterForVal
			);
			$countQuery = $queryCreator->createCountOfQuery(
				$columnsToFetch, $sortFor, $filterForVal
			);
			// var_dump($query);
			// die();
			//Fetch the Userdata
			TableMng::query('SET @activeSy :=
				(SELECT ID FROM SystemSchoolyears WHERE active = "1");');
			$data = TableMng::query($query);
			$usercount = TableMng::query($countQuery);

			// No division by zero, never show zero sites
			if($usersPerPage != 0 && $usercount[0]['count'] > 0) {
				$pagecount = ceil((int)$usercount[0]['count'] / (int)$usersPerPage);
			}
			else {
				$pagecount = 1;
			}

			$data = $this->fetchedDataToReadable($data, $columnsToFetch);

		} catch (Exception $e) {
			$this->_logger->log('Error processing the data',
				'Error', Null, json_encode(array('msg' => $e->getMessage())));
			die(json_encode(array(
				'value' => 'error',
				'message' => 'Ein Fehler ist bei der Datenverarbeitung ' .
					'aufgetreten.'
			)));
		}

		die(json_encode(array('value' => 'data',
						'users' => $data,
						'pagecount' => $pagecount)));
	}

	public function fetchShowableColumns() {

		// $columns = array();

		// $userdata = TableMng::query("SELECT *
		// 	FROM SystemUsers LIMIT 1, 1");

		// foreach($userdata[0] as $key => $data) {
		// 	if(!empty($this->_selectableColumns[$key])) {
		// 		$columns[$key] = $this->_selectableColumns[$key];
		// 	}
		// }

		// // //Messages-Module existing
		// // if(count(TableMng::query("SHOW TABLES LIKE 'MessageMessages';"))) {
		// // 	$columns['countMessageReceived'] = 'Nachrichten empfangen';
		// // 	$columns['countMessageSend'] = 'Nachrichten abgeschickt';
		// // }
		// // //Kuwasys existing
		// // if(count(TableMng::query("SHOW TABLES LIKE 'KuwasysClasses';"))) {
		// // 	$columns['countClass'] = 'Kurse';
		// // }
		// //Cards existing
		// if(count(TableMng::query("SHOW TABLES LIKE 'BabeskCards';"))) {
		// 	$columns['cardnumber'] = 'Kartennummer';
		// }
		// //Babesk existing
		// // if(count(TableMng::query("SHOW TABLES LIKE 'BabeskOrders';"))) {
		// 	// $columns['countOrders'] = 'Bestellungen';
		// // }

		die(json_encode(array('value' => 'data',
			'message' => $this->_selectableColumns)));
	}


	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	/**
	 * Converts boolean data and other stuff to user-friendly readable data
	 *
	 * For Example, the boolean '1' and '0' get converted to false or true
	 *
	 * @param  Array $data The data to search for
	 * @return Array The converted data
	 */
	protected function fetchedDataToReadable($data, $columnsToFetch) {

		$yes = 'Ja';
		$no = 'Nein';

		foreach($data as &$user) {

			if(isset($user['soli'])) {
				$user['soli'] = ($user['soli']) ? $yes : $no;
			}
			if(isset($user['first_passwd'])) {
				$user['first_passwd'] = ($user['first_passwd']) ? $yes : $no;
			}
			if(isset($user['locked'])) {
				$user['locked'] = ($user['locked']) ? $yes : $no;
			}
			if(isset($user['credit'])) {
				$user['credit'] = number_format($user['credit'], 2, '.', '');
			}
			if(in_array('cardnumber', $columnsToFetch) &&
				!isset($user['cardnumber'])) {
				$user['cardnumber'] = 'Keine';
			}
		}

		return $data;
	}

	/**
	 * Adds various Options for the User depending on Headmodule-Activation
	 */
	protected function activeModulesAddSelectableData() {

		//Columns for Kuwasys
		if($this->_acl->moduleGet('root/administrator/Kuwasys')) {
			$this->selectableAdd(array(
				'classes' => 'diesjährige Kurswahlen',
			));
		}

		//Columns for Babesk
		if($this->_acl->moduleGet('root/administrator/Babesk')) {

			$this->selectableAdd(array(
					'GID' => 'Preisgruppe',
					'credit' => 'Guthaben',
					'cardnumber' => 'Kartennummer',
					'soli' => 'ist Soli'
			));
		}
	}

	/**
	 * Adds the Options given so the User can select the Columns, too
	 *
	 * @param  array  $columns The Options to add
	 */
	protected function selectableAdd(array $columns) {

		$this->_selectableColumns = array_merge(
			$this->_selectableColumns,
			$columns);
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	protected $_smarty;

	protected $_selectableColumns = array(
		'ID' => 'ID',
		'forename' => 'Vorname',
		'name' => 'Name',
		'username' => 'Benutzername',
		'password' => 'Passwort',
		'email' => 'Emailadresse',
		'telephone' => 'Telefonnummer',
		'schoolyears' => 'Schuljahre',
		'grades' => 'Klassen',
		'activeGrade' => 'aktive Klasse',
		'birthday' => 'Geburtstag',
		'first_passwd' => 'ist erstes Passwort',
		'groups' => 'Benutzergruppen'
	);

	protected $_pdo;
}



/******************************************************************************
 * Creates the Query to Display the Users
 *****************************************************************************/

class UserDisplayAllQueryCreator {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct($pdo, $filterForColumns, $sortFor, $sortMethod,
		$userToStart, $usersPerPage) {

		$this->_pdo = $pdo;
		$this->_selectors = array();
		$this->_sortFor = $sortFor;
		$this->_sortMethod = $sortMethod;
		$this->_userToStart = $userToStart;
		$this->_usersPerPage = $usersPerPage;
		$this->_userElementsToFetch = array();

		foreach($filterForColumns as $col) {
			//Quote every column so it can be compared to the automatically
			//generated columns. It can be a user-column or a standalone one,
			//so try both possibilities
			$this->_filterForColumns[] = 'u.' . $this->quoteIdentifier($col);
			$this->_filterForColumns[] = $this->quoteIdentifier($col);
		}
	}


	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function createQuery($columns, $toSortFor, $toFilterValue) {

		$this->preQuery($columns, $toSortFor, $toFilterValue);
		$this->concatQuery($toFilterValue);
		return $this->_query;
	}

	public function createCountOfQuery($columns, $toSortFor, $toFilterValue) {

		$this->preQuery($columns, $toSortFor, $toFilterValue);
		$this->concatCountQuery($toFilterValue);

		return $this->_countQuery;
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function preQuery($columns, $toSortFor, $toFilterValue) {

		if(!$this->_preQueryRun) {
			foreach($columns as $col) {
				$this->addSubquery($col);
			}
			if(!empty($toSortFor)) {
				$this->addSubquery($toSortFor);
			}
			if(!empty($toFilterColumn)) {
				$this->addSubquery($toFilterColumn);
			}
			$this->_preQueryRun = true;
		}
	}

	protected function addSubquery($col) {
		switch($col) {
			case 'grades':
				$this->gradeQuery();
				break;
			case 'cardnumber':
				$this->cardsQuery();
				break;
			case 'activeGrade':
				$this->activeGradeQuery();
				break;
			case 'schoolyears':
				$this->schoolyearQuery();
				break;
			case 'classes':
				$this->classesQuery();
				break;
			case 'groups':
				$this->groupsQuery();
				break;
			default:   //Else guess that its a field in the users-table
				$this->addSelectStatement('u.' . $this->quoteIdentifier($col));
				break;
		}
	}

	protected function concatQuery($toFilterValue) {

		$selectQuery = implode(', ', $this->_selectors);
		$filterQuery = $this->filterForQuery($this->_filters, $toFilterValue);

		$this->_query = "SELECT $selectQuery
			FROM SystemUsers u
				$this->_queryJoin
			GROUP BY u.ID $this->_queryGroup
			$filterQuery
			ORDER BY $this->_sortFor {$this->_sortMethod}
			LIMIT $this->_userToStart, $this->_usersPerPage";
	}

	protected function concatCountQuery($filterVal) {

		$selectQuery = implode(', ', $this->_selectors);
		$filterQuery = $this->filterForQuery($this->_filters, $filterVal);

		$this->_countQuery = "SELECT COUNT(*) AS count FROM
		(SELECT $selectQuery
					FROM SystemUsers u
						$this->_queryJoin
					GROUP BY u.ID $this->_queryGroup
					$filterQuery) counting";
	}

	protected function filterForQuery($columns, $value) {
		if(!empty($columns) && !isBlank($value)) {
			$searches = array();
			$query = '';
			foreach($columns as $col) {
				if(in_array($col, $this->_filterForColumns)) {
					$searches[] = "$col LIKE '%$value%'";
				}
			}
			if(!empty($searches)) {
				$query = 'HAVING ' . implode(' OR ', $searches);
			}
			else {
				$query = '';
			}
			return $query;
		}
		else {
			return '';
		}
	}

	protected function cardsQuery() {

		if(!$this->_cardsQueryDone) {
			$this->addSelectStatement(
				'BabeskCards.cardnumber AS `cardnumber`'
			);
			$this->addJoinStatement('LEFT JOIN
				(SELECT UID, cardnumber FROM BabeskCards) BabeskCards
				ON BabeskCards.UID = u.ID');
			$this->_cardsQueryDone = true;
		}
	}

	protected function schoolyearQuery() {

		$this->usersInGradesAndSchoolyearsQuery();
		if(!$this->_schoolyearQueryDone) {
			$this->addSelectStatement('GROUP_CONCAT(DISTINCT sy.label
					SEPARATOR "<br />")
				AS schoolyears');
			$this->addJoinStatement(
				'
			LEFT JOIN SystemSchoolyears sy ON sy.ID = uigs.schoolyearId');
			$this->_schoolyearQueryDone = true;
		}
	}

	protected function gradeQuery() {

		$this->usersInGradesAndSchoolyearsQuery();
		if(!$this->_gradeQueryDone) {
			$this->addSelectStatement('GROUP_CONCAT( DISTINCT
				CONCAT(g.gradelevel, "-", g.label)
				SEPARATOR "<br />") AS `grades`');

			$this->addJoinStatement('
				LEFT JOIN SystemGrades g ON uigs.gradeId = g.ID
			');
			$this->_gradeQueryDone = true;
		}
	}

	protected function activeGradeQuery() {

		if(!$this->_activeGradeQueryDone) {
			$this->addSelectStatement(
				'CONCAT(ga.gradelevel, "-", ga.label) AS `activeGrade`'
			);
			$this->addJoinStatement('
				LEFT JOIN SystemAttendances uigsag
					ON uigsag.schoolyearId = @activeSchoolyear
					AND uigsag.userId = u.ID
				LEFT JOIN SystemGrades ga ON ga.ID = uigsag.gradeId
			');
			$this->addGroupStatement('ga.`gradelevel`');
			$this->_activeGradeQueryDone = true;
		}
	}

	protected function classesQuery() {

		if(!$this->_classesQueryDone) {
			$this->addSelectStatement(
				'GROUP_CONCAT(
					kuwasys_c.label, "  <i>(", kuwasys_uics.translatedName,
						")</i>"
					SEPARATOR "<hr/>"
				) AS classes'
				);

			$this->addJoinStatement('
				LEFT JOIN KuwasysUsersInClasses kuwasys_uic
					ON kuwasys_uic.UserID = u.ID
				LEFT JOIN KuwasysClasses kuwasys_c
					ON kuwasys_c.Id = kuwasys_uic.ClassID
					AND kuwasys_c.schoolyearId = @activeSchoolyear
				LEFT JOIN KuwasysUsersInClassStatuses kuwasys_uics
					ON kuwasys_uics.ID = kuwasys_uic.statusId
				');
			$this->_classesQueryDone = true;
		}
	}

	protected function usersInGradesAndSchoolyearsQuery() {

		if(!$this->_usersInGradesAndSchoolyearsQueryDone) {
			$this->addJoinStatement('
				LEFT JOIN SystemAttendances uigs
					ON uigs.userId = u.ID
			');
			$this->_usersInGradesAndSchoolyearsQueryDone = true;
		}
	}

    protected function groupsQuery() {

        if(!$this->_groupQueryDone) {
            $this->addSelectStatement('GROUP_CONCAT( DISTINCT sysgroup.name
				SEPARATOR "<br />") AS `groups`');

            $this->addJoinStatement('
				LEFT JOIN SystemUsersInGroups uig ON uig.userId = u.ID
				JOIN SystemGroups sysgroup ON sysgroup.ID = uig.groupId
			');
            $this->_groupQueryDone = true;
        }
    }

	protected function addSelectStatement($st) {

		if(in_array($st, $this->_selectors) === false) {
			$this->_selectors[] = $st;
		}

		// Also add the elements to filter
		if(strstr($st, 'AS') === false) {
			$this->_filters[] = $st;
		}
		else {
			$stSplit = explode('AS', $st);
			$this->_filters[] = trim($stSplit[1]);
		}
	}

	protected function addJoinStatement($st) {

		$this->_queryJoin .= " $st ";
	}

	protected function addGroupStatement($st) {
		$this->_queryGroup .= ", $st";
	}

	/**
	 * Quotes an identifier so that it is not vulnerable to SQL-Injection
	 * @param  string $ident The SQL-identifier to quote
	 * @return string        The quoted (with backticks) identifier
	 */
	protected function quoteIdentifier($ident) {
		return '`' . str_replace('`', '``', $ident) . '`';
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	protected $_selectors;
	protected $_filters;

	protected $_queryJoin = '';
	protected $_queryGroup = '';

	protected $_query = '';
	protected $_countQuery = '';

	protected $_gradeQueryDone = false;
	protected $_activeGradeQueryDone = false;
	protected $_schoolyearQueryDone = false;
	protected $_cardsQueryDone = false;
	protected $_classesQueryDone = false;
	protected $_usersInGradesAndSchoolyearsQueryDone = false;
    protected $_groupQueryDone = false;

	protected $_sortFor;
	protected $_filterForColumns;
	protected $_userToStart;
	protected $_usersPerPage;

	protected $_userElementsToFetch;

	/**
	 * The Accesscontrollayer, To find out what Headmodules are active
	 * @var Acl
	 */
	protected $_acl;

	protected $_pdo;

	protected $_preQueryRun = false;
}

?>
