<?php

require_once "../include/path.php";

require_once PATH_INCLUDE . '/TableMng.php';
require_once PATH_INCLUDE . '/Acl.php';
require_once PATH_INCLUDE . "/functions.php";
require_once PATH_INCLUDE . '/exception_def.php';
require_once PATH_INCLUDE . '/DataContainer.php';
require_once PATH_INCLUDE . '/ModuleExecutionInputParser.php';
require_once PATH_INCLUDE . '/ArrayFunctions.php';
require_once PATH_INCLUDE . '/sql_access/DBConnect.php';
require_once PATH_INCLUDE . '/Logger.php';
require_once 'Login.php';
require_once 'AdminInterface.php';

/**
 *
 */
class Administrator {

	////////////////////////////////////////////////////////////////////////
	//Constructor
	////////////////////////////////////////////////////////////////////////

	public function __construct() {

		if(!isset($_SESSION)) {
			$this->initEnvironment();
		}
		else if(!validSession()) {
			die(_g('The session is invalid, please login again'));
		}
		try {
			$this->initSmarty();
			TableMng::init();
			$this->_adminInterface = new AdminInterface(NULL, $this->_smarty);
			// AdminInterface has used global $smarty, workaround
			AdminInterface::$smartyHelper = $this->_smarty;
			$this->_moduleExecutionParser = new ModuleExecutionInputParser();
			$this->_moduleExecutionParser->setSubprogramPath(
				'root/administrator');
			$this->loadVersion();
			$this->initDatabaseConnections();
			$this->_logger = new Logger($this->_pdo);
			$this->_logger->categorySet('Administrator');
			$this->_acl = new Acl($this->_logger, $this->_pdo);
		}
		catch(MySQLConnectionException $e) {
			die('Sorry, could not connect to the database.');
		}
	}

	////////////////////////////////////////////////////////////////////////
	//Methods
	////////////////////////////////////////////////////////////////////////

	public function run() {

		$login = new Login($this->_smarty, $this->_pdo, $this->_logger);
		if($login->loginCheck()) {
			$this->accessControlInit();
			$this->initDisplayingModules();
			$this->initUserInterface();
			$this->adminBookmarks();
			if($this->_moduleExecutionParser->load()) {
				$this->backlink();
				$this->moduleBacklink();
				$this->executeModule();
			}
			else {
				$this->MainMenu();
			}
		}
		else {
			die('Not logged in');
		}
	}

	public function initUserInterface() {

		$this->_smarty->assign('username', $_SESSION['username']);
		$this->_smarty->assign('sid', htmlspecialchars(SID));

		$this->_smarty->assign('base_path',
			PATH_SMARTY_TPL . '/administrator/base_layout.tpl');

	}

