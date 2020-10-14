<?php

namespace administrator\System\GlobalSettings\Change;

require_once PATH_ADMIN . '/System/GlobalSettings/GlobalSettings.php';

class Change extends \administrator\System\GlobalSettings\GlobalSettings {

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		parent::entryPoint($dataContainer);
		$name = filter_input(INPUT_GET, 'name');
		$value = filter_input(INPUT_GET, 'value');

		if($name && $value) {
			//Store booleans as either 1 or 0 in the table
			if($value == 'true' || $value == 'false') {
				$value = ($value == 'true') ? 1 : 0;
			}
			$this->entryChange($name, $value);
		}
		else {
			dieHttp('Parameter fehlen / sind inkorrekt', 400);
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function entryChange($name, $value) {
        try {
            $stmt = $this->_pdo->prepare("UPDATE SystemGlobalSettings SET value = ? WHERE name = ?");
            $stmt->execute(array($value, $name));
        }
        catch(Exception $e) {
            dieHttp('Konnte Eintrag nicht ändern', 500);
        }

		die('Eintrag erfolgreich geändert.');
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////
}
?>