<?php

class Web {

	///////////////////////////////////////////////////////////////////////
	//Constructor
	///////////////////////////////////////////////////////////////////////

	public function __construct () {

		if (!isset($_SESSION)) {
			require_once "../include/path.php";
			$this->initEnvironment();
			$this->initSmarty();
		}

		require_once PATH_ACCESS . '/UserManager.php';
		require_once PATH_INCLUDE . '/functions.php';
		require_once PATH_INCLUDE . '/DataContainer.php';
		require_once PATH_INCLUDE . '/TableMng.php';
		require_once PATH_INCLUDE . '/Acl.php';
		require_once PATH_INCLUDE . '/ModuleExecutionInputParser.php';
		require_once PATH_INCLUDE . '/Logger.php';
		require_once 'WebInterface.php';

		TableMng::init ();
		$this->_userManager = new UserManager();
		$this->_loggedIn = isset($_SESSION['uid']);
		$this->_interface = new WebInterface($this->_smarty);
		$this->initDatabaseConnections();
		$this->_logger = new Logger($this->_pdo);
		$this->_logger->categorySet('Web');
		$this->_acl = new Acl($this->_logger, $this->_pdo);
		$this->_moduleExecutionParser = new ModuleExecutionInputParser();
		$this->_moduleExecutionParser->setSubprogramPath('root/web');
		$this->initLanguage();
	}

	///////////////////////////////////////////////////////////////////////
	//Methods
	///////////////////////////////////////////////////////////////////////
	public function logOut() {

		$this->_loggedIn = false;
		session_destroy();
	}

	public function mainRoutine() {

		$this->checkForMaintenance();
		$this->handleLogin();
		$this->handleRedirect();
		$this->initUserdata();
		$this->loadModules();
		$this->_smarty->assign('babeskActivated',
			(boolean) $this->_acl->moduleGet('root/web/Babesk'));
		$userData = $this->_userManager->getUserdata($_SESSION['uid']);
		$this->checkFirstPassword();
		$this->display();
	}

	///////////////////////////////////////////////////////////////////////
	//Implementations
	///////////////////////////////////////////////////////////////////////
	private function initEnvironment() {

		ini_set('session.use_cookies', 1);
		ini_set('session.use_only_cookies', 0);
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
		date_default_timezone_set('Europe/Berlin');

		session_start();

		//if this value is not set, the modules will not execute
		define('_WEXEC', 1);
	}

