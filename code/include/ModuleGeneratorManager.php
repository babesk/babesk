<?php

class ModuleGeneratorManager {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct($logger, $pdo) {

		$this->_logger = clone($logger);
		$this->_logger->categorySet('ModuleGeneratorManager');

		$this->_pdo = $pdo;
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function moduleRootGet() {
		return $this->_moduleRoot;
	}

	public function modulesLoad() {

		try {
			$data = $this->modulesFetchAll($this->_pdo);
			$moduleArray = $this->nestedSetToModules($data);
			//find root
			foreach($moduleArray as $mod) {
				if($mod->_name == 'root') {
					$this->_moduleRoot = $mod;
					return;
				}
			}
			throw new Exception('Root-Module not found!');

		} catch (Exception $e) {
			throw new ModulesException("Could not fetch Modules!", 1, $e);
		}
	}

	public function moduleExecute($command, $dataContainerForModule) {

		$module = $this->moduleByCommandGet($command);
		return $module->execute($command, $dataContainerForModule);
	}

	public function modulePathGet($module) {

		$path = $this->modulePathGetRecHelper(
			$module->_id,
			$this->_moduleRoot,
			'root');
		return rtrim($path, '/');
	}

	public function moduleByPathGet($path) {

		return $this->_moduleRoot->childByPathGet($path, true);
	}

	public function moduleByCommandGet($moduleCommand) {

		return $this->moduleByPathGet($moduleCommand->pathGet());
	}

	public function moduleGet($id) {

		return $this->_moduleRoot->anyChildByIdGet($id);
	}

	public function moduleEnableStatusAllowAll() {

		$this->_moduleRoot->allowAll();
	}

	public function moduleEnabledStatusChange($moduleId, $accessAllowed) {

		//rootmodule gets changed as reference
		$this->moduleEnabledStatusChangeHelper(
			$moduleId, $this->_moduleRoot, $accessAllowed);
	}

	public function modulesAsArrayGetAll() {

		return $this->_moduleRoot->moduleAsArrayGet();
	}

	/**
	 * Adds a new Module to the Database
	 *
	 * @param  string $parentPath The Module-path of the parent (with root)
	 * @param  object $newModule  ModuleGenerator the new Module to add
	 * @return int                The ID of the Module
	 */
	public function moduleAddNewToParent($parentPath, $newModule) {

		$parent = $this->moduleByPathGet($parentPath);

		if(!$parent) {
			throw new Exception("Parentmodule $parent not found!");
		}

		try {
			$stmt = $this->_pdo->prepare(
				'CALL moduleAddNew(
					?, ?, ?, ?, ?, @newModuleId);'
			);
			$stmt->execute(array(
				$newModule->_name,
				$newModule->_isEnabled,
				$newModule->_displayInMenu,
				$newModule->_executablePath,
				$parent->getId()
			));

			$stmt->closeCursor();   //Clear Buffer

			/*
			 * Get the ID of the new Module.
			 * We need to do this in a second query because of a MySQL-Bug.
			 */
			return $this->_pdo->query('SELECT @newModuleId')->fetchColumn();

		} catch (PDOException $e) {
			$msg = "Error adding the new module.";
			$this->_logger->log($msg, 'error', NULL,
				json_encode(array('error' => $e->getMessage())));
			throw new Exception($msg, 0, $e);
		}
	}

