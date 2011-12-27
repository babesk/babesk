<?php
/**
 * AdminUserInterface is to output the Interface
 * Enter description here ...
 * @author voelkerball
 *
 */
class AdminUserInterface {
	function __construct() {
		// 		require_once PATH_INCLUDE.'';
		global $smarty;
		$this->smarty = $smarty;
		$this->PathUserTemplates = PATH_SMARTY_ADMIN_MOD.'/mod_user/';
	}

	/**
	 * Show an error to the user
	 * This function shows an error to the user.
	 * @param string $msg The message to be shown
	 * @param string $lnk if not null, a link to another side, under the error message
	 */
	function ShowError($msg) {
		$this->smarty->assign('msg', $msg);
		$this->smarty->display($this->PathUserTemplates.'error.tpl');
	}

	function ShowSelectionFunctionality() {
		$this->smarty->display($this->PathUserTemplates.'user_select.tpl');
	}
	
	function ShowRegisterForm($ar_gid, $ar_g_names) {
		$this->smarty->assign('gid', $ar_gid);
		$this->smarty->assign('g_names', $ar_g_names);
		$this->smarty->display($this->PathUserTemplates.'register.tpl');
	}
	
	function ShowCardidInput() {
		$this->smarty->display($this->PathUserTemplates.'register_input_id.tpl');
	}
	
	function ShowRegisterFin($name, $forename) {
		$this->smarty->assign('name', $name);
		$this->smarty->assign('forename', $forename);
		$this->smarty->display($this->PathUserTemplates.'register_finished.tpl');
	}
	
	function ShowUsers($users) {
		$this->smarty->assign('users', $users);
		$this->smarty->display($this->PathUserTemplates.'show_users.tpl');
	}
	
	function ShowRepeatRegister() {
		echo '<p><a href="index.php?section=user&action=1">Bitte wiederholen sie den Vorgang</a></p>';
	}
	
	function ShowDeleteConfirmation($uid, $forename, $name) {
		$this->smarty->assign('forename',$forename);
		$this->smarty->assign('name',$name);
		$this->smarty->assign('uid',$uid);
		$this->smarty->display($this->PathUserTemplates.'deletion_confirm.tpl');
	}
	
	function ShowDeleteFin() {
		$this->smarty->display($this->PathUserTemplates.'deletion_finished.tpl');
	}
	
	function ShowChangeUser($user, $ar_gid, $ar_g_names) {
		$this->smarty->assign('user', $user);
		$this->smarty->assign('g_names', $ar_g_names);
		$this->smarty->assign('gid', $ar_gid);
	    
		$this->smarty->display($this->PathUserTemplates.'change_user.tpl');
		
	}
	function ShowChangeUserFin($id, $name, $forename, $username, $birthday, $credits, $GID, $locked) {
		$this->smarty->assign('id', $id);
		$this->smarty->assign('name', $name);
		$this->smarty->assign('forename', $forename);
		$this->smarty->assign('username', $username);
		$this->smarty->assign('birthday', $birthday);
		$this->smarty->assign('credits', $credits);
		$this->smarty->assign('gid', $GID);
		$this->smarty->assign('locked', $locked);
		$this->smarty->display($this->PathUserTemplates.'change_user_fin.tpl');
	}
	
	private $smarty;
	/**
	 * file in which the Smarty-templates are
	 */
	private $PathUserTemplates;
}
?>