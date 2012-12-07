<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_INCLUDE . '/functions.php';
require_once PATH_ACCESS . '/GlobalSettingsManager.php';
require_once 'PresetPasswordInterface.php';

class PresetPassword extends Module {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct ($name, $display_name, $path) {
		parent::__construct ($name, $display_name, $path);
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////
	/** The entry-point of the Module
	 *
	 */
	public function execute ($dataContainer) {
		$this->entryPoint ($dataContainer);
		if (isset($_GET ['action'])) {
			switch ($_GET ['action']) {
				case 'changePassword':
					$this->changePasswordHandle ();
					break;
			default:
				die ('action not defined');
			}
		}
		else {
			$this->mainMenuShow ();
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	/** Sets the Classes Variables
	 *
	 */
	private function entryPoint ($dataContainer) {
		defined('_AEXEC') or die('Access denied');
		$this->_interface = new PresetPasswordInterface ($this->relPath, $dataContainer->getSmarty());
		$this->_globalSettingsManager = new GlobalSettingsManager ();
	}

	private function mainMenuShow () {
		$firstLoginChangePassword = $this->firstLoginChangePasswordGet ();
		$this->_interface->mainMenuShow ($firstLoginChangePassword);
	}

	private function changePasswordHandle () {
		$pw = $_POST ['newPassword'];
		$onFirstLoginChangePassword = (isset($_POST ['firstLoginPassword'])) ? '1' : '0';
		$this->changePasswordCheckInput ($pw);
		$this->presetPasswordSet ($pw);
		$this->firstLoginChangePasswordSet ($onFirstLoginChangePassword);
		$this->_interface->dieMsg ('Die Einstellungen wurden übernommen');
	}

	private function changePasswordCheckInput ($pw) {
		try {
			inputcheck ($pw, 'password');
		} catch (WrongInputException $e) {
			$this->_interface->dieError ('Es wurde ein falsches Passwort eingegeben'
			);
		}
	}

	private function presetPasswordGet () {
		try {
			$password = $this->_globalSettingsManager->valueGet (GlobalSettings::PRESET_PASSWORD);
		} catch (MySQLVoidDataException $e) {
			$this->_globalSettingsManager->valueSet (GlobalSettings::PRESET_PASSWORD, hash_password(''));
			$password = hash_password('');
		}
		return $password;
	}

	private function presetPasswordSet ($password) {
		try {
			$this->_globalSettingsManager->valueSet (GlobalSettings::PRESET_PASSWORD, hash_password($password));
		} catch (Exception $e) {
			$this->_interface->dieError ('Konnte das Passwort nicht verändern');
		}
	}

	private function firstLoginChangePasswordGet () {
		try {
			$flcp = $this->_globalSettingsManager->valueGet (GlobalSettings::FIRST_LOGIN_CHANGE_PASSWORD);
		} catch (MySQLVoidDataException $e) {
			$this->_globalSettingsManager->valueSet (GlobalSettings::FIRST_LOGIN_CHANGE_PASSWORD, '0');
			$flcp = '0';
		}
		return $flcp;
	}

	private function firstLoginChangePasswordSet ($flcp) {
		try {
			var_dump($flcp);
			$this->_globalSettingsManager->valueSet (GlobalSettings::FIRST_LOGIN_CHANGE_PASSWORD, $flcp);
		} catch (Exception $e) {
			$this->_interface->dieError ('Konnte das den Wert für die Funktion zum Verändern des Passwortes bei einem ersten Login nicht verändern');
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	private $_interface;
	private $_globalSettingsManager;
}

?>