	/**
	 * Removes the Module and all its references by the given Module-ID
	 *
	 * @param  int    $moduleId
	 * @param  PDO    $pdo
	 * @throws Exception if something has gone wrong
	 */
	public function moduleRemove($module) {

		try {
			$this->linksOfModuleIdRemove($module->getId());
			$stmt = $this->_pdo->prepare("CALL moduleDelete(?)");
			$stmt->execute(array($module->getId()));

		} catch (PDOException $e) {
			$msg = "Could not remove the Module '$module->getId()' with all links.";
			$this->_logger->log($msg, 'error', NULL,
				json_encode(array('error' => $e->getMessage()))
			);
			throw new Exception($msg, 0, $e);
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function modulesFetchAll($pdo) {

		try {
			$stmt = $this->_pdo->query(
				'SELECT node.ID AS ID, node.lft AS lft,
					node.rgt AS rgt, node.name AS name, node.enabled AS enabled,
					node.executablePath AS executablePath,
					node.displayInMenu AS displayInMenu,
						(COUNT(parent.ID) - 1) AS level
				FROM SystemModules AS node, SystemModules AS parent
					WHERE node.lft BETWEEN parent.lft AND parent.rgt
				GROUP BY node.ID
				ORDER BY node.lft;');

			return $stmt->fetchAll();

		} catch (PDOException $e) {
			$msg = 'Error occured while fetching the modules.';
			$this->_logger->log($msg, 'Critical', NULL,
				json_encode(array('error' => $e->getMessage()))
			);
			throw new Exception($msg, 0, $e);
		}
	}

	/**
	 * Converts the nested set Array to modules
	 *
	 * Code shamelessly stolen from http://www.tutorials.de/php/312024-verschachteltes-array-aus-nested-sets.html and changed
	 *
	 * @param  Array $array An Array of Modules, each represented by
	 * 	another Array. These need to contain the following keys:
	 * ['lft', 'rgt', 'ID', 'name', 'enabled']
	 * @return Module the Module with all Childs
	 */
	protected function nestedSetToModules($array) {

		$struct = array();
		$level = 0;
		$helper =& $struct;

		foreach($array as $item) {

			if($level < $item['level']) {
				$keys = array_keys($helper);
				$buffer = $helper[$keys[count($keys)-1]];
				$helper =& $buffer->_childs;
			}

			else if($level > $item['level']) {
				$helper =& $struct;
				$i=0;
				while($i < $item['level']) {
					$keys = array_keys($helper);
					$buffer = $helper[$keys[count($keys)-1]];
					$helper =& $buffer->_childs;
					$i++;
				}
			}

			///@todo Add a constructor for ModuleGenerator that is less silly
			$helper[$item['ID']] = new ModuleGenerator(
				$item['ID'],
				$item['name'],
				(boolean) $item['enabled'],
				$item['rgt'],
				$item['lft'],
				$item['executablePath'],
				$item['displayInMenu']);
			$level = $item['level'];
		}

		return $struct;

	}

	protected function modulePathGetRecHelper($moduleId, $module,
		$begPath) {

		if(count($module->_childs)) {

			foreach($module->_childs as $mod) {
				$path = $begPath . '/' . $mod->_name;

				if($mod->_id == $moduleId) {
					return $path;
				}
				else {
					$retPath = $this->modulePathGetRecHelper(
						$moduleId,
						$mod,
						$path);
					if($retPath) {
						return $retPath;
					}
				}
			}
		}
		else {
			return false;
		}
	}

	protected function moduleEnabledStatusChangeHelper($searchedId,
		&$module, $access) {

		if($module->_id == $searchedId) {
			$module->_userHasAccess = $access;
			return true;
		}
		else {
			if($module->_childs != null) {
				foreach($module->_childs as &$child) {
					$ret = $this->moduleEnabledStatusChangeHelper($searchedId,
						$child, $access);
					if($ret) {
						$module->_userHasAccess = $access;
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Removes Entries in other Tables linking to the Module
	 *
	 * Logs on Error
	 *
	 * @param  int    $moduleId The Id of the module
	 * @throws PDOException If links could not be deleted
	 */
	protected function linksOfModuleIdRemove($moduleId) {

		try {
			$stmt = $this->_pdo->prepare(
				'DELETE FROM SystemGroupModuleRights WHERE moduleId = :moduleId'
			);

			$stmt->execute(array('moduleId' => $moduleId));

		} catch (PDOException $e) {
			$this->_logger->log(
				"Could not remove the Links of Module $moduleId");
			throw $e;
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	protected $_moduleRoot;

	/**
	 * Allows to log stuff
	 * @var Logger
	 */
	protected $_logger;

	protected $_pdo;
}

?>