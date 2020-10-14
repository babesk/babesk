<?php
require_once PATH_ADMIN.'/AdminInterface.php';
/**
 * @author Mirek Hancl
 *
 */
class AdminBooklistInterface extends AdminInterface{

	function __construct($mod_path) {

		parent::__construct($mod_path);

		$this->MOD_HEADING = $this->tplFilePath.'mod_booklist_header.tpl';
		$this->smarty->assign('booklistParent', $this->MOD_HEADING);
	}

	function ShowSelectionFunctionality() {
		$this->smarty->display($this->tplFilePath.'index.tpl');
	}

	function ShowBooklist($bookcodes,$navbar) {
		$this->smarty->assign('bookcodes', $bookcodes);
		$this->smarty->assign('navbar', $navbar);
		$this->smarty->display($this->tplFilePath.'show_booklist.tpl');
	}

	function ShowSelectionForBooksToKeep() {
		$this->smarty->display($this->tplFilePath.'showGradeSelection.tpl');
	}

	function ShowSelectionForBooksByTopic() {
		$this->smarty->display($this->tplFilePath.'showTopicSelection.tpl');
	}

	function ShowDeleteConfirmation($id) {
		$this->smarty->assign('id', $id);
		$this->smarty->display($this->tplFilePath.'deletion_confirm.tpl');
	}


	/**
	 * The Path to the Smarty-Parent-Templatefile
	 */
	protected $MOD_HEADING;
}
?>