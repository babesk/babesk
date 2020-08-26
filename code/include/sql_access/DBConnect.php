<?php

/**
 * Handles the connection to the Database
 * @author voelkerball
 *
 */
class DBConnect {

	////////////////////////////////////////////////////////////////////////////////
	//Attributes
	////////////////////////////////////////////////////////////////////////////////
	private $_host;
	private $_username;
	private $_password;
	private $_databaseName;
	private $_database;
	private $_databaseXML;
	private $_databaseXMLPath;
	private $_databaseXMLSafetyStringBeginning;
	private $_databaseXMLSafetyStringEnd;

	////////////////////////////////////////////////////////////////////////////////
	//Constructor
	////////////////////////////////////////////////////////////////////////////////
	public function __construct ($host = NULL, $username = NULL, $password = NULL, $databaseName = NULL) {

		$this->_databaseXMLPath = dirname(__FILE__) . '/databaseValues.php';
		$this->_databaseXMLSafetyStringBeginning = '<?php die("NO ACCESS");/**';
		$this->_databaseXMLSafetyStringEnd = '*/?>';

		if (isset($host, $username, $password, $databaseName)) {
			$this->initDatabase($host, $username, $password, $databaseName);
		}
	}

	////////////////////////////////////////////////////////////////////////////////
	//Getters and Setters
	////////////////////////////////////////////////////////////////////////////////
	public function getDatabase () {

		if (!$this->_database) {
			throw new Exception('Database was not initialized correctly by now');
		}
		return $this->_database;
	}

	public function getPdo() {

		$host = $this->_host;
		$username = $this->_username;
		$password = $this->_password;
		$databaseName = $this->_databaseName;
		try {
			$pdo = new PDO("mysql:host=$host;dbname=$databaseName",
				$username, $password);

		} catch (Exception $e) {
			throw new Exception('Could not connect to the Database!');
		}
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$pdo->exec("set names utf8");
		return $pdo;
	}

	public function setDatabaseValues ($host, $username, $password, $databaseName) {

		$this->_host = $host;
		$this->_username = $username;
		$this->_password = $password;
		$this->_databaseName = $databaseName;
	}

	public function setDatabaseXMLPath ($path) {

		$this->_databaseXMLPath = $path;
	}

	////////////////////////////////////////////////////////////////////////////////
	//Methods
	////////////////////////////////////////////////////////////////////////////////
	public function initDatabaseFromXML () {

		$this->loadDatabaseXML();
		$this->initDatabase($this->_host, $this->_username, $this->_password, $this->_databaseName);
	}

	public function initDatabase ($host, $username, $password, $databaseName) {

		$this->setDatabaseValues($host, $username, $password, $databaseName);
		$this->_database = new MySQLi($this->_host, $this->_username, $this->_password, $this->_databaseName);

		if (mysqli_connect_errno()) {
			throw new MySQLConnectionException(mysqli_connect_error());
		}
		if (!$this->_database) {
			throw new MySQLConnectionException('Error connecting to the MySQL-Server');
		}
	}

	/**
	 * @param boolean $recreateFileIfExists
	 * @return false if no XML-File was created, true if an XML-File was created / the existing one overwritten
	 */
	public function createDatabaseXML ($recreateFileIfExists) {

		if ($recreateFileIfExists || !validateDatabaseXML()) {

			$this->_databaseXML = new SimpleXMLElement('<mysqli></mysqli>');
			$xmlObjDatabase = $this->_databaseXML->addChild('database');

			$xmlObjDatabase->addChild('host', $this->_host);
			$xmlObjDatabase->addChild('name', $this->_databaseName);
			$xmlObjDatabase->addChild('username', $this->_username);
			$xmlObjDatabase->addChild('password', $this->_password);

			$xmlStr = $this->_databaseXML->asXML();

			file_put_contents($this->_databaseXMLPath, $this->_databaseXMLSafetyStringBeginning . $xmlStr . $this->
				_databaseXMLSafetyStringEnd);

			return true;
		}

		return false;
	}

	public function validateDatabaseXML () {

		libxml_use_internal_errors(true);
		if (!file_exists($this->_databaseXMLPath)) {
			return false;
		}

		if (!$databaseXML = new SimpleXMLElement($this->adaptXmlString())) {
			return false;
		}

		try {
			$dbObj = $this->getXMLObjDatabase($databaseXML);
		} catch (Exception $e) {
			return false;
		}

		// 		if (empty($dbObj->name) || empty($dbObj->host) || empty($dbObj->password) || empty($dbObj->username)) {
		// 			echo 'blubb';
		// 			return false;
		// 		}
		return true;
	}

	////////////////////////////////////////////////////////////////////////////////
	//Implementations
	////////////////////////////////////////////////////////////////////////////////
	private function loadDatabaseXML () {

		if (!$this->validateDatabaseXML()) {
			throw new Exception('Could not use the Database-XML-file');
		}

		$this->_databaseXML = new SimpleXMLElement($this->adaptXmlString());

		$xmlObjDatabase = $this->getXMLObjDatabase();

		$this->setDatabaseValues($xmlObjDatabase->host, $xmlObjDatabase->username, $xmlObjDatabase->password,
			$xmlObjDatabase->name);
	}

	private function adaptXmlString () {
		$fileString = file_get_contents($this->_databaseXMLPath);
		if (!$fileString) {
			die('Could not load the databaseXML!');
		}
		$fileString = str_replace($this->_databaseXMLSafetyStringBeginning, '', $fileString);
		$fileString = str_replace($this->_databaseXMLSafetyStringEnd, '', $fileString);
		$fileString = trim($fileString);
		return $fileString;
	}

	private function getXMLObjDatabase ($databaseXML = NULL) {

		if (isset($databaseXML)) {
			return $databaseXML->database;
		}
		else {
			return $this->_databaseXML->database;
		}
	}
}
?>
