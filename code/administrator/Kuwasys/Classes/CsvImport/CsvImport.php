<?php

namespace administrator\Kuwasys\Classes;

require_once __DIR__ . '/../Classes.php';

class CsvImport extends \Classes {

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$mod = new \ModuleExecutionCommand('root/administrator/Kuwasys/' .
			'Classes/CsvImport/FileUploadForm');
		$dataContainer->getAcl()->moduleExecute($mod, $dataContainer);
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////
}

?>