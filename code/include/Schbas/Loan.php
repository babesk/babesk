<?php

namespace Babesk\Schbas;

/**
 * Contains operations useful for the loan-process of Schbas
 */
class Loan {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct($dataContainer) {

		$this->entryPoint($dataContainer);
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	/**
	 * Returns an array of possible isbn-identifiers for the given gradelevel
	 * @param  int    $gradelevel The Gradelevel
	 * @return array              The array of possible isbn-identifiers of the
	 *                            gradelevel, or false if gradelevel not found
	 */
	public function gradelevel2IsbnIdent($gradelevel) {

		if(!empty($this->_gradelevelIsbnIdentAssoc[$gradelevel])) {
			return $this->_gradelevelIsbnIdentAssoc[$gradelevel];
		}
		else {
			return false;
		}
	}

	/**
	 * Returns an array of gradelevels associated with the given isbnIdentifier
	 * @param  string $ident The identifier (like '69' or '05')
	 * @return array         The array of gradelevels or false if none found
	 */
	public function isbnIdent2Gradelevel($ident) {

		$gradelevels = array();
		//Extract possible gradelevels
		foreach($this->_gradelevelIsbnIdentAssoc as $gradelevel => $idents) {
			if(in_array($ident, $idents)) {
				$gradelevels[] = $gradelevel;
			}
		}
		if(count($gradelevels)) {
			return $gradelevels;
		}
		else {
			return false;
		}
	}

