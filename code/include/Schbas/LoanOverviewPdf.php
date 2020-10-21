<?php

namespace Babesk\Schbas;

require_once PATH_INCLUDE . '/Schbas/SchbasPdf.php';
require_once PATH_INCLUDE . '/Schbas/Loan.php';

/**
 * Creates a pdf that shows the overview of the loan-statuses of a user
 */
class LoanOverviewPdf {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct($dataContainer) {

		$this->_smarty = $dataContainer->getSmarty();
		$this->_pdo = $dataContainer->getPdo();
		$this->_dataContainer = $dataContainer;
		$this->_loanHelper = new \Babesk\Schbas\Loan($this->_dataContainer);
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function showPdf($user) {

		$schoolyear = $this->_loanHelper->schbasPreparationSchoolyearGet();
		$stmt = $this->_pdo->prepare("SELECT * FROM SystemSchoolyears WHERE ID = ?");
		$stmt->execute(array($schoolyear));
		$schoolyear = $stmt->fetch();
		$booksToLoan = $this->_loanHelper->loanBooksOfUserGet(
			$user, ['schoolyear' => $schoolyear, 'includeAlreadyLend' => true]
		);
		$booksToBuy = $this->_loanHelper->selfboughtBooksOfUserGet(
			$user, $schoolyear
		);
		$booksLend = $this->_loanHelper->lendBooksOfUserGet($user);
		$letterDate = date('d.m.Y H:i');

		$this->_smarty->assign('schoolyear', $schoolyear);
		$this->_smarty->assign('user', $user);
		$this->_smarty->assign('letterDate', $letterDate);
		$this->_smarty->assign('booksToLoan', $booksToLoan);
		$this->_smarty->assign('booksToBuy', $booksToBuy);
		$this->_smarty->assign('booksLend', $booksLend);
		$html = $this->_smarty->fetch(
			PATH_SMARTY_TPL . '/pdf/schbas-loan-overview.pdf.tpl'
		);
		$schbasPdf = new \Babesk\Schbas\SchbasPdf(
			$user['ID'], ''
		);
		$schbasPdf->create($html);
		$schbasPdf->output();
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	protected $_smarty;
	protected $_pdo;
	protected $_dataContainer;
	protected $_loanHelper;
}

?>