<?php

namespace web\Settings\Account;

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_WEB . '/Settings/Settings.php';

class Account extends \Settings {

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);
		$lockAccount = filter_input(INPUT_GET, 'lockAccount');
		if($lockAccount == 'lockAccount') {
			$this->lockUserAccount($_SESSION['uid']);
			header('Location: index.php?action=logout');
		}
		else if($lockAccount == 'confirm') {
			$this->displayTpl('lockConfirmation.tpl');
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function entryPoint($dataContainer) {

		parent::entryPoint($dataContainer);
		parent::initSmartyVariables();
		$this->_smarty->assign(
			'inh_path', PATH_SMARTY_TPL . '/web/baseLayout.tpl'
		);
	}

	protected function lockUserAccount($user) {

		$stmt = $this->_pdo->prepare("UPDATE SystemUsers SET locked = 1 WHERE id = ?");
		$stmt->execute(array($user));
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

}

?>