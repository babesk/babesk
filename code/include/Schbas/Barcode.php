<?php

namespace Babesk\Schbas;

class Barcode {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct() {

	}

	public static function createByBarcodeString($barcodeStr) {

		$barcode = new Barcode();
		$barcode->initByBarcodeString($barcodeStr);
		return $barcode;
	}

	/**
	 * Creates the barcode by reading the data from the inventory-object
	 * Note that it also reads from the book and subject of the $inventory, so
	 * be sure to fetch those with a query beforehand if you dont want
	 * additional queries to be executed.
	 * @param  DM:SchbasInventory $inventory
	 */
	public static function createByInventory($inventory) {

		$barcode = new Barcode();
		$book = $inventory->getBook();
		if(!$book || !$book->getSubject()) {
			return false;
		}
		$barcode->_subject = $book->getSubject()->getAbbreviation();
		$barcode->_class = $book->getClass();
		$barcode->_bundle = $book->getBundle();
		$barcode->_purchaseYear = $inventory->getYearOfPurchase();
		$barcode->_exemplar = $inventory->getExemplar();
		$barcode->_delimiter = '/';
		return $barcode;
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function getSubject() {
		return $this->_subject;
	}

	public function getPurchaseYear() {
		return $this->_purchaseYear;
	}

	public function getClass() {
		return $this->_class;
	}

	public function getBundle() {
		return $this->_bundle;
	}

	public function getDelimiter() {
		return $this->_delimiter;
	}

	public function getExemplar() {
		return $this->_exemplar;
	}

	public function getAsString() {
		return "$this->_subject $this->_purchaseYear $this->_class " .
			"$this->_bundle $this->_delimiter $this->_exemplar";
	}


	public function initByBarcodeString($barcode) {

		$barcode = $this->barcodeStringNormalize($barcode);
		$elements = explode(' ', $barcode);
		if(count($elements) != 6) {
			return false;
		}
		list(
				$this->_subject,
				$this->_purchaseYear,
				$this->_class,
				$this->_bundle,
				$this->_delimiter,
				$this->_exemplar
			) = $elements;
		return true;
	}

	public function getMatchingBookExemplar($pdo) {

	    $stmt = $pdo->prepare("SELECT * FROM SystemSchoolSubjects WHERE abbreviation = ?");
	    $stmt->execute(array($this->_subject));
	    $subjectID = $stmt->fetch();
	    if(!$subjectID)
	        return false;

	    $stmt = $pdo->prepare("SELECT i.*, b.title, b.author, b.publisher, b.isbn, b.price, b.class, b.bundle, sub.name AS subName FROM SchbasInventory i 
                              JOIN SchbasBooks b ON (i.book_id = b.id)
                              JOIN SystemSchoolSubjects sub ON (b.subjectId = sub.ID)
                              WHERE year_of_purchase = :yop
                              AND exemplar = :exemplar
                              AND b.subjectId = :subjectID
                              AND b.class = :class
                              AND b.bundle = :bundle");
	    $stmt->execute(array(
	        ':yop' => $this->_purchaseYear,
            ':exemplar' => $this->_exemplar,
            ':subjectID' => $subjectID['ID'],
            ':class' => $this->_class,
            ':bundle' => $this->_bundle));
		return $stmt->fetch();
	}

	public function getMatchingBooks($pdo) {
		$query = $pdo->prepare("SELECT b.* FROM SchbasBooks b JOIN SystemSchoolSubjects s ON (b.subjectId = s.ID)
                                WHERE b.class = ? AND b.bundle = ? AND s.abbreviation = ?");
		$query->execute(array($this->_class, $this->_bundle, $this->_subject));
		return $query->fetchAll();
	}


	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	private function barcodeStringNormalize($barcode) {

		$barcode = trim($barcode);
		$barcode = str_replace("-", "/", $barcode);
		//add space after / when it's missing
		$barcode = preg_replace("/\/([0-9])/", "/ $1", $barcode);
		$barcode = str_replace("  ", " ", $barcode);
		return $barcode;
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	protected $_subject;
	protected $_purchaseYear;
	protected $_class;
	protected $_bundle;
	protected $_delimiter;
	protected $_exemplar;



}

?>