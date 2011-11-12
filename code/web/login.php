<?php
/**
 * login-function
 * handles the login. It shows the login-form, then checks the input and, if successful,
 * it returns the ID of the User.
 * @param string $username
 * @param string $formpass
 * @return true if successfuly logged in
 */
function login() {
	defined('_WEXEC') or die("Access denied");
	global $smarty;
	
	if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['login'], $_POST['password'])) {
		require_once PATH_INCLUDE.'/constants.php';
		require_once PATH_INCLUDE.'/user_access.php';
		$userManager = new UserManager();
		$username = $_POST['login'];
		$formpass = $_POST['password'];
		if(!preg_match('/\A^[a-zA-Z]{1}[a-zA-Z0-9_-]{2,20}\z/', $username) OR !preg_match('/\A^[a-zA-Z0-9 _-]{4,20}\z/', $formpass)){
			$smarty->assign('error', INVALID_LOGIN);
		}

		//get the userID by the username
		try {
			$uid = $userManager->getUserID($username);
		} catch (MySQLVoidDataException $e) {
			$smarty->assign('error', INVALID_LOGIN);
			$smarty->display('web/login.tpl');
			die();
		} catch (Exception $e) {
			die('ERROR:'.$e);
		}
		$is_pw_correct = $userManager->checkPassword($uid, $formpass);

		if (!$is_pw_correct) {
			$smarty->assign('error', INVALID_LOGIN);
			$userManager->AddLoginTry($uid);
			$smarty->display('web/login.tpl');
			exit();
		}
		else {
			$_SESSION['uid'] = $uid;
			return true;
		}
	}
	else {
		$smarty->display('web/login.tpl');
	}
}


?>