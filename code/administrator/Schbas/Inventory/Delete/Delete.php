<?php

namespace administrator\Schbas\Inventory\Delete;

require_once PATH_ADMIN . '/Schbas/Inventory/Inventory.php';
require_once PATH_INCLUDE . '/Schbas/Barcode.php';

class Delete extends \Inventory {

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);
		if(isset($_POST['barcodes']) && count($_POST['barcodes'])) {
			$this->barcodesDelete($_POST['barcodes']);
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function barcodesDelete($barcodeStrings) {

		foreach($barcodeStrings as $barcodeStr) {
			$barcode = new \Babesk\Schbas\Barcode();
			if(!$barcode->initByBarcodeString($barcodeStr)) {
				dieHttp("Der Barcode '$barcodeStr' ist nicht korrekt", 400);
			}
			$bookCopy = $barcode->getMatchingBookExemplar($this->_pdo);
			if($bookCopy) {
			    $query = $this->_pdo->prepare("DELETE FROM SchbasLending WHERE inventory_id = ?");
			    $query->execute(array($bookCopy['id']));

                $query = $this->_pdo->prepare("DELETE FROM SchbasInventory WHERE id = ?");
                $query->execut-e(array($bookCopy['id']));

			}
			else {
				echo "<p>Kein Buchexemplar zu Barcode $barcodeStr gefunden. " .
					"</p>";
			}
		}
		die('Die Exemplare wurden erfolgreich gelöscht');
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

}

?>