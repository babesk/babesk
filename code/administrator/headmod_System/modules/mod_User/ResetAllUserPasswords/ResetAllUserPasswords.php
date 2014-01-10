<?php

require_once __DIR__ . '/../User.php';

/**
 * Allows to reset Passwords of all users to the preset Password
 */
class ResetAllUserPasswords extends User {

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {
		$this->entryPoint($dataContainer);

		if(isset($_POST['resetConfirmed'])) {
			$this->resetUserPasswords();
		}
		else {
			$this->mainMenuDisplay();
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	/**
	 * Resets the userpasswords to the preset Password
	 * Excludes the User with the ID 1, since he is usually the SuperUser
	 */
	protected function resetUserPasswords() {

		$presetPassword = $this->presetPasswordGet();

		if($presetPassword) {
			$sql = "UPDATE users
				SET password = '$presetPassword'
				WHERE ID <> 1";
		}
		else {
			$this->_interface->dieError(_g('Please set the preset password ' .
				'before reseting the users passwords.'));
		}
	}

	/**
	 * Fetches the Preset Password from the Database
	 * @return string The preset Password if found, else false
	 */
	protected function presetPasswordGet() {

		try {
			$stmt = $this->_pdo->query(
				'SELECT value FROM global_settings
					WHERE name = "presetPassword"');

			return $stmt->fetchColumn();

		} catch (PDOException $e) {
			$this->_logger->log('Could not find the Preset Password.');
			return false;
		}
	}

	protected function mainMenuDisplay() {
		$this->displayTpl('confirmReset.tpl');
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////


}

?>