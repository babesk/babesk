<?php

abstract class Module {

	////////////////////////////////////////////////////////////////////////
	//Constructor
	////////////////////////////////////////////////////////////////////////

	public function __construct ($name, $display_name, $path) {
		$this->name = $name;
		$this->relPath = $path;
		$this->executablePath = $path . $this->name . '.php';
		$this->displayName = $display_name;
	}

	////////////////////////////////////////////////////////////////////////
	//Getters and Setters
	////////////////////////////////////////////////////////////////////////

	public function getName () {
		return $this->name;
	}

	public function getDisplayName () {
		return $this->displayName;
	}

	////////////////////////////////////////////////////////////////////////
	//Methods
	////////////////////////////////////////////////////////////////////////


	public function execute ($dataContainer) {

		require $this->executablePath;
	}

	public function initAndExecute($dataContainer) {

		$dataContainer = $this->preExecution($dataContainer);
		// $this->debugDataPrint($dataContainer);
		$this->execute($dataContainer);
	}

	////////////////////////////////////////////////////////////////////////
	//Implementations
	////////////////////////////////////////////////////////////////////////

	protected function preExecution($dataContainer) {

		return $dataContainer;
	}

	protected function entryPoint($dataContainer) {

		$this->_pdo = $dataContainer->getPdo();
		$this->_smarty = $dataContainer->getSmarty();
		$this->_acl = $dataContainer->getAcl();
		$this->_submoduleExecutionpath =
			$dataContainer->getExecutionCommand()->pathGet();
		$this->_modExecCommand = $dataContainer->getExecutionCommand();
		$this->_logger = clone($dataContainer->getLogger());
		$this->_logger->categorySet($this->_modExecCommand->pathGet());
		$this->_interface = $dataContainer->getInterface();
		$this->_dataContainer = $dataContainer;
	}

	protected function debugDataPrint($dataContainer) {

		try {
			echo 'DebugData:<pre>';
			echo 'ModuleName: ' . $this->name . '<br />';
			echo 'ExecutionRequest: ';
			var_dump($dataContainer->getExecutionCommand()->pathGet());
			if($dataContainer->getExecutionCommand()->pathGet()) {
				echo 'ModulePosition: ';
				var_dump($this->modulePositionInExecutionPathGet(
					$dataContainer->getExecutionCommand()->pathGet()));
				echo 'SubmoduleCount: ';
				var_dump($this->submoduleCountGet($dataContainer->getExecutionCommand()->pathGet()));
			}

		} catch (SubmoduleException $e) {
			echo 'NOPE';
		}
		echo '</pre>';
	}

	/**
	 * Initializes some Smarty-Variables to display the Website
	 *
	 * Should get called by the Subclass using it since most Modules used
	 * Interfaces specific to them so they dont use and have Smarty
	 */
	protected function initSmartyVariables() {

		$subprogram = $this->_modExecCommand->subprogramGet();
		$this->_smartyModuleTemplatesPath =
			PATH_SMARTY_TPL . '/' . $subprogram . $this->relPath;

		$siteHeaderPath = $this->_smartyModuleTemplatesPath . 'header.tpl';
		$this->_smarty->assign('inh_path', $siteHeaderPath);
	}

	protected function moduleTemplatePathSet($customPath = false) {

		if($customPath === false) {
			$relpath = $this->relPath;
		}
		else {
			$relpath = $customPath;
		}
		$subprogram = $this->_modExecCommand->subprogramGet();
		$this->_smartyModuleTemplatesPath =
			PATH_SMARTY_TPL . '/' . $subprogram . $relpath;
	}

	/**
	 * Displays a Templatefile which is in the standard templatePath
	 *
	 * @param  string $tplName the Name of the Template-File
	 */
	protected function displayTpl($tplName) {

		$path = $this->_smartyModuleTemplatesPath . $tplName;
		try {
			$this->_smarty->display($path);

		} catch (SmartyException $e) {
			$errorMsg = 'Could not display a Smarty-Template.';
			if(!file_exists($path)) {
				$errorMsg .= ' File does not exist.';
			}
			$this->_logger->log($errorMsg, 'error', 'Smarty',
				json_encode(array('path' => $path,
					'error' => $e->getMessage())));
			throw $e;
		}
	}

	/**
	 * Checks if the Level of Submodule exists in the SubmoduleExecutionstring
	 * @param  integer $level The Level of Submodule
	 * (1 for the first Submodule)
	 * @return boolean True of it exists, else false
	 */
	protected function execPathHasSubmoduleLevel($level, $path) {

		if($path) {
			$elements = explode('/', $path);
			$submodules = count($elements) - 4;
			return $level <= $submodules;
		}
		else {
			return false;
		}
	}

