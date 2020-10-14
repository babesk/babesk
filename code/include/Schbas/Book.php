<?php

namespace Babesk\Schbas;

/**
 * Contains operations useful for handling the books of Schbas
 */
class Book {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct($dataContainer) {

		$this->entryPoint($dataContainer);
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////


	public function barcodeParseToArray($barcode) {

		$barcode = $this->barcodeNormalize($barcode);
		$barcodeAr = array();
		list(
				$barcodeAr['subject'],
				$barcodeAr['purchaseYear'],
				$barcodeAr['class'],
				$barcodeAr['bundle'],
				$barcodeAr['delimiter'],
				$barcodeAr['exemplar']
			) = explode(' ', $barcode);
		return $barcodeAr;
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function entryPoint($dataContainer) {

		$this->_pdo = $dataContainer->getPdo();
		$this->_logger = $dataContainer->getLogger();
	}

	private function barcodeNormalize($barcode) {

		$barcode = str_replace("-", "/", $barcode);
		//add space after / when it's missing
		$barcode = preg_replace("/\/([0-9])/", "/ $1", $barcode);
		$barcode = str_replace("  ", " ", $barcode);
		return $barcode;
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	protected $_pdo;
	protected $_logger;
}