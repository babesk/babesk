<?php

require_once PATH_CODE . '/include/GeneralInterface.php';

/**
 * The Interface for the Subprogram PublicData. It does not predetermine a menu-
 * layout, only the basic ErrorOutputs, the whole displaying is set by the interfaces
 * of the modules, to allow for embedded displays etc
 */
class PublicDataInterface extends GeneralInterface {
	////////////////////////////////////////////////////////////////////////////////
	//Constructor
	////////////////////////////////////////////////////////////////////////////////
	/**
	 * Initializes the Class.
	 * @param $smarty If given, the class will use this variable as a smarty-Class.
	 * if not given, the class will initialize the complete Smarty-environment
	 */
	public function __construct ($smarty = false, $modRelativePath = false) {
		if (!$smarty) {
			$this->initSmarty ();
		}
		else {
			$this->_smarty = $smarty;
		}
		$this->_smartyModTemplates = PATH_SMARTY_TPL . '/publicData' . $modRelativePath;
		$this->_smartyParentTemplate = PATH_SMARTY_TPL . '/publicData/baseLayout.tpl';
		$this->_smarty->assign('inh_path', $this->_smartyParentTemplate);
	}

	////////////////////////////////////////////////////////////////////////////////
	//Getters and Setters
	////////////////////////////////////////////////////////////////////////////////
	public function getSmarty () {
		return $this->_smarty;
	}
	////////////////////////////////////////////////////////////////////////////////
	//Methods
	////////////////////////////////////////////////////////////////////////////////
	/**
	 * Show an error to the user and dies
	 * This function shows an error to the user and die()s the process.
	 * @param string $msg The message to be shown
	 */
	function dieError ($msg) {

		$this->_smarty->append('_userErrorOutput', $msg);
		$this->_smarty->display(PATH_SMARTY_TPL . '/publicData/message.tpl',
			md5($_SERVER['REQUEST_URI']), md5($_SERVER['REQUEST_URI']));
		die();
	}

	/**
	 * Show a message to the user and dies
	 * This function shows a message to the user and die()s the process.
	 * @param string $msg The message to be shown
	 */
	function dieMsg ($msg) {
		$this->_smarty->assign('_userMsgOutput', $msg);
		$this->_smarty->display(PATH_SMARTY_TPL . '/publicData/message.tpl',
			md5($_SERVER['REQUEST_URI']), md5($_SERVER['REQUEST_URI']));
		die();
	}

	function showError ($msg) {
		$this->_smarty->append('_userErrorOutput', $msg);
	}

	function showMsg ($msg) {
		$this->_smarty->append('_userMsgOutput', $msg);
	}

	/**
	 * dies and displays all messages which were used by showError and showMsg
	 */
	function dieDisplay () {
		$this->smarty->display(PATH_SMARTY_TPL . '/publicData/message.tpl',
			md5($_SERVER['REQUEST_URI']), md5(	$_SERVER['REQUEST_URI']));
	}

	////////////////////////////////////////////////////////////////////////////////
	//Implementations
	////////////////////////////////////////////////////////////////////////////////
	/**
	 * Initializes Smarty and the Variables of the program
	 */
	private function initSmarty () {
		require PATH_SMARTY . "/smarty_init.php";
		$this->_smarty = $smarty;
		$this->_smarty->assign('status', '');
		$version=@file_get_contents("../version.txt");
if ($version===FALSE) $version = "";
$smarty->assign('babesk_version', $version);
	}
	////////////////////////////////////////////////////////////////////////////////
	//Attributes
	////////////////////////////////////////////////////////////////////////////////
	protected $_smarty;
	/**
	 * The path to a template defining the structure to display
	 */
	private $_smartyParentTemplate;
}

?>
