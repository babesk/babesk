<?php

/**
 * This class contains Data used by the modules
 *
 * It is intended to solve the problem of global data (like the
 * smarty-variable) and is a "databridge" between the main-Class of the
 * Program which stores all the Information (here Administrator.php) and the
 * modules.
 *
 * @author Pascal Ernst <pascal.cc.ernst@googlemail.com>
 */
class DataContainer {
	////////////////////////////////////////////////////////////////////////
	//Constructor
	////////////////////////////////////////////////////////////////////////

	public function __construct (
		$smarty,
		$interface,
		$acl = NULL,
		$pdo = NULL,
		$logger = NULL
	) {

		$this->_smarty = $smarty;
		$this->_interface = $interface;
		$this->_acl = $acl;
		$this->_pdo = $pdo;
		$this->_logger = $logger;
	}

	////////////////////////////////////////////////////////////////////////
	//Getters and Setters
	////////////////////////////////////////////////////////////////////////
	public function getSmarty() {
		return $this->_smarty;
	}

	public function setSmarty($smarty) {
		$this->_smarty = $smarty;
	}

	public function setInterface($interface) {
		$this->_interface = $interface;
	}

	public function getInterface() {
		return $this->_interface;
	}

	public function getDatabase() {
		return $this->_db;
	}

	public function setDatabase($db) {
		$this->_db = $db;
	}

	public function getAcl() {
		return $this->_acl;
	}

	public function setAcl($acl) {
		$this->_acl = $acl;
	}

	public function getPdo() {
		return $this->_pdo;
	}

	public function setPdo($pdo) {
		$this->_pdo = $pdo;
	}

	public function getLogger() {
		return $this->_logger;
	}

	public function setLogger($logger) {
		$this->_logger = $logger;
	}

	public function getExecutionCommand() {
		return $this->_executionCommand;
	}

	public function setExecutionCommand($executionCommand) {
		$this->_executionCommand = $executionCommand;
	}

	////////////////////////////////////////////////////////////////////////
	//Methods
	////////////////////////////////////////////////////////////////////////

	////////////////////////////////////////////////////////////////////////
	//Implementations
	////////////////////////////////////////////////////////////////////////

	////////////////////////////////////////////////////////////////////////
	//Attributes
	////////////////////////////////////////////////////////////////////////

	/**
	 * Needed to use Smarty, an external Templating-Program for better
	 * separation between Programcode and Displaying Code
	 */
	protected $_smarty;
	protected $_interface;
	protected $_db;

	/**
	 * The AccessControlLayer
	 *
	 * @var Acl
	 */
	protected $_acl;

	/**
	 * Represents the Execution-Command suggesting what Module to be executed
	 * @var ModuleExecutionCommand
	 */
	protected $_executionCommand;

	/**
	 * The Database-Connection
	 * @var PDO
	 */
	protected $_pdo;


	/**
	 * Allows to Log Errors, notices and other stuff
	 * @var Logger
	 */
	protected $_logger;
}

?>
