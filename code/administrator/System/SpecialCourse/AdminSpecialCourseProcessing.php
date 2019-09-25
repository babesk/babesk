<?php
require_once PATH_ACCESS . '/GlobalSettingsManager.php';
require_once PATH_ACCESS . '/UserManager.php';
require_once PATH_ACCESS . '/GroupManager.php';

class AdminSpecialCourseProcessing {
	function __construct($SpecialCourseInterface) {

		$this->SpecialCourseInterface = $SpecialCourseInterface;
		$this->messages = array(
				'error' => array('max_credits' => 'Maximales Guthaben der Gruppe überschritten.',
						'mysql_register' => 'Problem bei dem Versuch, den neuen Benutzer in MySQL einzutragen.',
						'input1' => 'Ein Feld wurde falsch mit ', 'input2' => ' ausgefüllt',
						'uid_get_param' => 'Die Benutzer-ID (UID) vom GET-Parameter ist falsch: Der Benutzer ist nicht vorhanden!',
						'groups_get_param' => 'Ein Fehler ist beim holen der Gruppen aufgetreten.',
						'delete' => 'Ein Fehler ist beim löschen des Benutzers aufgetreten:',
						'add_cardid' => 'Konnte die Karten-ID nicht hinzufügen. Vorgang abgebrochen.',
						'register' => 'Konnte den Benutzer nicht hinzufügen!',
						'change' => 'Konnte den Benutzer nicht ändern!',
						'passwd_repeat' => 'das Passwort und das wiederholte Passwort stimmen nicht überein',
						'card_id_change' => 'Warnung: Konnte den Zähler der Karten-ID nicht erhöhen.',
						'no_groups' => 'Es sind keine Gruppen vorhanden!',
						'user_existing' => ' der Benutzer ist schon vorhanden oder die Kartennummer wird schon benutzt.'),
				'get_data_failed' => 'Ein Fehler ist beim fetchen der Daten aufgetreten',
				'notice' => array('please_repeat' => 'Bitte wiederholen sie den Vorgang.'));
	}

	function EditSpecialCourses($editOrShow) {

		$globalSettingsManager = new globalSettingsManager();


		if(!$editOrShow) {
			$specialCourses = $globalSettingsManager->getSpecialCourses();
			$specialCourses_exploded = explode("|", $specialCourses);
			$this->SpecialCourseInterface->ShowSpecialCourses($specialCourses_exploded);
		}
		else {
			$specialCourses="";
			for ($i = 1; $i <= $editOrShow['relcounter']; $i++) {
				if (!$editOrShow['rel'.$i]=="") {
					$specialCourses.=$editOrShow['rel'.$i]."|";
				}
			}
			if(sizeof($specialCourses)>0) $specialCourses = substr($specialCourses, 0,strlen($specialCourses)-1);
			$globalSettingsManager->setSpecialCourses($specialCourses);
			$this->SpecialCourseInterface->ShowSpecialCoursesSet($specialCourses);
		}

	}



	//////////////////////////////////////////////////
	//--------------------Show Users--------------------
	//////////////////////////////////////////////////
	function ShowUsers($filter) {

		$globalSettingsManager = new globalSettingsManager();
		$userManager = new UserManager();
		$groupManager = new GroupManager();

		try {
			$groups = $groupManager->getTableData();
			//$users = $userManager->getTableData();
			isset($_GET['sitePointer'])?$showPage = $_GET['sitePointer'] + 0:$showPage = 1;
			$nextPointer = $showPage*10-10;
			$users = $userManager->getUsersSorted($nextPointer,$filter);
		} catch (Exception $e) {
			$this->logs
					->log('ADMIN', 'MODERATE',
							sprintf('Error while getting Data from MySQL:%s in %s', $e->getMessage(), __METHOD__));
			$this->SpecialCourseInterface->dieError($this->messages['error']['get_data_failed']);
		}

		foreach ($users as &$user) {
			$is_named = false;
			foreach ($groups as $gn) {
				if ($gn['ID'] == $user['GID']) {
					$user['groupname'] = $gn['name'];
					$is_named = true;
					break;
				}
			}
			$is_named or $user['groupname'] = 'Error: This group is non-existent!';
		}
		$specialCourses = $globalSettingsManager->getSpecialCourses();
		$specialCourses_exploded = explode("|", $specialCourses);
		$navbar = navBar($showPage, 'SystemUsers', 'System', 'SpecialCourse', '3',$filter);
		$gradelevel = TableMng::query(sprintf("SELECT gradelevel FROM SystemGrades GROUP BY gradelevel"));
		$years = TableMng::query(sprintf("SELECT * FROM Systemschoolyears"));
		$this->SpecialCourseInterface->ShowUsers($users,$gradelevel,$years,$navbar);
	}

