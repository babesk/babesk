<?php

namespace administrator\System\User\UserUpdateWithSchoolyearChange;

require_once 'UserUpdateWithSchoolyearChange.php';

/**
 * Allows the user to resolve the conflicts
 **/
class ConflictsResolve extends \administrator\System\User\UserUpdateWithSchoolyearChange {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function entryPoint($dataContainer) {

		parent::entryPoint($dataContainer);
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

}
?>