	public function booksInGradelevelToLoanGet($gradelevel) {

		$classes = $this->gradelevel2IsbnIdent($gradelevel);
		if($classes) {
			try {
                $classesString = str_repeat('?, ', count($classes)-1) . '?';
				$stmt = $this->_pdo->prepare("SELECT * FROM SchbasBooks b 
											  LEFT JOIN SystemSchoolSubjects sub ON (b.subjectId = sub.ID)
											  WHERE b.class IN (" .$classesString . ")");
				$stmt->execute($classes);
				$books = $stmt->fetchAll();
				return $books;
			}
			catch(\Exception $e) {
				$this->_logger->logO('Could not fetch the books to loan in ' .
					'gradelevel', ['sev' => 'error', 'moreJson' =>
						$e->getMessage()]);
			}
		}
		else {
			return array();
		}
	}


	/**
	 * Calculates the loan-price of a book by its full price and its class
	 * @param  float  $flatPrice The full price of the book
	 * @param  string $class     The class of the book (like "05" or "92")
	 * @return float             The resulting loan-price
	 */
	public function bookLoanPriceCalculate($flatPrice, $class) {

		if(isset($this->_classToPriceFactor[$class])) {
			$factor = $this->_classToPriceFactor[$class];
			$loanPrice = $flatPrice * $factor;
			return $loanPrice;
		}
		else {
			throw new \Exception('No book-class "' . $class . '" found.');
		}
	}

	/**
	 * Calculates the reduced loan-price of a book by its price and its class
	 * @param  float  $flatPrice The full price of the book
	 * @param  string $class     The class of the book (like "05" or "92")
	 * @return float             The resulting reduced loan-price
	 */
	public function bookReducedLoanPriceCalculate($flatPrice, $class) {

		if(isset($this->_classToPriceFactor[$class])) {
			$factor = $this->_classToPriceFactor[$class];
			$loanPrice = $flatPrice * $factor * 0.8;
			return $loanPrice;
		}
		else {
			$this->_logger->logO('A book assigned to a user has no valid ' .
				'class', ['sev' => 'error', 'moreJson' => ['userId' =>
						$user->getId(), 'bookId' => $book->getId()]]);
			throw new \Exception('No book-class "' . $class . '" found.');
		}
	}

	public function loanPriceOfAllBookAssignmentsForUserCalculate($user) {

		// We want to calculate the price of even the already lend books,
		// because the pricecalculation of the book itself already reflects
		// how many years it will be lend
		$books = $this->loanBooksOfUserGet(
			$user, ['includeAlreadyLend' => true]
		);
		$feeNormal = 0.00;
		$feeReduced = 0.00;
		foreach($books as $book) {
			$normalPrice = $this->bookLoanPriceCalculate(
				$book['price'], $book['class']
			);
			$reducedPrice = $this->bookReducedLoanPriceCalculate(
				$book['price'], $book['class']
			);
			$feeNormal += $normalPrice;
			$feeReduced += $reducedPrice;
		}
		$feeNormal = round($feeNormal);
		$feeReduced = round($feeReduced);
		return array($feeNormal, $feeReduced);
	}

	/**
	 * Fetches the books the user should lend but has not done so yet
	 * Filters out the books the user has already lent and those that the user
	 * will buy by himself.
	 *
	 * @param  Object $user    The \Babesk\ORM\SystemUsers object
	 * @param  Array  $options An array of options. Values can include:
	 *                         'schoolyear' => Returns the books the user has
	 *                             to lend for this specific schoolyear.
	 *                             Default is the schbasPreparationSchoolyear.
	 *                         'includeSelfpay' => Books that would get
	 *                             filtered because the user is buying them
	 *                             for himself will be included.
	 *                         'includeAlreadyLend' => Books that would get
	 *                             filtered because the user already has lend
	 *                             them will be included.
	 * @return array        An array of Doctrine-Objects representing the books
	 */
	public function loanBooksOfUserGet($user, array $opt = Null) {

		try {
			$schoolyear = (isset($opt['schoolyear'])) ?
				$opt['schoolyear'] : $this->schbasPreparationSchoolyearGet();
			// Default to subtracting the selfpaid books
			$subtractSelfpay = (empty($opt['includeSelfpay']));
			$includeAlreadyLend = (!empty($opt['includeAlreadyLend']));
			$query = "SELECT b.*, usb.userId, usb.schoolyearId, usb.bookId, u.*, sub.name as subject FROM SchbasBooks b 
					  JOIN SystemSchoolSubjects sub ON (b.subjectId = sub.ID)
					  JOIN SchbasUsersShouldLendBooks usb ON (b.id = usb.bookId)
					  JOIN SystemUsers u ON (u.ID = usb.userId) 
					  WHERE usb.schoolyearId = :syid
					  AND usb.userId = :uid ";
			if($subtractSelfpay) {
				$query = $query . " AND usb.bookId NOT IN (SELECT BID FROM SchbasSelfpayer WHERE UID = :uid)";
			}
			$stmt = $this->_pdo->prepare($query);
			$stmt->execute(array(':syid' => $schoolyear,
								 ':uid' => $user['ID']));

			$books = $stmt->fetchAll();

			if(!$includeAlreadyLend) {
				$books = $this->loanBooksGetFilterAlreadyLentBooks(
					$books, $user
				);
			}
			return $books;

		} catch (\Exception $e) {
			$this->_logger->log('Could not fetch the loanBooks',
				['sev' => 'error', 'moreJson' => $e->getMessage()]);
		}
	}

	/**
	 * Fetches all books the user has lend
	 * @param  object $user The user
	 * @return array        The books
	 */
	public function lendBooksOfUserGet($user) {
		$stmt = $this->_pdo->prepare("SELECT b.* FROM SchbasLending l JOIN SchbasInventory i ON (i.id = l.inventory_id) JOIN SchbasBooks b ON (b.id = i.book_id) WHERE user_id = ?");
		$stmt->execute(array($user['ID']));
		$lendBooks = $stmt->fetchAll();
		return $lendBooks;
	}

	/**
	 * Fetches books a user has lend and now has to return
	 * @param  object  $user       the user for the books
	 * @param  object  $schoolyear Optional; If given will calculate the books
	 *                             to return for the given schoolyear. Default
	 *                             is the schbasPreparationSchoolyear
	 * @return array               An array of books
	 */
	public function lendBooksToReturnOfUserGet($user, $schoolyear = false) {

		if(!$schoolyear) {
			$schoolyear = $this->schbasPreparationSchoolyearGet();
		}
		$lendBooks = $this->lendBooksOfUserGet($user);
		$shouldLendBooks = $this->loanBooksOfUserGet(
			$user, ['includeAlreadyLend' => true, 'schoolyear' => $schoolyear]
		);
		// lendBooks - shouldLendBooks = booksToReturn
		$booksToReturn = [];
		foreach($lendBooks as $lendBook) {
			foreach($shouldLendBooks as $shouldLendBook) {
				if($lendBook['id'] == $shouldLendBook['id']) {
					continue 2;
				}
			}
			$booksToReturn[] = $lendBook;
		}
		return $booksToReturn;
	}

	/**
	 * Gets all books the user should lend but instead is buying them himself
	 * @return array  An array of books
	 */
	public function selfboughtBooksOfUserGet($user, $schoolyear = false) {

		if(!$schoolyear) {
			$schoolyear = $this->schbasPreparationSchoolyearGet();
		}
		$stmt = $this->_pdo->prepare("SELECT b.*, sub.name as subject FROM SchbasBooks b
										JOIN SystemSchoolSubjects sub ON (b.subjectId = sub.ID)
										JOIN SchbasSelfpayer sp ON (sp.BID = b.id)
										WHERE sp.UID = ?");
		$stmt->execute(array($user['ID']));
		$books = $stmt->fetchAll();
		return $books;
	}

	public function amountOfInventoryAssignedToUsersGet($book) {

		$schoolyear = $this->schbasPreparationSchoolyearGet();
		try {
			$query = $this->_pdo->prepare("SELECT COUNT(*) FROM SchbasUsersShouldLendBooks usb
										   WHERE bookId = ? AND schoolyearId = ?");
			$query->execute(array($book['id'], $schoolyear));
			return $query->fetch()[0];
		}
		catch(\Exception $e) {
			$this->_logger->logO('Error fetching amount of inventory ' .
				'assigned to users', ['sev' => 'error', 'moreJson' => [
					'bookId' => $book->getId(), 'msg' => $e->getMessage()]]);
			return Null;
		}
	}

	/**
	 * Returns the schoolyear for which schbas is getting prepared
	 * @return int ID of the Schoolyear
	 */
	public function schbasPreparationSchoolyearGet() {

		$stmt = $this->_pdo->prepare("SELECT * FROM SystemGlobalSettings WHERE name=?");
		$stmt->execute(array("schbasPreparationSchoolyearId"));
		$syEntry = $stmt->fetch()['value'];
		return $syEntry;
	}

	public function booksAssignedToGradelevelsGet() {

		$stmt = $this->_pdo->query("SELECT * FROM SchbasBooks");
		$books = $stmt->fetchAll();

		$booksInGradelevels = array();
		foreach($books as $book) {
			$gradelevels = $this->isbnIdent2Gradelevel($book['class']);
			$booksInGradelevels[] = array(
				'book' => $book,
				'gradelevels' => $gradelevels
			);
		}
		return $booksInGradelevels;
	}


	/**
	 * Calculates the subjects of a user by accumulating the correct columns
	 * @param  object $user       the user
	 * @param  int    $gradelevel The gradelevel of the user
	 * @return array              the subjects of the user as an array of
	 *                            strings
	 */
	public function userSubjectsCalc($user, $gradelevel) {

        $coreSubjects = \TableMng::query("SELECT abbreviation FROM SchbasCoreSubjects c JOIN SystemSchoolSubjects s ON c.subject_id = s.ID WHERE gradelevel = ".$gradelevel);
        $userSubjects = array_unique(array_merge(
                explode('|', $user['special_course']),
                array_column($coreSubjects, "abbreviation"))
        );

        return $userSubjects;
	}



	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function entryPoint($dataContainer) {

		$this->_pdo = $dataContainer->getPdo();
		$this->_dataContainer = $dataContainer;
		$this->_logger = clone($dataContainer->getLogger());
		$this->_logger->categorySet('Babesk/Schbas/Loan');
	}

	/**
	 * Returns the users gradelevel of the grade being in the active schoolyear
	 * @param  int    $userId The ID of the user
	 * @return int            The gradelevel
	 * @todo   Probably want to extract this function to System/SystemUsers
	 */
	protected function activeGradelevelOfUserGet($userId) {

		$stmt = $this->_pdo->prepare(
			'SELECT g.gradelevel FROM SystemUsers u
				INNER JOIN SystemAttendances a
					ON a.userId = u.ID
				INNER JOIN SystemGrades g ON g.ID = a.gradeId
				WHERE a.schoolyearId = @activeSchoolyear
					AND u.ID = :userId
		');
		$stmt->execute(array('userId' => $userId));
		return $stmt->fetchColumn();
	}

	protected function loanBooksGetFilterAlreadyLentBooks($books, $user) {
		$stmt = $this->_pdo->prepare("SELECT i.book_id FROM SchbasLending l JOIN SchbasInventory i ON (l.inventory_id = i.id) WHERE user_id = ?");
		$stmt->execute(array($user['ID']));
		$lent = $stmt->fetchAll();
		$filteredBooks = array();
		foreach ($books as $book){
			$notLend = true;
			foreach ($lent as $le){
				if($book['bookId'] == $le['book_id'])
					$notLend = false;
			}
			if($notLend){
				$filteredBooks[] = $book;
			}
		}
		return $filteredBooks;
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	protected $_gradelevelIsbnIdentAssoc = array(
		'5'  => array('05', '56'),
		'6'  => array('56', '06', '69', '67'),
        '7'  => array('78', '07', '69', '79', '67', '70'),
        '8'  => array('78', '08', '69', '79', '89', '70', '80'),
        '9'  => array('90', '91', '09', '92', '69', '79', '89', '70', '80'),
        '10' => array('90', '91', '10', '92', '70', '80'),
        '11' => array('91', '12', '92', '13', '11'),
        '12' => array('12', '92', '13', '23'),
        '13' => array('13', '23')
	);

	protected $_isbnIdentSchoolyearRange = [
		'05' => 1, '06' => 1, '07' => 1, '08' => 1, '09' => 1, '10' => 1,
		'56' => 2, '67' => 2, '78' => 2, '89' => 2, '90' => 2, '12' => 2,
			'13' => 2,
		'79' => 3, '91' => 3,
        '69' => 4, '92' => 4, '70' => 4, '11' => 1, '23' => 2, '80' => 3
	];

	//Maps the book-classes to the pricefactor with which the flatPrice to
	//divide. Corresponds to the amount of years the user is lend the book.
	protected $_classToPriceFactor = array(
        "05" => (1/3),
        "06" => (1/3),
        "07" => (1/3),
        "08" => (1/3),
        "09" => (1/3),
        "10" => (1/3),
        "56" => 0.2,
        "67" => 0.2,
        "78" => 0.2,
        "89" => 0.2,
        "90" => 0.2,
        "12" => 0.2,
        "13" => 0.2,
        "79" => (2/15),
        "91" => (2/15),
        "69" => 0.1,
        "92" => 0.1,
        '70' => 0.1,
        '11' => (1/3),
        '23' => 0.2,
		'80' => (2/15)
	);

	protected $_pdo;
	protected $_logger;
	protected $_dataContainer;
}

?>