	private function initSmarty() {

		require PATH_SMARTY . "/smarty_init.php";
		$this->_smarty = $smarty;
		$version=@file_get_contents("../version.txt");
		if ($version===FALSE) {
			$version = "";
		}
		$smarty->assign('babesk_version', $version);
		$relRoot = '../';
        $smarty->setTemplateDir('../smarty_templates');
		$smarty->assign('path_js', $relRoot . 'include/js');
		$smarty->assign('path_css', $relRoot . 'include/css');
		$smarty->assign('path_images', $relRoot . 'images');
		$this->_smarty->assign('error', '');
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

	/**
	 * Checks if the User has a preset Password and has not changed it yet
	 */
	private function checkFirstPassword() {

		$changePasswordOnFirstLoginEnabled = TableMng::query('SELECT value
			FROM SystemGlobalSettings WHERE `name` = "firstLoginChangePassword"');

		if ($changePasswordOnFirstLoginEnabled[0]['value'] == '1') {
			$userData = $this->_userManager->getUserdata ($_SESSION ['uid']);
			$firstPassword = $userData ['first_passwd'];

			if ($firstPassword != '0') {
				$modGen = $this->_acl->moduleGeneratorManagerGet();
				$this->_smarty->assign(
					'moduleGenMan', $modGen);
				$this->_smarty->assign('moduleroot',
					$modGen->moduleRootGet());

				$pwChange = new ModuleExecutionCommand(
					'root/web/Settings/ChangePresetPassword');
				$this->_acl->moduleExecute(
					$pwChange, $this->dataContainerCreate());
				die ();
			}
		}
	}

	/**
	 * handles if the user gets redirected after some seconds
	 */
	private function redirect() {

		try {
			$data = TableMng::query('SELECT gsDelay.value AS delay,
					gsTarget.value AS target
				FROM SystemGlobalSettings gsDelay, SystemGlobalSettings gsTarget
				WHERE gsDelay.name = "webHomepageRedirectDelay" AND
					gsTarget.name = "webHomepageRedirectTarget"');

		} catch (Exception $e) {
			return;
		}
		if(isset($data[0]['target']) && $data[0]['target'] != '') {
			$red = array (
				'time' => $data[0]['delay'],
				'target' => $data[0]['target']);
			$this->_smarty->assign('redirection', $red);
		}
	}

	private function initUserdata() {

		$userData = $this->_userManager->getUserdata($_SESSION['uid']);

		$this->addSessionUserdata($userData);
		$this->handleModuleSpecificData($userData);
		$this->loginTriesHandle($userData);
		if($userData['locked']) {
			session_destroy();
			$this->_smarty->display(PATH_SMARTY_TPL . '/web/login.tpl');
		}
		$this->addUserdataToSmarty();
	}

	/**
	 * Adds Session-vars containing data about the connected client
	 */
	private function addSessionUserdata($userData) {
		$_SESSION['username'] = $userData['forename'] . ' ' . $userData['name'];
		$_SESSION['last_login'] = $userData['last_login'];
		$_SESSION['login_tries'] = $userData['login_tries'];
		$_SESSION['IP'] = $_SERVER['REMOTE_ADDR'];
		$_SESSION['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
	}

	private function addUserdataToSmarty() {

		$this->_smarty->assign('uid', $_SESSION['uid']);
		$this->_smarty->assign('username', $_SESSION['username']);
		if(!empty($_SESSION['last_login'])) {
			$this->_smarty->assign(
				'last_login', formatDateTime($_SESSION['last_login']));
		}
	}

	/**
	 * check for new mail
	 */
	private function checkForMail() {

		try {
			$mailcount = TableMng::query(sprintf("SELECT COUNT(*) AS count
				FROM MessageReceivers mr
				LEFT JOIN MessageMessages m ON mr.messageId = m.ID
				WHERE %s = userId
					AND SYSDATE() BETWEEN m.validFrom AND DATE_ADD(validTo, INTERVAL 1 DAY)
					AND mr.read = 0",
				$_SESSION['uid']));

		} catch (MySQLVoidDataException $e) {
			return; //no new mails found

		} catch (Exception $e) {
			return; //No Emails found, maybe the tables do not exist
		}

		if ($mailcount[0]['count'] > 0) {
			$this->_smarty->assign('newmail', $mailcount[0]['count']);
		}
	}

	private function executeModule() {

		try {
			$command = $this->_moduleExecutionParser->executionCommandGet();
			$this->_smarty->assign(
				'activeHeadmodule', $command->moduleAtLevelGet(2)
			);
			$this->_acl->moduleExecute($command, $this->dataContainerCreate());

		} catch (AclException $e) {
			$this->_logger->log('Forbidden module-access-try by a user',
				'Notice', Null, json_encode(array(
					'msg' => $e->getMessage(), 'uid' => $_SESSION['uid']
			)));
			if($e->getCode() == 105) { //Module-Access forbidden
				$this->_interface->dieError(
					'Keine Zugriffsberechtigung auf dieses Modul!');
			}
			$this->_logger->log('Could not execute a module!',
				'Notice', Null, json_encode(array('msg' => $e->getMessage())));
			$this->_interface->dieError(_g('Error executing the Module!'));

		} catch (Exception $e) {
			$this->_logger->log('Could not execute a module!',
				'Notice', Null, json_encode(array('msg' => $e->getMessage())));
			$this->_interface->dieError(_g('Error executing the Module!'));
		}
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

	private function handleLogin() {

		if (!$this->_loggedIn) {
			$this->logIn();
			$this->redirect();
		}
	}

	private function logIn() {

		require_once 'Login.php';
		$loginManager = new Login($this->_smarty, $this->_pdo);
		if($loginManager->login()) {
			$this->_userManager->updateLastLoginToNow($_SESSION['uid']);
		}
	}

	private function displayCreditsWhenActive($userData) {

		//module-specific
		if (isset($userData['credit'])) {
			$_SESSION['credit'] = $userData['credit'];
			$this->_smarty->assign('credit', $_SESSION['credit']);
		}
	}

	private function loginTriesHandle($userData) {

		if ($_SESSION['login_tries'] > 3) {
			$this->_smarty->assign('login_tries', $_SESSION['login_tries']);
			$this->_userManager->ResetLoginTries($userData['ID']);
			$_SESSION['login_tries'] = 0;
		}
	}

	private function handleModuleSpecificData($userData) {

		$this->displayCreditsWhenActive($userData);
		$this->checkForMail();
	}

	private function loadModules() {

		try {
			$this->_acl->accessControlInit($_SESSION['uid']);

			$this->_smarty->assign(
				'modules', $this->headmodulesToDisplayGet()
			);

		} catch (AclException $e) {
			$this->_logger->log('user is not in any group',
				'error', Null,
				json_encode(array('msg' => $e->getMessage())));
			$this->_interface->dieError('Sie sind in keiner Gruppe und ' .
				'haben daher keine Rechte! Wenden sie sich bitte an den ' .
				'Administrator');
		}
	}

	/**
	 * Returns all Headmodules the user has access to and should be displayed
	 * @return array The headmodules
	 */
	private function headmodulesToDisplayGet() {

		$webModule = $this->_acl->moduleGet('root/web');
		if(!$webModule) {
			$this->_logger->log(
				'a user tried to access web without rights!',
				'Notice', Null, json_encode(
					array('userId' => $_SESSION['uid'])
			));
			$this->_interface->dieError(_g('You have no access to web!'));
		}
		$childs = $webModule->getChilds();
		$headmodsToDisplay = array();
		foreach($childs as $child) {
			if($child->isDisplayInMenuAllowed()) {
				$headmodsToDisplay[] = $child;
			}
		}
		return $headmodsToDisplay;
	}

	private function handleRedirect() {
		if (isset($_GET ['webRedirect'])) { //redirect to a module
			$this->redirect();
		}
	}

	private function display() {

		// $this->_smarty->assign('moduleroot', $this->_acl->getModuleroot());
		$this->_smarty->assign(
			'moduleGenMan', $this->_acl->moduleGeneratorManagerGet());
		if ($this->_moduleExecutionParser->load()) {
			$this->executeModule();
		}
		else {
			$birthday = date("m-d",strtotime($this->_userManager->getBirthday($_SESSION['uid'])));

			$this->_smarty->assign('birthday',$birthday);
			$this->_smarty->display(PATH_SMARTY_TPL . '/web/main_menu.tpl');
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
			clone($this->_interface),
			clone($this->_acl),
			$this->_pdo,
			clone($this->_logger));

		return $dataContainer;
	}

	private function checkForMaintenance() {

		$settings = $this->_em->getRepository(
				'DM:SystemGlobalSettings'
			)->findOneByName('siteIsUnderMaintenance');
		if($settings) {
			if($settings->getValue() == 1) {
				$this->_interface->setBacklink(false);
				$this->_interface->dieMessage(
					'Die Seite wird momentan überarbeitet. Versuche es ' .
					'später nochmal!'
				);
			}
		}
	}

	///////////////////////////////////////////////////////////////////////
	//Attributes
	///////////////////////////////////////////////////////////////////////

	/**
	 * The Prefix to the location where the Images are
	 * @var string
	 */
	private $_imagepathPrefix = '../images/moduleBackgrounds/';

	/**
	 * Allows to log errors and other things
	 * @var Logger
	 */
	private $_logger;

	private $_pdo;

	private $_smarty;

	private $_loggedIn;

	private $_interface;

	private $_userManager;

	private $_acl;

	private $_moduleExecutionParser;

}

?>