	//////////////////////////////////////////////////
	//--------------------Show single user------------
	//////////////////////////////////////////////////
    /**
     * @param $uid
     */
    function ShowSingleUser($uid) {

		$userManager = new UserManager();

		$users = [];
		try {
			$users = $userManager->getSingleUser($uid);
		} catch (Exception $e) {
			$this->logs->log('ADMIN', 'MODERATE', sprintf('Error while getting Data from MySQL:%s in %s', $e->getMessage(), __METHOD__));
			$this->SpecialCourseInterface->dieError($this->messages['error']['get_data_failed']);
		}

		$schoolyear = TableMng::query("SELECT * FROM SystemSchoolyears WHERE active = 1")[0]['ID'];
		$gradelevel = TableMng::query(sprintf("SELECT * FROM SystemAttendances a JOIN SystemGrades g ON a.gradeId=g.ID WHERE schoolyearId=%s AND userId=%s",$schoolyear, $uid))[0]['gradelevel'];
        $nonCoreSubjects = TableMng::query(sprintf("SELECT * FROM SystemSchoolSubjects s WHERE NOT EXISTS(SELECT * FROM SchbasCoreSubjects c WHERE c.subject_id=s.ID AND c.gradelevel=%s)", $gradelevel));

		dieJson(json_encode(array('user' => $users, 'subjects' => $nonCoreSubjects)));
	}


	function SaveUsers($post_vars) {
		$userManager = new UserManager();
		$courses = array();
		foreach($post_vars as $key => $value) {
			try {
				list($uid,$abbr) = explode('|', $key);
				if(!isset($courses[$uid])) {
                    $courses[$uid]=array();
				}
                $courses[$uid][]=$abbr;
			} catch (Exception $e) {
				$this->SpecialCourseInterface->dieError($this->messages['error']['change'] . $e->getMessage());
			}
		}
		foreach ($courses as $user => $course){
            $userManager->SetSpecialCourse($user, $course);
		}
		$this->ShowUsers('name');
	}

	function showUserByGradelevelAjax($gradelevel, $filter){
		$schoolyear = TableMng::query("SELECT * FROM SystemSchoolyears WHERE active=1")[0]['ID'];

		$user = TableMng::query(sprintf("SELECT u.ID, u.forename, u.name, u.special_course FROM SystemUsers u JOIN SystemAttendances a ON u.ID=a.userId JOIN SystemGrades g ON a.gradeID=g.ID WHERE g.gradelevel = %s AND a.schoolyearId=%s ORDER BY %s", $gradelevel, $schoolyear, $filter));

		$nonCoreSubjects = TableMng::query(sprintf("SELECT * FROM SystemSchoolSubjects s WHERE NOT EXISTS(SELECT * FROM SchbasCoreSubjects c WHERE c.subject_id=s.ID AND c.gradelevel=%s)", $gradelevel));

		dieJson(json_encode(array('user' => $user, 'subjects' => $nonCoreSubjects)));
	}


	var $messages = array();

}

?>
