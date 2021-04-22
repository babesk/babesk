<?php

namespace administrator\Schbas\Inventory\Add;

require_once PATH_ADMIN . '/Schbas/Inventory/Inventory.php';
require_once PATH_INCLUDE . '/Schbas/Barcode.php';

class Add extends \Inventory {

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);
		if(isset($_POST['barcodes']) && count($_POST['barcodes'])) {
			$this->barcodesAdd($_POST['barcodes']);
		}
		else if(
			isset($_POST['barcodesWithBookIds']) &&
			count($_POST['barcodesWithBookIds'])
		) {
			$this->inventoryWithBooksAdd($_POST['barcodesWithBookIds']);
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function barcodesAdd($barcodeStrings) {

		foreach($barcodeStrings as $barcodeStr) {
			$barcode = new \Babesk\Schbas\Barcode();
			if(!$barcode->initByBarcodeString($barcodeStr)) {
				dieHttp("Der Barcode '$barcodeStr' ist nicht korrekt", 400);
			}
			$books = $barcode->getMatchingBooks();
			if(count($books) > 1) {
				dieHttp("Der Barcode '$barcodeStr' passt zu mehr als 1 Buch",
					400);
			}
			else if(count($books) == 0) {
				dieHttp("Zum Barcode '$barcodeStr' konnte kein passendes " .
					"Buch gefunden werden", 400);
			}
			else {
				$book = $books->first();
				$inventory = new \Babesk\ORM\SchbasInventory();
				$inventory->setBook($book);
				$inventory->setYearOfPurchase($barcode->getPurchaseYear());
				$inventory->setExemplar($barcode->getExemplar());
				$this->_em->persist($inventory);
			}
		}
		try {
			$this->_em->flush();
		}
		catch(\Doctrine\DBAL\DBALException $e) {
			if($e->getPrevious()->getCode() === '23000') {
				dieHttp('Ein oder mehrere angegebene Barcodes gibt es schon!',
					400);
			}
			else {
				throw $e;
			}
		}
		die('Die Buch-Exemplare wurden erfolgreich hinzugefügt.');
	}

	/**
	 * Adds new copies of books
	 * Needs the given bookIds, it does not re-check if everything is correct
	 * @param  array  $barcodeContainers
	 */
	protected function inventoryWithBooksAdd($barcodeContainers) {

		$barcodes = [];
		foreach($barcodeContainers as $container) {
            if (!isset($container['bookId']) || !isset($container['barcode'])) {
                dieHttp('Inkorrekte Daten wurden übergeben.', 400);
            }
            $bookId = $container['bookId'];
            $barcodeStr = $container['barcode'];
            $barcode = new \Babesk\Schbas\Barcode();
            if ($barcode->initByBarcodeString($barcodeStr)) {
                $barcodes[] = $barcode;
            } else {
                dieHttp("Der Barcode '$barcodeStr' ist nicht korrekt", 400);
            }
            $query = $this->_pdo->prepare("SELECT * FROM SchbasInventory WHERE book_id = ? AND year_of_purchase = ? AND exemplar = ?");
            $query->execute(array($bookId, $barcode->getPurchaseYear(), $barcode->getExemplar()));
            $existing = $query->fetch();
            if ($existing) {
                dieHttp('Ein oder mehrere angegebene Barcodes gibt es schon!',
                    400);
            } else {
                $query = $this->_pdo->prepare("INSERT INTO SchbasInventory(book_id, year_of_purchase, exemplar) VALUES (?,?,?)");
                $query->execute(array($bookId, $barcode->getPurchaseYear(), $barcode->getExemplar()));
            }

        }
		die('Die Buch-Exemplare wurden erfolgreich hinzugefügt.');
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

}

?>