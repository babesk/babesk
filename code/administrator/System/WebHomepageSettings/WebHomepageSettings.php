<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_ACCESS . '/GlobalSettingsManager.php';
require_once 'WebHomepageSettingsInterface.php';
require_once PATH_ADMIN . '/System/System.php';

class WebHomepageSettings extends System {
	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct ($name, $display_name, $path) {
		parent::__construct ($name, $display_name, $path);
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute ($dataContainer) {
		$this->entry($dataContainer);
		if (isset ($_GET ['action'])) {
			switch ($_GET ['action']) {
				case 'redirect':
					$this->redirect ();
				break;
				case 'helptext':
					$this->helptext ();
				break;
				case 'maintenance':
					$this->maintenance ();
					break;
				case 'setmaintenance':
					$this->setMaintenance();
					break;
				default:
					die ('wrong action-value');
					break;
			}
		}
		else {
			$this->_interface->mainMenu ();
		}
	}


	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function entry($dataContainer) {

		defined('_AEXEC') or die("Access denied");
		parent::entryPoint($dataContainer);
		$this->initSmartyVariables();

		$this->_interface = new WebHomepageSettingsInterface (
			$this->relPath, $dataContainer->getSmarty());

		$this->_globalSettingsMng = new GlobalSettingsManager ();
	}

	/**
	 * Settings for Redirection in web, after the user logged in
	 */
	protected function redirect () {
		if (isset ($_POST ['time'], $_POST ['target'])) {
			$this->redirectCheck ($_POST ['time'], $_POST ['target']);
			$this->redirectSet ($_POST ['time'], $_POST ['target']);
			$this->_interface->dieMsg ('Die Weiterleitungs-Einstellungen wurden verändert');
		}
		else {
			$this->_interface->redirect ($this->_globalSettingsMng->valueGet(GlobalSettings::WEBHP_REDIRECT_DELAY),$this->_globalSettingsMng->valueGet(GlobalSettings::WEBHP_REDIRECT_TARGET));
		}
	}

	/**
	 * Checks the Input of the user at the redirect-function
	 * @used-by WebHomepageSettings::redirect ()
	 */
	protected function redirectCheck ($time, $target) {
		if (!is_numeric ($time)) {
			$this->_interface->dieError ('falsche Eingabe der Delayzeit');
		}
		if (strlen($target) < 2) {
			$this->_interface->dieError ('falsche Eingabe des Pfades');
		}
	}

	/**
	 * Uploads the Changes to the Db
	 */
	protected function redirectSet ($time, $target) {
		try {
			$this->_globalSettingsMng->valueSet (
				GlobalSettings::WEBHP_REDIRECT_DELAY, $time);
			$this->_globalSettingsMng->valueSet (
				GlobalSettings::WEBHP_REDIRECT_TARGET, $target);
		} catch (Exception $e) {
			$this->dieError ('Konnte die Weiterleitungs-Einstellungen nicht ändern');
		}
	}

	protected function maintenance() {
		$stmt = $this->_pdo->prepare("SELECT * FROM SystemGlobalSettings WHERE name = 'siteIsUnderMaintenance'");
		$stmt->execute();
		$entry = $stmt->fetch();

		if($entry) {
			$val = $entry['value'];
		}
		else {
			$this->_pdo->prepare("INSERT INTO SystemGlobalSettings(name, value) VALUES ('siteIsUnderMaintenance', 0)")->execute();
			$val = 0;
		}

		$this->_smarty->assign('maintenance', $val);
		$this->displayTpl('maintenance.tpl');
		//$this->_interface->maintenance($val);
	}

	/**
	 * Settings for maintenance in web, before the user logged in
	 */
	protected function setMaintenance () {

		$value = (isset($_POST['maintenance'])) ? 1 : 0;
		try {
			$stmt = $this->_pdo->prepare("UPDATE SystemGlobalSettings SET value=? WHERE name='siteIsUnderMaintenance'");
			$stmt->execute(array($value));

		} catch (Exception $e) {
			$this->_interface->dieError(
				'Konnte den Wartungsmodus nicht ändern'
			);
		}
		$this->_interface->dieSuccess('Wartungsmodus erfolgreich verändert.');
	}

	/**
	 * Settings for helptext in web, before the user logged in
	 */
	protected function helptext () {
		if (isset ($_POST ['helptext'])) {
			$this->helptextSet ($_POST ['helptext']);
			$this->_interface->dieMsg ('Die Hilfetext-Einstellungen wurden ver&auml;ndert');
		}
		else {
			$this->_interface->helptext ($this->_globalSettingsMng->valueGet(GlobalSettings::WEBLOGIN_HELPTEXT));
		}
	}



	/**
	 * Uploads the Changes to the Db
	 */
	protected function helptextSet ($helptext) {
		try {
			$this->_globalSettingsMng->valueSet (
					GlobalSettings::WEBLOGIN_HELPTEXT,$helptext);
		} catch (Exception $e) {
			$this->dieError ('Konnte die Hilfetext-Einstellungen nicht &auml;ndern');
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	protected $_globalSettingsMng;
	protected $_interface;

}

?>
