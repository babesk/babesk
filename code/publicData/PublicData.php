<?php

require_once "../include/path.php";
require_once 'PublicDataInterface.php';
require_once PATH_INCLUDE . '/DataContainer.php';
require_once PATH_INCLUDE . '/Acl.php';
require_once PATH_INCLUDE . '/TableMng.php';
require_once PATH_INCLUDE . '/exception_def.php';
require_once PATH_INCLUDE . '/ModuleExecutionInputParser.php';
require_once PATH_INCLUDE . '/Logger.php';

/**
 * This Class organizes the Sub-program "publicData"
 * @author Pascal Ernst <pascal.cc.ernst@gmail.com>
 */
class PublicData {
	///////////////////////////////////////////////////////////////////////
	//Constructor
	///////////////////////////////////////////////////////////////////////

	public function __construct () {
		TableMng::init();
		$this->environmentInit ();
	}

	///////////////////////////////////////////////////////////////////////
	//Getters and Setters
	///////////////////////////////////////////////////////////////////////

	///////////////////////////////////////////////////////////////////////
	//Methods
	///////////////////////////////////////////////////////////////////////

	public function publicDataEntrypoint() {

		try {
			$this->_moduleExecutionParser = new ModuleExecutionInputParser();
			$this->_moduleExecutionParser->setSubprogramPath(
				'root/PublicData');
			if($this->_moduleExecutionParser->load()) {
				$this->_acl->accessControlInitAllowAll();
				$this->_acl->moduleExecute(
					$this->_moduleExecutionParser->executionCommandGet(),
					$this->_dataContainer);
			}
			else {
				die('Kein Modul zum Ausführen übergeben!');
			}

		} catch (Exception $e) {
			$this->_interface->dieError('Konnte Modul nicht ausführen');
		}
	}

	///////////////////////////////////////////////////////////////////////
	//Implementations
	///////////////////////////////////////////////////////////////////////
	private function environmentInit () {
		$this->phpIniSet();
		$this->sessionInit ();
		$this->attributesInit ();
		//if this value is not set, the modules will not execute
		define('_AEXEC', 1);
		error_reporting(E_ALL);
		date_default_timezone_set('Europe/Berlin');
	}

	private function attributesInit () {
		$this->_interface = new PublicDataInterface ();
		// $this->_moduleManager = new ModuleManager ('publicData', $this->_interface);
		// $this->_moduleManager->setDataContainer ($this->_dataContainer);
		// $this->_moduleManager->allowAllModules ();
		$this->initDatabaseConnections();
		$this->_logger = new Logger($this->_pdo);
		$this->_logger->categorySet('PublicData');
		$this->_acl = new Acl($this->_logger, $this->_pdo);
		$this->_dataContainer = new DataContainer (
			$this->_interface->getSmarty (),
			$this->_interface,
			$this->_acl,
			$this->_pdo,
			$this->_em,
			$this->_logger
		);
	}

	private function sessionInit () {
		session_name('sid');
		session_start();
	}

	private function phpIniSet () {
		ini_set('display_errors', 1);
		ini_set('session.use_cookies', 1);
		ini_set('session.use_only_cookies', 0);
		ini_set("default_charset", "utf-8");
	}

	/**
	 * Initializes the PDO-Object, used for Database-Queries
	 *
	 * triggers an error when the PDO-Object could not be created
	 */
	private function initDatabaseConnections() {

		try {
			$connector = new DBConnect();
			$connector->initDatabaseFromXML();
			$this->_pdo = $connector->getPdo();
			$this->_pdo->query('SET @activeSchoolyear :=
				(SELECT ID FROM SystemSchoolyears WHERE active = "1");');

		} catch (Exception $e) {
			trigger_error('Could not create the PDO-Object!');
		}
	}

	///////////////////////////////////////////////////////////////////////
	//Attributes
	///////////////////////////////////////////////////////////////////////
	private $_interface;
	private $_dataContainer;

	private $_acl;

	private $_pdo;

	private $_logger;

	private $_moduleExecutionParser;
}

?>
