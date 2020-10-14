<?php

namespace administrator\System\User\DisplayAll\Multiselection\Actions;

/**
 * Baseclass for an ActionHandler, a class that does something with userdata
 */
abstract class Action {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct($dataContainer) {

		$this->_pdo = $dataContainer->getPdo();
		$this->_smarty = $dataContainer->getSmarty();
		$this->_acl = $dataContainer->getAcl();
		$this->_submoduleExecutionpath =
			$dataContainer->getExecutionCommand()->pathGet();
		$this->_modExecCommand = $dataContainer->getExecutionCommand();
		$this->_logger = clone($dataContainer->getLogger());
		$this->_interface = $dataContainer->getInterface();
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function actionExecute($clientData) {

		if(isset($clientData['_multiselectionSelectedOfUsers']) &&
			count($clientData['_multiselectionSelectedOfUsers'])) {
			$this->execute($clientData);
		}
		else {
			$this->dieError('Keine Nutzer ausgewählt!');
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	abstract protected function execute($clientData);

	protected function dieSuccess($msg) {
		$this->dieStatus($msg, 'success');
	}

	protected function dieError($msg) {
		$this->dieStatus($msg, 'error');
	}

	protected function dieStatus($msg, $status) {
		die(json_encode(array('value' => $status,
			'message' => $msg)));
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	/**
	 * The Smarty-Object used to display Information to the User
	 * @var Smarty
	 */
	protected $_smarty;

	/**
	 * Contains the Path to the Templates-folder of this module
	 * @var string
	 */
	protected $_smartyModuleTemplatesPath;

	/**
	 * The Connection to the Database
	 * @var Pdo
	 */
	protected $_pdo;


	/**
	 * Allows for checking if the User has access to the Submodules
	 * @var Acl
	 */
	protected $_acl;

	/**
	 * Allows for logging problems or notices to the Database
	 * @var Logger
	 */
	protected $_logger;

	/**
	 * Wraps what Module should be executed
	 * @var Object ModuleExecutionCommand
	 */
	protected $_modExecCommand;

	/**
	 * Allows easy displaying of errors, messages, ...
	 * @var Interface
	 */
	protected $_interface;

	protected $_submoduleExecutionpath;
}

?>