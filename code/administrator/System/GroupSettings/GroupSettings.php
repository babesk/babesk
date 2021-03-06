<?php

require_once PATH_INCLUDE . '/Group.php';
require_once PATH_ADMIN . '/AdminInterface.php';
require_once PATH_ADMIN . '/System/System.php';

class GroupSettings extends System {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct($name, $display_name, $path) {

		parent::__construct($name, $display_name, $path);
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);

		if(isset($_GET['action']) && 'POST' == $_SERVER['REQUEST_METHOD']) {
			switch($_GET['action']) {
				case 'groupsFetch':
					$this->groupsAllGet();
					break;
				case 'groupsChange':
					$this->groupsChange();
					break;
				case 'modulesFetch':
					$this->modulesFetch();
					break;
				case 'rightChange':
					$this->modulerightStatusChange();
					break;
				default:
					die('Wrong action-value!');
					break;
			}
		}
		else {
			$this->_smarty->display(
				PATH_SMARTY_TPL . "/administrator/$this->relPath/" .
				"main.tpl");
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	/**
	 * The entry-Point of the Module, when it gets executed
	 *
	 * @param  DataContainer $dataContainer a instance containing general data
	 */
	protected function entryPoint($dataContainer) {

		defined('_AEXEC') or die('Access denied');

		parent::entryPoint($dataContainer);
		$this->_smarty = $dataContainer->getSmarty();
		$this->_acl = $dataContainer->getAcl();
		$this->_interface = new AdminInterface($this->relPath,
			$this->_smarty);
	}

	/**
	 * Fetches all Groups and Outputs them for the JS-Script
	 */
	protected function groupsAllGet() {

		$array = $this->_acl->getGrouproot()->groupAsArrayGet();
		$formatted = $this->groupsFormatForJstree($array);
		die(json_encode(array('value' => 'success',
			'data' => $formatted)));
	}

	/**
	 * Formats the given Group-Array for JSTree, a plugin for JQuery
	 *
	 * @param  Array $groupRootArray The groups as a multidimensional Array
	 */
	protected function groupsFormatForJstree($groupRootArray) {

		$recFuncHelper = array($groupRootArray);
		$groupArray = $this->childsFormatForJstree($recFuncHelper);

		return $groupArray;
	}

	/**
	 * A helper-function formatting an Array
	 *
	 * @param  Array $childs Childs of a Module
	 */
	protected function childsFormatForJstree($childs) {

		if(count($childs)) {
			foreach($childs as &$child) {
				$child['children'] = $child['childs'];
				$child['data'] = $child['name'];
				$child['metadata'] = $child['id'];
				unset($child['childs']);
				unset($child['name']);
				unset($child['id']);
				if(count($child['children'])) {
					$child['children'] = $this->childsFormatForJstree(
						$child['children']);
				}
			}
			return $childs;
		}
		else {
			return array();
		}
	}

	/**
	 * Changes a Group based on the given data
	 */
	protected function groupsChange() {

		$query = '';
		$changeCounter = 0;

		if(isset($_POST['data'])) {

			foreach($_POST['data'] as &$data) {
				TableMng::sqlEscape($data);
			}
			$query = $this->groupsChangeQuery($_POST['data']);
		}
		else {
			die(json_encode(array('value' => 'error',
				'message' => 'No data given!')));
		}

		try {
			TableMng::getDb()->autocommit(false);
			TableMng::queryMultiple($query);
			TableMng::getDb()->autocommit(true);

		} catch (Exception $e) {
			die(json_encode(array(
				'value' => 'error',
				'message' => 'Konnte die Query nicht ausführen!')));
		}

		die(json_encode(array(
			'value' => 'success',
			'message' => 'Die Gruppen wurden erfolgreich geändert!')));
	}

	/**
	 * Creates the Correct Query changing the Group
	 *
	 * @param  Array $data The data needed to create the Array
	 */
	protected function groupsChangeQuery($data) {

		$query = '';

		if(!empty($data['action'])) {

			$this->rootChangeWantedCheck($data);

			switch($data['action']) {
				case 'add':
					$query = $this->groupAddQuery($data);
					break;
				case 'rename':
					$query = $this->groupChangeQuery($data);
					break;
				case 'delete':
					$query = $this->groupDeleteQuery($data);
					break;
				default:
					return '';
			}
		}
		else {
			return '';
		}

		return $query;
	}

	/**
	 * Check if user wants to change the root-Node and decline if so
	 *
	 * @param  Array $data The data needed to check for the root-Element
	 */
	protected function rootChangeWantedCheck($data) {

		if(isset($data['name']) && $data['name'] == 'root' ||
			isset($data['oldName']) && $data['oldName'] == 'root') {
			die(json_encode(array(
				'value' => 'error',
				'message' => 'Root darf nicht geändert werden!')));
		}
	}

	/**
	 * Creates a Query to add a Group
	 *
	 * @param  Array $data The data needed to create the Query
	 * @return String The Query
	 */
	protected function groupAddQuery($data) {

		$name = $data['name'];
		$parentPath = $data['parentPath'];

		$parentgroup = $this->_acl->getGrouproot()->groupByPathGet(
			$parentPath);

		if($parentgroup) {
			$query = Group::groupAddQueryCreate($name, $parentgroup);
		}
		else {
			die(json_encode(array(
				'value' => 'error',
				'message' => 'Ein Fehler ist beim Finden der Elterngruppe ' .
					'aufgetreten')));
		}

		return $query;
	}

	/**
	 * Create a Query to change a Group
	 *
	 * @param  Array $data The data needed to create the Query
	 * @return String The Query
	 */
	protected function groupChangeQuery($data) {

		$escapedData = $this->groupChangeQueryEscapeInput($data);
		$group = $this->_acl->getGrouproot()->groupByPathGet(
			"$escapedData[parentPath]/$escapedData[oldName]");

		return $this->groupChangeQueryGet($group, $escapedData);
	}

	/**
	 * Escapes the Input given for creating the groupChangeQuery
	 *
	 * @param  Array $data The Inputdata
	 * @return Array The escaped Inputdata
	 */
	protected function groupChangeQueryEscapeInput($data) {

		TableMng::sqlEscape($data['oldName']);
		TableMng::sqlEscape($data['newName']);
		TableMng::sqlEscape($data['parentPath']);

		return $data;
	}

	/**
	 * Creates and returns the groupChangeQuery
	 *
	 * @param  Group $group The Group
	 * @param  Array $escapedData The Escaped Inputdata
	 * @return String The Query
	 */
	protected function groupChangeQueryGet($group, $escapedData) {

		if($group) {
			$query = Group::groupChangeQueryCreate(
				$group, $escapedData['newName']);
		}
		else {
			die(json_encode(array(
				'value' => 'error',
				'message' => 'Ein Fehler ist beim Finden der Gruppe ' .
					'aufgetreten')));
		}

		return $query;
	}

	/**
	 * Creates a Query to delete a Group
	 *
	 * @param  Array $data The data needed to delete the Group
	 * @return String The Query
	 */
	protected function groupDeleteQuery($data) {

		$escapedData = $this->groupDeleteQueryEscapeData($data);
		$group = $this->_acl->getGrouproot()->groupByPathGet(
			"$escapedData[parentPath]/$escapedData[name]");

		return $this->groupDeleteQueryCreate($group);
	}

	/**
	 * Escapes the data given to a Group
	 *
	 * @param  Array $data The data
	 * @return Array The escaped data
	 */
	protected function groupDeleteQueryEscapeData($data) {

		TableMng::sqlEscape($data['name']);
		TableMng::sqlEscape($data['parentPath']);

		return $data;
	}

	/**
	 * Creates the Query to delete the Group and returns it
	 *
	 * @param  Group $group The Group to delete
	 * @return String The executable Query
	 */
	protected function groupDeleteQueryCreate($group) {

		if($group) {
			$query = Group::groupDeleteQueryCreate($group);
		}
		else {
			die(json_encode(array(
				'value' => 'error',
				'message' => 'Ein Fehler ist beim Finden der Gruppe ' .
					'aufgetreten')));
		}

		return $query;
	}

	/**
	 * Fetches all Modules, formats them and outputs them for JSTree
	 */
	protected function modulesFetch() {

		TableMng::sqlEscape($_POST['grouppath']);

		$this->modulesFetchDoubleclickFix();
		$group = $this->modulesFetchGroupGet($_POST['grouppath']);
		$rights = $this->modulesFetchRightsInit($group);

		$mods = $this->getAllModulesByGroup($group);
		$modulesJstree = $this->modulesFormatForJstree(
			$mods->moduleAsArrayGet(), $rights, $group);

		die(json_encode(array(
			'value' => 'success',
			'message' => 'Die Daten wurden erfolgreich abgerufen',
			'data' => $modulesJstree)));
	}

	/**
	 * Fix errornous behaviour of JQuery with Doubleclick & Singleclick
	 *
	 * dblclick executes a single click too and problems arise.
	 * Suppress at least an Errormessage to the User
	 */
	protected function modulesFetchDoubleclickFix() {

		//errornous behavior in Javascript,
		if(substr($_POST['grouppath'], -1) == '/') {
			die(json_encode(array('value' => 'quickfix')));
		}
	}

	protected function modulesFetchGroupGet($path) {

		$group = $this->_acl->getGrouproot()->groupByPathGet($path);

		if($group) {
			return $group;
		}
		else {
			die(json_encode(array('value' => 'error',
				'message' => 'Konnte die Gruppe nicht finden')));
		}
	}

	/**
	 * Fetches and returns the GroupModuleRights for the given Group
	 *
	 * @param  Group $group The Group which rights to Fetch
	 * @return Array An Array of GroupModuleRights
	 */
	protected function modulesFetchRightsInit($group) {

		$groupId = $group->getId();
		$rightDbData = $this->modulesFetchRightsFetch($groupId);
		$rights = $this->modulesFetchDbRightsToObjects($rightDbData);
		return $rights;
	}

	/**
	 * Fetches the rights as an Array from the Database
	 *
	 * @param  int $groupId The Group-ID of the Rights
	 * @return Array The Rights that were returned from the Server
	 */
	protected function modulesFetchRightsFetch($groupId) {

		try {
			$rights = TableMng::query("SELECT * FROM SystemGroupModuleRights
				WHERE `groupId` = '$groupId'");

		} catch (Exception $e) {
			die(json_encode(array('value' => 'error',
				'message' => 'Konnte die Rechte nicht abrufen')));
		}

		return $rights;
	}

	/**
	 * Converts the Array of Rights to GroupModuleRights
	 *
	 * @param  Array $rightArray The data returned from the Database
	 * @return Array The GroupModuleRights
	 */
	protected function modulesFetchDbRightsToObjects($rightArray) {

		if(count($rightArray)) {
			$rights = GroupModuleRight::initMultiple($rightArray);
		}
		else {
			$rights = array();
		}

		return $rights;
	}

	/**
	 * Formats the Modules so that JSTree can process them
	 *
	 * @param  Array $moduleArray An Array of modules
	 * @param  Array $rights An Array of GroupModuleRight-Elements
	 * @return Array The formatted modules
	 */
	protected function modulesFormatForJstree($moduleArray, $rights, $group) {

		$recFuncHelper = array($moduleArray);
		$this->_parentgroupModules = $this->parentgroupModulerightsGet($group);
		$formattedModules = $this->modulechildsFormatForJstree(
			$recFuncHelper, $rights);

		return $formattedModules;
	}

	/**
	 * A Helper-function to allow JSTree to display the modules
	 *
	 * @param  Array $childs The Childs of the Module
	 * @param  Array $rights The GroupModuleRight-Array
	 * @return Array the changed Childs
	 */
	protected function modulechildsFormatForJstree($childs, $rights) {

		$changeable = false;
		$title = '';

		if(count($childs)) {
			foreach($childs as &$module) {

				$changeable = $this->moduleEntryAllowedToChangeCheck($module);

				$module = $this->modulechildsFormatForJstreeAdjustModuleData(
					$module, $changeable);

				if(count($module['children'])) {
					$module['children'] = $this->modulechildsFormatForJstree(
						$module['children'], $rights);
				}
			}
			return $childs;
		}
		else {
			return array();
		}
	}

	/**
	 * Changes the Array-data of the Module allowing to display them in Jstree
	 *
	 * @param  Array $module The Array representing the Module
	 * @param  boolean $changeable If the Module is changeable or not
	 * @return Array The Module with changed Arraydata
	 */
	protected function modulechildsFormatForJstreeAdjustModuleData(
		$module, $changeable) {

		$title = $this->modulechildsFormatForJstreeTitleCreate(
			$module, $changeable);

		// $changeableStr = ($changeable || !$module['enabled']) ?
			// 'changeable' : 'notChangeable';

		$module['children'] = $module['childs'];
		$module['data'] = $module['name'];
		$module['attr'] = array(
			'id' => 'module_' . $module['id'],
			'module_enabled' => ($module['enabled'] && $changeable),
			'user_has_access' => $module['userHasAccess'],
			// 'rel' => $changeableStr,
			'title' => $title);
		unset($module['enabled']);
		unset($module['childs']);
		unset($module['name']);
		unset($module['id']);

		return $module;
	}

	/**
	 * Creates a description for the Module based on its data
	 *
	 * @param  Array $module The Module to create a description for
	 * @param  boolean $changeable If the Module is changeable or not
	 * @return String The description of the Module (HTML-title)
	 */
	protected function modulechildsFormatForJstreeTitleCreate(
		$module, $changeable) {

		if($module['userHasAccess']) {
			if($changeable) {
				$title = 'Doppelklick um Modul zu deaktivieren';
			}
			else {
				$title = 'Recht auf dieses Modul wurde von einer übergeordneten Gruppe oder untergeordnetem Modul gesetzt; Verändern sie diesen Zugriff dort';
			}
		}
		else {
			$title = 'Doppelklick um Modul zu aktivieren';
		}

		return $title;
	}

	/**
	 * Checks if the Moduleentry-Right is allowed to change by the User
	 *
	 * @param  Array $module An Array representing the Module
	 * @return boolean True if the Module is allowed to be changed, else false
	 */
	protected function moduleEntryAllowedToChangeCheck($module) {

		$hasChildWithAccessAllowed =
			$this->moduleArrayHasChildWithUserHasAccessAllowed($module);

		return !$hasChildWithAccessAllowed &&
			!$this->isRightSetInParentgroupModule($module);
	}

	/**
	 * Checks if the userHasAccess-Value of the Parent module is set to true
	 *
	 * @return boolean true if userHasAccess set to true, else false
	 */
	protected function isRightSetInParentgroupModule($module) {

		$parentgroupMod = $this->_parentgroupModules->anyChildByIdGet($module['id']);
		if(is_object($parentgroupMod)) {
			$isRightSetInParentgroupModule = $parentgroupMod->userHasAccess();
		}
		else {
			/* When Root-object gets tested, nothing gets back (not a child of
			 * anything) */
			return false;
		}

		return $isRightSetInParentgroupModule;
	}

	/**
	 * Checks if the Module has a Child with userHasAccess Allowed (true)
	 *
	 * @return boolean true if it has a child, false if not
	 */
	protected function moduleArrayHasChildWithUserHasAccessAllowed($module) {

		if(count($module['childs'])) {
			foreach($module['childs'] as $child) {
				if($child['userHasAccess']) {
					return true;
				}
				else {
					if($this->moduleArrayHasChildWithUserHasAccessAllowed(
						$child)) {
						return true;
					}
				}
			}
		}
		else {
			return false;
		}

		return false;
	}

	/**
	 * Returns the Modules with the userHasAccess-Rights set of the Parentgroup
	 *
	 * @param  Group $group The Group to get the Parentgroup from
	 * @return Module The Moduleroot with all the Modules
	 */
	protected function parentgroupModulerightsGet($group) {

		$compAcl = new Acl($this->_logger, $this->_pdo);
		$parentgroup = Group::directParentGet(
			$group,
			$compAcl->getGrouproot());
		$compAcl->accessControlInitByGroup($parentgroup);
		$modules = $compAcl->moduleGeneratorManagerGet()->moduleRootGet();

		return $modules;
	}

	/**
	 * Changes the Right of a Module
	 */
	protected function modulerightStatusChange() {

		if(!empty($_POST['moduleId']) && !empty($_POST['grouppath'])) {

			$moduleId = $_POST['moduleId'];
			$grouppath = $_POST['grouppath'];
			TableMng::sqlEscape($moduleId);
			TableMng::sqlEscape($grouppath);

			$group = $this->_acl->getGrouproot()->groupByPathGet($grouppath);
			$module = $this->modulerightStatusChangeModuleGet(
				$group, $moduleId);

			if($module->isEnabled()) {
				// Reverse the state of the module since the User wants
				// it changed
				$desiredState = !($module->userHasAccess());

				$this->modulerightStatusChangeUpload( $desiredState, $moduleId,
					$group);
			}
			else {
				die(json_encode(array('value' => 'error',
					'message' => _g('The Module is deactivated! You need to activate it first in the ModuleSettings.'))));
			}

			die(json_encode(array(
				'value' => 'success',
				'message' => 'Die Rechte wurden erfolgreich verändert')));
		}
		else {
			die(json_encode(array('value' => 'error',
				'message' => 'Zu wenig Daten gegeben!')));
		}
	}

	/**
	 * Gets the Module by the ModuleId with its rights set by the group
	 *
	 * @param  Group $group The Group to set the rights for
	 * @return ModuleGenerator the Module
	 */
	protected function modulerightStatusChangeModuleGet($group, $moduleId) {

		$modulerootWithRightsSet = $this->getAllModulesByGroup($group);

		if($modulerootWithRightsSet->getId() != $moduleId) {
			$module = $modulerootWithRightsSet->anyChildByIdGet($moduleId);
		}
		else {
			$module = $modulerootWithRightsSet;
		}

		return $module;
	}

	/**
	 * Uploads the Change of a Status to the Database
	 *
	 * @param  boolean $desiredState If the module is enabled or not
	 * @param  int $moduleId The Module-ID
	 * @param  int $group The Group-ID
	 */
	protected function modulerightStatusChangeUpload(
		$desiredState, $moduleId, $group) {

		if($desiredState) {
			GroupModuleRight::rightCreate(
				$moduleId,
				$group->getId());
		}
		else {
			GroupModuleRight::rightDelete(
				$moduleId,
				$group->getId());
		}
	}

	/**
	 * Gets all Modules and sets the rights for the given Group
	 *
	 * @param  Group $group The group for which the rights to set
	 * @return Array All Modules (even the not allowed ones)
	 */
	protected function getAllModulesByGroup($group) {

		$groupAcl = new Acl($this->_logger, $this->_pdo);
		$groupAcl->accessControlInitByGroup($group);
		$mods = $groupAcl->moduleGetWithNotAllowedModules('root');

		return $mods;
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	protected $_acl;

	protected $_interface;

	/**
	 * Used for Creating the Moduleright-Table, to determine if Module is
	 * changeable or not
	 *
	 * @var Module
	 */
	protected $_parentgroupModules;
}

?>
