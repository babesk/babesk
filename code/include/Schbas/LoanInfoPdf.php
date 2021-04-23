<?php

namespace Babesk\Schbas;

require_once PATH_INCLUDE . '/Schbas/SchbasPdf.php';
require_once PATH_INCLUDE . '/Schbas/Loan.php';

class LoanInfoPdf {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct($dataContainer) {

	    $this->_pdo = $dataContainer->getPdo();
		$this->_interface = $dataContainer->getInterface();
		$this->_smarty = $dataContainer->getSmarty();
		$this->_dataContainer = $dataContainer;
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function setDataByUser($user) {

		$this->_user = $user;
	}

	public function setDataByGradelevel($gradelevel) {

		$this->_gradelevel = $gradelevel;
	}

	public function showPdf() {

		if(!$this->_gradelevel && !$this->_user) {
			die('Either gradelevel or user has to be set');
		}

		$this->_loanHelper = new \Babesk\Schbas\Loan($this->_dataContainer);
		$userId = ($this->_user) ? $this->_user['ID'] : 0;

		if(!$this->_gradelevel) {
			$this->_gradelevel = $this->getGradelevelForUser($this->_user);
		}

		$bankAccount = $this->_pdo->query("SELECT value FROM SystemGlobalSettings WHERE name = 'bank_details'")->fetch();
		$bankData = explode('|', $bankAccount['value']);
		// $letterDateIso = $settingsRepo
		// 	->findOneByName('schbasDateCoverLetter')
		// 	->getValue();
		// $letterDate = date('d.m.Y', strtotime($letterDateIso));
		$letterDate = date('d.m.Y');

		list($feeNormal, $feeReduced) = $this->getFees();
		$books = $this->getBooks();


		$textId = $this->_gradelevel;
		$textQuery = $this->_pdo->prepare("SELECT * FROM SchbasTexts WHERE description = ?");

		$textQuery->execute(array('coverLetter'));
		$coverLetter = $textQuery->fetch();

        $textQuery->execute(array('textOne' . $textId));
        $textOne = $textQuery->fetch();

        $textQuery->execute(array('textTwo' . $textId));
        $textTwo = $textQuery->fetch();

        $textQuery->execute(array('textThree' . $textId));
        $textThree = $textQuery->fetch();

		$this->_smarty->assign('books', $books);
		$this->_smarty->assign('gradelevel', $this->_gradelevel);
		$this->_smarty->assign('letterDate', $letterDate);
		$this->_smarty->assign('coverLetter', $coverLetter);
		$this->_smarty->assign('textOne', $textOne);
		$this->_smarty->assign('textTwo', $textTwo);
		$this->_smarty->assign('textThree', $textThree);
		$this->_smarty->assign('bankData', $bankData);
		$this->_smarty->assign('feeNormal', $feeNormal);
		$this->_smarty->assign('feeReduced', $feeReduced);
		$html = $this->_smarty->fetch(
			PATH_SMARTY_TPL . '/pdf/schbas-loan-info.pdf.tpl'
		);
		$schbasPdf = new \Babesk\Schbas\SchbasPdf(
			$userId, $this->_gradelevel
		);
		$schbasPdf->create($html);
		$schbasPdf->output();
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function getGradelevelForUser($user) {

		$prepSchoolyear = $this->_loanHelper->schbasPreparationSchoolyearGet();
		$gradeQuery = $this->_pdo->prepare("SELECT g.* FROM SystemGrades g JOIN SystemAttendances a ON (a.gradeId = g.ID)
                                            WHERE a.schoolyearId = ? AND a.userId = ?");
		$gradeQuery->execute(array($prepSchoolyear, $this->_user['ID']));
		$grade = $gradeQuery->fetch();
		if(!$grade) {
			$this->_interface->dieError(
				'Der Schüler ist nicht im nächsten Schuljahr eingetragen. ' .
				'Bitte informieren Sie die Schule.'
			);
		}
		return $grade['gradelevel'];
	}

	protected function getFees() {

		if($this->_user) {
			return $this->_loanHelper
				->loanPriceOfAllBookAssignmentsForUserCalculate(
					$this->_user
				);
		}
		else {
			return [0.00, 0.00];
		}
	}

	protected function getBooks() {

		if($this->_user) {
			return $this->_loanHelper->loanBooksOfUserGet(
				$this->_user, ['includeAlreadyLend' => true]
			);
		}
		else {
			return $this->_loanHelper->booksInGradelevelToLoanGet(
				$this->_gradelevel
			);
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	protected $_em;
	protected $_interface;
	protected $_dataContainer;

	protected $_loanHelper;

	protected $_user;
	protected $_gradelevel;
}


?>