	public function executeModule() {

		try {
			$execCom = $this->_moduleExecutionParser->executionCommandGet();
			$this->_smarty->assign('moduleExecCommand', $execCom);
			$genManager = $this->_acl->moduleGeneratorManagerGet();
			$module = $genManager->moduleByPathGet($execCom->pathGet());
			if($module) {
				$this->_smarty->assign('moduleExecutedId', $module->getId());
			}
			$this->_acl->moduleExecute(
				$execCom, $this->dataContainerCreate()
			);
		} catch(AclAccessDeniedException $e) {
			if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
				dieHttp('Keine Berechtigung', 401);
			}
			else {
				$this->_adminInterface->dieError('Keine Berechtigung!');
			}
		} catch(AclModuleLockedException $e) {
			if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
				dieHttp('Modul gesperrt', 423);
			}
			else {
				$this->_adminInterface->dieError('Modul gesperrt');
			}
		} catch(Exception $e) {
			$this->_logger->log(
				'Error executing a Module', 'Notice', Null,
				json_encode(array(
					'command' => $execCom->pathGet(),
					'userId' => $_SESSION['UID'],
					'exceptionType' => get_class($e),
					'msg' => $e->getMessage()
			)));

			if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
				http_response_code(500);
				//It was an Ajax-Call, dont show the whole Website
				die('Konnte das Modul nicht ausführen!');
			}
			else {
				$this->_adminInterface->dieError(
					'Konnte das Modul nicht ausführen!');
			}
		}
	}

	public function MainMenu() {

		$adminModule = $this->_acl->moduleGet('root/administrator');
		$this->_smarty->display(
			PATH_SMARTY_TPL . '/administrator/menu.tpl'
		);
	}


	////////////////////////////////////////////////////////////////////////
	//Implementations
	////////////////////////////////////////////////////////////////////////

	private function initEnvironment() {

		$this->setPhpIni();
		$this->initLanguage();

		//if this value is not set, the modules will not execute
		define('_AEXEC', 1);

		session_name('sid');
		session_start();
		error_reporting(E_ALL);
		date_default_timezone_set('Europe/Berlin');
	}

	private function initLanguage() {

		$language = 'de_DE.utf-8';
		$domain = 'messages';

		putenv("LANG=$language");
		setlocale(LC_ALL, $language);
		// Set the text domain as 'messages'
		bindtextdomain($domain, PATH_CODE . '/locale');
		bind_textdomain_codeset($domain, "UTF-8");
		textdomain($domain);
	}

	private function initSmarty() {

		require_once PATH_SMARTY . "/smarty_init.php";

		$this->_smarty = $smarty;
		$this->_smarty->assign('status', '');

		$relRoot = '../';
// 		$smarty->assign('path_smarty_tpl', $relRoot . 'smarty_templates');
		$smarty->assign('path_smarty_tpl', PATH_SMARTY_TPL);
		$smarty->assign('path_js', $relRoot . 'include/js');
		$smarty->assign('path_css', $relRoot . 'include/css');

		$version=@file_get_contents("../version.txt");
		if ($version===FALSE) $version = "";
		$smarty->assign('babesk_version', $version);
		$smarty->assign('https_enabled', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'); 
	}

	/**
	 * Initializes the PDO-Object, used for Database-Queries
	 *
	 * triggers an error when
	 */
	private function initDatabaseConnections() {

		try {
			$connector = new DBConnect();
			$connector->initDatabaseFromXML();
			$this->_pdo = $connector->getPdo();
			$this->_em = $connector->getDoctrineEntityManager();
			$this->_pdo->query(
				'SET @activeSchoolyear :=
				(SELECT ID FROM SystemSchoolyears WHERE active = "1" LIMIT 1);
			');

		} catch (PDOException $e) {
			echo $e->getMessage();
			die("Sorry, could not connect to the database with pdo.");
		}
	}

	private function setPhpIni() {

		ini_set('display_errors', 1);
		ini_set('session.use_cookies', 1);
		ini_set('session.use_only_cookies', 0);
		ini_set("default_charset", "utf-8");
	}

	private function initDisplayingModules() {

		$adminModule = $this->_acl->moduleGet('root/administrator');

		if($adminModule) {
			$this->_smarty->assign('is_mainmenu', true);
			$this->_smarty->assign('headmodules', $adminModule->getChilds());
			$this->_smarty->assign(
				'moduleGenMan', $this->_acl->moduleGeneratorManagerGet());
		}
		else {
			$this->_logger->log('Administrator-Layer access denied.',
				'Notice', null, json_encode(array(
					'userId' => $_SESSION['UID']))
			);
			$this->_smarty->assign('status',
				'Account hat keine Admin-Berechtigung');
			
			$this->_smarty->display(
				PATH_SMARTY_TPL . '/administrator/login.tpl'
			);
			die();
		}
	}

	private function loadVersion() {

		$version = '';

		if(file_exists('../version.txt')) {
			$version = file_get_contents('../version.txt');
		}
		$this->_smarty->assign('babesk_version', $version);
	}

	private function accessControlInit() {

		try {
			$this->_acl->accessControlInit($_SESSION['UID']);

		} catch(AclException $e) {
			if($e->getCode() == 104) {
				$this->_smarty->assign('status',
					'Account hat keine Admin-Berechtigung');
				$this->_smarty->display(
					PATH_SMARTY_TPL . '/administrator/login.tpl'
				);
				die();
			}
			else {
				$this->_adminInterface->dieError(
					'Konnte den Zugriff nicht einrichten!');
			}
		}
	}

	/**
	 * Retrieves the bookmarks for the admin user
	 */
	private function adminBookmarks() {

		try {
			$stmt = $this->_pdo->prepare(
				'SELECT bmid, (
						SELECT GROUP_CONCAT(parent.name ORDER BY parent.lft ASC SEPARATOR "|")
						FROM SystemModules AS node,
							SystemModules AS parent
						WHERE node.lft BETWEEN parent.lft AND parent.rgt
							AND node.ID = mid
					) AS modulePath
				FROM SystemAdminBookmarks WHERE uid = :userId
				-- Order it so we dont need to order manually in PHP or Smarty
				ORDER BY bmid'
			);

			$stmt->execute(array('userId' => $_SESSION['UID']));
			$bookmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$this->_smarty->assign('bookmarks', $bookmarks);

		} catch (PDOException $e) {
			$this->_logger->log('Error fetching the bookmarks',
				'Notice', Null, json_encode(array(
					'msg' => $e->getMessage(),
					'user' => $_SESSION['UID']))
			);
		}
	}

	/**
	 * Adds an "Back to Module"-Link, useful if header-link couldnt be shown
	 */
	private function moduleBacklink() {

		$modCommand = clone(
			$this->_moduleExecutionParser->executionCommandGet());
		$modCommand->delim = '|';
		$link = $modCommand->pathGet();
		$this->_smarty->assign('moduleBacklink', $link);
	}

	/**
	 * Modules can set a manual backlink, handle it if set
	 */
	private function backlink() {

		if(!empty($_SESSION['backlink'])) {
			$this->_smarty->assign('backlink', $_SESSION['backlink']);
			unset($_SESSION['backlink']);
		}
	}

	/**
	 * Creates a DataContainer and returns it
	 * @return Object DataContainer A Container containing general data needed
	 *                by the Modules
	 */
	private function dataContainerCreate() {

		$dataContainer = new DataContainer(
			$this->_smarty,
			clone($this->_adminInterface),
			clone($this->_acl),
			$this->_pdo,
			$this->_em,
			clone($this->_logger)
		);

		return $dataContainer;
	}

	////////////////////////////////////////////////////////////////////////
	//Attributes
	////////////////////////////////////////////////////////////////////////

	/**
	 * The Access-Control-Layer
	 */
	private $_acl;

	/**
	 * Doctrines entity Manager
	 * @var EntityManager
	 */
	private $_em;

	/**
	 * The Interface handling displaying stuff
	 * @var AdminInterface
	 */
	private $_adminInterface;

	/**
	 * If the User is logged in or not
	 * @var boolean
	 */
	private $_userLoggedIn;

	/**
	 * The Smarty-Object
	 * @var Smarty
	 */
	private $_smarty;

	/**
	 * To log things
	 * @var Logs
	 */
	private $_logger;

	private $_login;

	private $_moduleExecutionParser;

	private $_pdo;
}

?>
