<?php

namespace Babesk\Schbas;

require_once PATH_INCLUDE . '/Schbas/Loan.php';

/**
 * Generates the entries for the table SchbasUsersShouldLendBooks
 */
class ShouldLendGeneration {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct($dataContainer) {

		$this->_dataContainer = $dataContainer;
		$this->_pdo = $dataContainer->getPDO();
		$this->_logger = clone($dataContainer->getLogger());
		$this->_logger->categorySet(
			'Babesk/Schbas/Loan/ShouldLendGeneration'
		);
		$this->_loanHelper = new Loan($this->_dataContainer);
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	/**
	 * Generates the ShouldLendAssignments
	 * @param  array  $opt An array with optional options
	 *                     'onlyForUsers': An array of SystemUsers (rows).
	 *                         If given, will only generate the
	 *                         assignments for these users.
	 *                         If not, will generate for all users of the
	 *                         current schbasPreparationSchoolyear.
	 *                     'schoolyear': The DM:SystemSchoolyears for which to
	 *                         generate the assignments. If nothing given, it
	 *                         will generate for the schbasPrepSchoolyear.
	 * @return bool   true on success
	 */
	public function generate(array $opt = []) {

		if(isset($opt['onlyForUsers'])) {
			$this->_users = $opt['onlyForUsers'];
		}
		if(isset($opt['schoolyear'])) {
			$this->_schoolyear = $opt['schoolyear'];
		}

		$this->generateInit();
		foreach($this->_users as $user) {
			$gradelevel = $this->gradelevelGet($user);
			if(!$gradelevel) { continue; }
			$subjects = $this->_loanHelper->userSubjectsCalc(
				$user, $gradelevel
			);
			$classes = $this->_loanHelper->gradelevel2IsbnIdent(
				$gradelevel
			);
			if(!$classes) { continue; }
			foreach($this->_books as $book) {
				$bookSubject = $book['abbreviation'];
				if(!$bookSubject) { continue; }
				// Book is for other gradelevels than the user is in
				if(!in_array($book['class'], $classes)) { continue; }
                if(in_array($bookSubject, $subjects)) {
                    //if($this->isBookFiltered($bookSubjectAbbreviation, $subjects, $gradelevel)) {
                    $this->entryAdd($user, $book);
                }
			}
		}
		return true;
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function generateInit() {

		if(!$this->_schoolyear) {
			$this->preparationSchoolyearFetch();
		}
		if(!$this->_users) {
			$this->usersFetch();
		}
		$this->booksFetch();;
		$this->specialCourseTriggerFetch();
	}

	protected function preparationSchoolyearFetch() {

		$this->_schoolyear = $this->_loanHelper
			->schbasPreparationSchoolyearGet();
		if(!$this->_schoolyear) {
			$this->_logger->log(
				'Vorbereitungsschuljahr nicht gesetzt.', 'error'
			);
			throw new \Exception('schbasPreparationSchoolyear not found');
		}
	}

	protected function usersFetch() {

		try {
		    $stmt = $this->_pdo->query("SELECT * FROM SystemUsers");
			/**$userQuery = $this->_em->createQuery(
				'SELECT partial u.{
						id, religion, foreign_language, special_course
					},
					uigs, partial g.{id, gradelevel}
				FROM DM:SystemUsers u
				INNER JOIN u.attendances uigs
				INNER JOIN uigs.schoolyear sy WITH sy = :schoolyear
				INNER JOIN uigs.grade g
			');
			$userQuery->setParameter(
				'schoolyear', $this->_schoolyear
			);*/

			$this->_users = $stmt->fetchAll();
		}
		catch (Exception $e) {
			$this->_logger->logO('Could not fetch the users',
				['sev' => 'error', 'moreJson' => $e->getMessage()]);
			throw new \Exception('Could not fetch the users');
		}
	}

	protected function booksFetch() {

		// Fetch the books
		try {
		    $stmt = $this->_pdo->query("SELECT b.*, sub.name, sub.abbreviation FROM SchbasBooks b LEFT JOIN SystemSchoolSubjects sub ON (b.subjectId = sub.ID)");
			/**$bookQuery = $this->_em->createQuery(
				'SELECT partial b.{id, class}, partial s.{id, abbreviation}
				FROM DM:SchbasBook b
				INNER JOIN b.subject s
			');*/
			$this->_books = $stmt->fetchAll();
		}
		catch (Exception $e) {
			$this->_logger->logO('Could not fetch the books',
				['sev' => 'error', 'moreJson' => $e->getMessage()]);
			throw new \Exception('Could not fetch the books');
		}
	}


	protected function specialCourseTriggerFetch() {

	    $stmt = $this->_pdo->query("SELECT * FROM SystemGlobalSettings WHERE name = 'special_course_trigger'");

		$this->_specialCourseTrigger = (int)$stmt->fetch()['value'];
	}

	protected function gradelevelGet($user) {

        $stmt = $this->_pdo->prepare("SELECT gradelevel FROM SystemGrades g JOIN SystemAttendances a ON (g.ID = a.gradeId) WHERE userId = ? AND schoolyearId = ?");
        $stmt->execute(array($user['ID'], $this->_schoolyear));
        $grade = $stmt->fetch();

		if(!$grade) {
			return false;
		}
		$gradelevel = $grade['gradelevel'];
		return $gradelevel;
	}

	/**
	 * Checks if the book gets filtered out or not.
	 *
	 * The conditions are:
	 * - If the user has a subject which the book also has, he needs to lend
	 *   the book.
	 * - If he has not, but the books subject is also not in the filter, he
	 *   needs to lend it regardles.
	 * - If he has not, but the books subject is in the filter, he does not
	 *   need to lend it.
	 *
	 * @return boolean             Returns true if the book does not get
	 *                             filtered.
	 */
	protected function isBookFiltered($bookSubject, $subjects, $gradelevel) {
		// Does the user explicitly participate to the subject of the book?
		if(in_array($bookSubject, $subjects)) {
			return true;
		}
		else {
			$bookFilter = [];
			if($this->_specialCourseTrigger > $gradelevel) {
				$bookFilter = $this->_bookFilter;
			}
			else {
				// Check for filtering with special_course
				$bookFilter = $this->_bookFilterWithCourse;
			}
			if(in_array($bookSubject, $bookFilter)) {
				return false;
			}
			else {
				return true;
			}
		}
	}

	protected function entryAdd($user, $book) {

	    $stmt = $this->_pdo->prepare("INSERT INTO SchbasUsersShouldLendBooks(userId, bookId, schoolyearId) VALUES (?,?,?)");
	    $stmt->execute(array(
	        $user['ID'],
            $book['id'],
            $this->_schoolyear
        ));
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	protected $_dataContainer;

	protected $_logger;

    /**
     * @var \PDO
     */
    protected $_pdo;
	protected $_loanHelper;

	protected $_schoolyear;
	protected $_users;
	protected $_books;

	/**
	 * Contains school-subjects with which to filter the books.
	 *
	 * The book-filter consists of entries of the SystemGlobalSettings-table,
	 * namely 'religion' and 'foreign_language'.
	 */
	protected $_bookFilter;

	/**
	 * Contains school-subjects with which to filter the books.
	 *
	 * The book-filter consists of entries of the SystemGlobalSettings-table,
	 * namely 'religion', 'foreign_language' and 'special_course'.
	 */
	protected $_bookFilterWithCourse;

	protected $_specialCourseTrigger;
}

?>