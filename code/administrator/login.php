<?php
//No direct access
defined('_AEXEC') or die("Access denied");

require_once PATH_ACCESS . '/AdminManager.php';
require_once PATH_ACCESS . '/AdminGroupManager.php';
require_once PATH_INCLUDE . '/moduleManager.php';

$adminManager = new AdminManager();
$admingroupManager = new AdminGroupManager();
$moduleManager = new ModuleManager('administrator');

$smarty->assign('babesk_version', file_get_contents("../version.txt"));

$login = false;

if ('POST' == $_SERVER['REQUEST_METHOD']) {
	if (!isset($_POST['Username'], $_POST['Password'])) {
		die(INVALID_FORM);
	}
	if (('' == $adminname = trim($_POST['Username'])) OR ('' == $password = trim($_POST['Password']))) {
		die(EMPTY_FORM);
	}
	$aid = $adminManager->getAdminID($adminname);
	$gid = $admingroupManager->getAdminGroup($adminname);

	if (!$adminManager->checkPassword($aid, $password)) {
		$smarty->assign('status', INVALID_LOGIN);
		$smarty->display('administrator/login.tpl');
		return;
	}
	else {
		$_SESSION['UID'] = $aid;
		$_SESSION['GID'] = $gid;
		$_SESSION['username'] = $adminname;
	}

	//an array for module data
	$_SESSION['module_data'] = array();

	//Get the available modules
	$groupData = $admingroupManager->getAdminGroupData($gid, 'modules');
	$module_string = $groupData['modules'];
	$modules = $moduleManager->getAllModules();

	//copy the module mask
	$_SESSION['modules'] = array();
	foreach ($modules as $module) {
		$_SESSION['modules'][$module->getName()] = False; //set all modules to disallow
	}

	//global admin
	if ($module_string == '_ALL') {
		$moduleManager->allowAllModules();
	}
	//any regular admin
	else {
		$moduleManager->allowModules(explode(', ', $module_string));
	}
	//Successfully logged in
	$login = true;
}
else {

	$smarty->display('administrator/login.tpl');
}

?>