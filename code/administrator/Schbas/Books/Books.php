<?php

namespace administrator\Schbas\Books;

require_once PATH_ADMIN . '/Schbas/Schbas.php';

/**
 * Submodules offer data by REST-requests
 */
class Books extends \Schbas {

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


	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

}

?>