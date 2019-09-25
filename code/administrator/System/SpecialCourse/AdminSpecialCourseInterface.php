<?php
require_once PATH_ADMIN.'/AdminInterface.php';
/**
 * AdminUserInterface is to output the Interface
 * Enter description here ...
 * @author infchem
 *
 */
class AdminSpecialCourseInterface extends AdminInterface{
	
	function __construct($mod_path) {
		
		parent::__construct($mod_path);
		
		$this->MOD_HEADING = $this->tplFilePath.'mod_specialCourse_header.tpl';
		$this->smarty->assign('SpecialCourseParent', $this->MOD_HEADING);
	}

	function ShowSelectionFunctionality() {
		$this->smarty->display($this->tplFilePath.'specialCourse_select.tpl');
	}
	
	function ShowSpecialCourses($SpecialCourses) {
		$this->smarty->assign('SpecialCourses', $SpecialCourses);
		$this->smarty->display($this->tplFilePath.'show_specialCourses.tpl');
	}
	
	function ShowSpecialCoursesSet($SpecialCourses) {
		$this->smarty->assign('SpecialCourse', $SpecialCourses);
		$this->smarty->display($this->tplFilePath.'show_specialCourses_set.tpl');
	}
	
	function ShowUsers($users,$gradelevel,$years,$navbar) {
		$this->smarty->assign('users', $users);
		$this->smarty->assign('gradelevel', $gradelevel);
		$this->smarty->assign('navbar', $navbar);
		$this->smarty->assign('schoolyears', $years);
		$this->smarty->display($this->tplFilePath.'show_users.tpl');
	
	}
	
	function ShowUsersSuccess() {
		$this->smarty->display($this->tplFilePath.'show_specialCourses_set.tpl');
	
	}
	
	/**
	 * The Path to the Smarty-Parent-Templatefile
	 */
	protected $MOD_HEADING;
}
?>