	/**
	 * Executes a Submodule by calling a Method
	 *
	 * The name of the called Method begins with submodule, goes on with the
	 * modules name and ends with Execute. For Example submoduleUserExecute()
	 *
	 * @param  String $path The Path to the Submodule, beginning from the
	 *                      moduleroot
	 * @param  int sublevel The level of the Submodule (The first submodule
	 *                   is at 1)
	 * @param  string prefix The Prefix of the Methodname to execute
	 * @param  string postfix The Postfix of the Methodname to execute
	 * @return ???    Returns the value that the Submodule returns
	 */
	protected function submoduleExecuteAsMethod($path, $sublevel = 1,
		$prefix = 'submodule', $postfix = "Execute") {

		$pos = $this->modulePositionInExecutionPathGet($path);
		$submodPos = $pos + $sublevel;
		//Remove not wanted deeper submodules
		$mods = explode('/', $path);
		$slicedPath = implode('/', array_splice($mods, 0, $submodPos + 1));

		$submodule = $this->_acl->moduleGet($slicedPath);
		if($submodule) {
			$methodName = $prefix . $submodule->getName() . $postfix;
			if(method_exists($this, $methodName)) {
				return $this->$methodName();
			}
			else {
				throw new SubmoduleNotExistingException(
					_g("Submodule-Method $methodName does not exist."));
			}
		}
		else {
			throw new SubmoduleAccessDeniedException(
				"Access to this Module not allowed!");
		}
	}

	/**
	 * Gets the Count of submodule-levels of this Object in the ModulePath
	 *
	 * All Modules that are in the moduleExecutionPath and hierarchially under
	 * this module will be counted.
	 *
	 * @param  string $moduleExecutionPath The Module Execution Path to check
	 * @return int                         The count of the submodules
	 * @throws ModuleException If This Module does not exist in the
	 *         moduleExecutionPath
	 */
	protected function submoduleCountGet($moduleExecutionPath) {

		// $pos = $this->modulePositionInExecutionPathGet($moduleExecutionPath);

		$modulesOnlyPath = $this->moduleExecutionPathStripToModulesOnly(
			$moduleExecutionPath);
		$moduleClassPath = $this->ancestorModulePathGet();

		$strippedPath = str_replace($moduleClassPath, '', $modulesOnlyPath,
			$replaceCount);
		$strippedPath = ltrim($strippedPath, '/');

		if(!$replaceCount < 1) {
			if(empty($strippedPath)) {
				return 0;
			}
			else {
				return count(explode('/', $strippedPath));
			}
		}
		else {
			throw new SubmoduleException('Module does not exist in the ' .
				'Executionpath, cant find a startingpoint to count the ' .
				'Submodules');
		}
	}

	/**
	 * Strips the ModuleExecutionPath of the not-Moduly part
	 *
	 * The ModuleExecutionPath gets preceded by the root-element
	 * (conveniently named root) and the Program-Part (web or administrator).
	 * This function removes them.
	 *
	 * @param  string $path The whole ModuleExecutionPath
	 * @return string       The Path without the Non-Module preceding elements
	 */
	private function moduleExecutionPathStripToModulesOnly($path) {

		$levels = explode('/', $path);

		for($i = 0; $i < 2; $i++) {
			array_shift($levels);
		}

		return implode('/', $levels);
	}

	/**
	 * Returns the Position of this Module in the ModuleExecutionPath
	 *
	 * Counting starts from 0 (the Element root is at position 0)
	 *
	 * @param  string $execPath The ExecutionPath
	 * @return int              The position of the Module in the Executionpath
	 *                          returns false on Error. Be aware that it also
	 *                          can return zero, so check for false correctly!
	 */
	protected function modulePositionInExecutionPathGet($execPath) {

		$modulePath = $this->ancestorModulePathGet();

		if(strpos($execPath, $modulePath) !== false) {
			/**
			 * The position of the Module is the Count of its parents and
			 * itself added with 2, since the Executionpath has 2 Elements
			 * preceding the modules (root/administrator or root/web)
			 * minus one to begin the count from 0 instead of one
			 */
			$position = count(explode('/', $modulePath)) + 1;

			return $position;
		}
		else {
			return false;
		}
	}

	private function ancestorModulePathGet() {

		$classes = array_reverse($this->ancestorsGet());
		array_shift($classes);   // We dont want the "Module"-Class
		$modulePath = implode('/', $classes);

		return $modulePath;
	}

	private function executionPathSliceToLevels($path, $levelOfSubMod) {

		//levelOfSubMod starts from the Submodule, 4 levels under root
		$levelOfMod = $levelOfSubMod + 4;
		$levels = explode('/', $path);
		if(count($levels) < $levelOfMod) {
			return false;
		}
		$levelsWanted = array_slice($levels, 0, $levelOfMod);
		$executePath = implode('/', $levelsWanted);
		return $executePath;
	}


	/**
	 * Returns all Ancestors of the Module and itself starting by the lowest
	 *
	 * @return array  The Ancestors and the Class itself
	 */
	private function ancestorsGet() {

		$class = get_class($this);

		for($classes[] = $class;
			$class = get_parent_class($class);
			$classes[] = $class);

		return $classes;
	}

	////////////////////////////////////////////////////////////////////////
	//Attributes
	////////////////////////////////////////////////////////////////////////

	protected $name;
	protected $relPath;
	protected $displayName;
	protected $executablePath;

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
