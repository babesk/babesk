<?php

require_once PATH_CODE . '/include/GeneralInterface.php';

/**
 * The class the Interface-classes in the modules base on
 */
class AdminInterface extends GeneralInterface {

	public function __construct ($mod_rel_path, $smarty = NULL) {

		if ($smarty) {
			$this->smarty = $smarty;
		}
		else {
			$this->smarty = self::$smartyHelper;
		}
		$this->tplFilePath = PATH_SMARTY_TPL . '/administrator' . $mod_rel_path;
		$this->parentPath = PATH_SMARTY_TPL . '/administrator/baseLayout.tpl';
		$this->smarty->assign('tplFilePath', $this->tplFilePath);
		$this->smarty->assign('inh_path', $this->parentPath);
	}

	/**
	 * Show an error to the user and dies
	 * This function shows an error to the user and die()s the process.
	 * @param string $msg The message to be shown
	 */
	public function dieError ($msg) {

		$this->smarty->append('_userErrorOutput', $msg);
		$this->dieDisplay();
	}

	/**
	 * Show an error to the user and dies while using ajax.
	 * This function shows an error to the user and die()s the process.
	 * @param string $msg The message to be shown
	 */
	public function dieErrorAjax ($msg) {

		$this->smarty->assign('error', $msg);
		$this->smarty->display(PATH_SMARTY_TPL . '/administrator/messageAjax.tpl', md5($_SERVER['REQUEST_URI']), md5(
				$_SERVER['REQUEST_URI']));
		die();
	}

	/**
	 * Show a message to the user and dies
	 * This function shows a message to the user and die()s the process.
	 * @param string $msg The message to be shown
	 */
	public function dieMsg ($msg) {
		$this->smarty->assign('_userMsgOutput', $msg);
		$this->dieDisplay();
	}

	/**
	 * Show a success-message to the user and dies
	 * @param string $msg The success-message to be shown
	 */
	public function dieSuccess($msg) {
		$this->smarty->assign('_userSuccessOutput', $msg);
		$this->dieDisplay();
	}

	public function showError ($msg) {
		$this->smarty->append('_userErrorOutput', $msg);
	}

	public function showWarning ($msg) {
		$this->smarty->append('_userWarningOutput', $msg);
	}

	public function showMsg ($msg) {
		$this->smarty->append('_userMsgOutput', $msg);
	}

	public function showSuccess ($msg) {
		$this->smarty->append('_userSuccessOutput', $msg);
	}

	public function flashDanger($msg, $title = '') {
		$this->smarty->append('_flashDanger', array(
			'msg' => $msg, 'title' => $title
		));
	}

	public function flashWarning($msg, $title = '') {
		$this->smarty->append('_flashWarning', array(
			'msg' => $msg, 'title' => $title
		));
	}

	public function flashInfo($msg, $title = '') {
		$this->smarty->append('_flashInfo', array(
			'msg' => $msg, 'title' => $title
		));
	}

	public function flashSuccess($msg, $title = '') {
		$this->smarty->append('_flashSuccess', array(
			'msg' => $msg, 'title' => $title
		));
	}
	/**
	 * dies and displays all messages which were used by showError and showMsg
	 */
	public function dieDisplay () {

		$this->smarty->display(PATH_SMARTY_TPL . '/administrator/message.tpl', md5($_SERVER['REQUEST_URI']), md5(
			$_SERVER['REQUEST_URI']));
		die();
	}

	/**
	 *
	 * @param string $promptMessage The Message shown to the User
	 * @param string $sectionString The String of the GET-Parameter section, used for Module-execution
	 * @param string $actionString The String of the GET-Parameter action, used for Function-execution in Modules
	 * @param string $confirmedString The String of the "confirmed"-Button
	 * @param string $notConfirmedString The String of the "notConfirmed"-Button
	 */
	public function confirmationDialog ($promptMessage, $sectionString, $actionString, $confirmedString, $notConfirmedString) {

		$this->smarty->assign('promptStr', $promptMessage);
		$this->smarty->assign('sectionStr', $sectionString);
		$this->smarty->assign('actionStr', $actionString);
		$this->smarty->assign('confirmedStr', $confirmedString);
		$this->smarty->assign('notConfirmedStr', $notConfirmedString);
		$this->smarty->display(PATH_SMARTY_TPL . '/administrator/confirmationDialog.tpl');
	}

	/**
	 * This function generates a general Form based on the Parameters given and shows it.
	 * It does not make sure if the Variables are right, so use it correctly!
	 * @param string $headString The Headline of the Form displayed to the User, something like "Add User"
	 * @param string $sectionString The Parameter of the $_GET-Variable 'section', containing information what module
	 * 	to use when form is send
	 * @param string $actionString The Parameter of the $_GET-Variable 'action', usually containing information about
	 * 	what action the module should do.
	 * 	If additional parameters are needed, attach them to this variable like this: 'actionStr&keyStr=varStr'
	 * @param array(array(string)) $inputContainer an array of an array of strings. For each array of strings the form
	 *  adds an 'input'-field which needs the following informations:
	 *  type: the type of the input-field - like text or password
	 *  displayName: The label that describes the inputfield
	 *  name: The name of the input-field, needed for Postprocessing the Variables
	 *  value: This variable is optional. if set, it sets the Value of the input-field to the String given.
	 *  optionString : This variable is optional. optionString is used for additional information like "checked" for
	 *   checkboxes.This string will be placed in the <input> field if set.
	 * @param string $submitString The content of the Submit-Button
	 */
	public function generalForm ($headString, $sectionString, $actionString, $inputContainer, $submitString) {

		$this->smarty->assign('headString', $headString);
		$this->smarty->assign('sectionString', $sectionString);
		$this->smarty->assign('actionString', $actionString);
		$this->smarty->assign('inputContainer', $inputContainer);
		$this->smarty->assign('submitString', $submitString);
		$this->smarty->display(PATH_SMARTY_TPL . '/administrator/generalForm.tpl');
	}

	public function backlink($link) {
		$this->smarty->assign('backlink', 'index.php?module=' . $link);
	}

	public static function escapeForJs($msg) {

		$msg = str_replace(array("\r\n", "\r", "\n"), "<br />", $msg);
		$msg = addslashes($msg);
		return $msg;
	}

	protected $smarty;

	/**
	 * The Path to the Parent-Smartyfile to inherit from
	 */
	protected $parentPath;

	/**
	 * The Path to the Smarty-Templates of the Module
	 */
	protected $tplFilePath;

	/**
	 * Workaround for the usage of global $smarty; to remove this, you need
	 * to change every Interface inheriting from this Class and every Module
	 * using these inheriting Interfaces
	 * @var Smarty
	 */
	public static $smartyHelper;

}
?>
