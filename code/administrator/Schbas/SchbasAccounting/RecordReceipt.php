<?php

namespace administrator\Schbas\SchbasAccounting;

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_INCLUDE . '/Schbas/Loan.php';
require_once 'SchbasAccounting.php';
require_once PATH_INCLUDE . '/pdf/GeneralPdf.php';

class RecordReceipt extends \SchbasAccounting {

	///////////////////////////////////////////////////////////////////////
	//Constructor
	///////////////////////////////////////////////////////////////////////

	public function __construct($name, $display_name, $path) {

		parent::__construct($name, $display_name, $path);
	}

	///////////////////////////////////////////////////////////////////////
	//Methods
	///////////////////////////////////////////////////////////////////////

	/**
	 * Moduleexecution starts here
	 */
	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);

		try {
		    $stmt = $this->_pdo->prepare("SELECT COUNT(*) FROM SchbasAccounting");
		    $stmt->execute();
			$count = $stmt->fetch();
			if($count[0] == 0) {
				$this->_interface->dieMsg('Es gibt keine Einträge.');
			}
		}
		catch(\Exception $e) {
			$this->_logger->logO('Could not check if accounting-entries exist',
				['sev' => 'error', 'more' => $e->getMessage()]);
			$this->_interface->dieError('Konnte die Einträge nicht lesen');
		}


		if(isset($_POST['filter'])) {
			$this->userdataAjaxSend();
		}
		else if(isset($_GET['pdf-title'])) {
			$this->pdfSend();
		}
		else if(isset($_POST['userId'], $_POST['amount'], $_POST['to-pay'])) {
			$this->paidAmountChange($_POST['userId'], $_POST['amount'], $_POST['to-pay']);
		}
		else if(isset($_POST['userId'], $_POST['returned'])){
            $this->formReturnedChange($_POST['userId'], $_POST['returned']);
        }
		else {
			$this->displayTpl('record-receipt.tpl');
		}
	}

	///////////////////////////////////////////////////////////////////////
	//Implements
	///////////////////////////////////////////////////////////////////////

	/**
	 * Initializes various variables to use in the module
	 */
	protected function entryPoint($dataContainer) {

		parent::entryPoint($dataContainer);
		$this->initSmartyVariables();
	}

	/**
	 * Sends the client the data of the users he requested as json
	 * Dies sending json
	 */
	private function userdataAjaxSend() {

		$opt = [];
		$opt['specialFilter'] = filter_input(INPUT_POST, 'specialFilter');
		$data = $this->userdataFetch(
			$_POST['filter'],
			$_POST['filterForColumns'],
			$_POST['sortColumn'],
			$_POST['activePage'],
			$opt
		);
		$loanHelper = new \Babesk\Schbas\Loan($this->_dataContainer);
		$prepSchoolyear = $loanHelper->schbasPreparationSchoolyearGet();

		$stmt = $this->_pdo->prepare("SELECT * FROM SystemSchoolyears WHERE ID = ?");
		$stmt->execute(array($prepSchoolyear));
		$schoolyear = $stmt->fetch();
		if(count($data['users'])) {
			die(json_encode(array(
				'users' => $data['users'], 'pagecount' => $data['pagecount'],
				'schbasPreparationSchoolyear' => $schoolyear['label']
			)));
		}
		else {
			http_response_code(204);
			die(json_encode(array(
				'message' => 'Keine Benutzer gefunden!'
			)));
		}
	}

	/**
	 * Fetches the userdata by the given parameter
	 * @param  string $filter  Filters the users. Can be void
	 * @param  string $pagenum The number of page requested
	 * @return array           [
	 *                             'users' => [<userdata>],
	 *                             'pagecount' => <pagecount>
	 *                         ]
	 */
	private function userdataFetch(
		$filter, $filterForCol, $sortColumn, $pagenum, array $options = []
	) {

		$data = $this->userdataQueryCreate(
			$filter, $filterForCol, $sortColumn, $pagenum, $options
		);
        $pagecount = $this->pagecountGet($data);
        $data = array_slice($data, ($pagenum -1) * $this->_usersPerPage, $this->_usersPerPage);

		$users = array();
        /**foreach($data as $page) {
            $user = $page[0];
            //Doctrines array-hydration treats foreign keys different
            $user['cardnumber'] = $page['cardnumber'];
            $user['payedAmount'] = (isset($page['payedAmount'])) ?
                $page['payedAmount'] : 0.00;
            $user['amountToPay'] = (isset($page['amountToPay'])) ?
                $page['amountToPay'] : 0.00;
            $user['missingAmount'] = (isset($page['missingAmount'])) ?
                $page['missingAmount'] : 0.00;
            $user['loanChoice'] = $page['loanChoice'];
            $user['loanChoiceAbbreviation'] =
                $page['loanChoiceAbbreviation'];
            $user['activeGrade'] = $page['activeGrade'];
            if (isset($page['accID'])){
                $user['formReturned'] = \TableMng::query(sprintf("SELECT formReturned FROM SchbasAccounting WHERE id = %s", $page['accID']))[0]['formReturned'];
                $user['accID'] = $page['accID'];
            }
            $users[] = $user;
        }*/
        return array('users' => $data, 'pagecount' => $pagecount);
	}

	/**
	 * Creates the Query with which to fetch the userdata
	 * @param  string $filter  Filters the users. Can be void
	 * @param  string $pagenum The number of page requested
	 * @return Query           A doctrine query object for fetching the users
	 */
	private function userdataQueryCreate(
		$filter, $filterForCol, $sortColumn, $pagenum, array $options = []
	) {

		$loanHelper = new \Babesk\Schbas\Loan($this->_dataContainer);
		$prepSchoolyear = $loanHelper->schbasPreparationSchoolyearGet();
		$query = "SELECT u.*, c.cardnumber, lc.name AS loanChoice, lc.abbreviation AS loanChoiceAbbreviation, lc.id AS loanChoiceId, CONCAT(u.gradelevel, u.label) AS activeGrade, a.amountToPay, a.payedAmount, a.amountToPay - a.payedAmount AS missingAmount, a.formReturned 
                  FROM UserActiveClass u
                  JOIN BabeskCards c ON (c.UID = u.ID)
                  LEFT JOIN (SELECT * FROM SchbasAccounting WHERE schoolyearId = :syID) a ON (u.ID = a.userId)
                  LEFT JOIN SchbasLoanChoices lc ON (a.loanChoiceId = lc.ID)
                  LEFT JOIN SystemAttendances att ON (att.userId = u.ID)
                  WHERE att.schoolyearId = :syID  ";

		if(isset($options['specialFilter'])) {
			if($options['specialFilter'] == 'showMissingAmountOnly') {
			    $query .= "AND a.amountToPay - a.payedAmount > 0 ";
			}
			else if($options['specialFilter'] == 'showMissingFormOnly') {
			    $query .= "AND lc.id IS NULL ";
			}
			else if($options['specialFilter'] == 'showSelfbuyerOnly') {
                $query .= "AND lc.abbreviation = 'nl' ";
			}
			else if($options['specialFilter'] == 'showNotPayingOnly') {
                $query .= "AND lc.abbreviation = 'ls' ";
			}
			else if($options['specialFilter'] == 'showPayedTooMuch') {
                $query .= "AND a.amountToPay - a.payedAmount < 0 ";
            }
            else if($options['specialFilter'] == 'showFormNotReturned') {
                $query .= "AND lc.abbreviation = 'ls' AND a.formReturned != 1 ";
            }
		}
		if(!empty($filter) && !empty($filterForCol)) {
		    $queryOr = "AND (0 ";
			if(in_array('cardnumber', $filterForCol)) {
                $queryOr .= "OR c.cardnumber LIKE :filter ";
			}
			if(in_array('grade', $filterForCol)) {
                $queryOr .= "OR CONCAT(u.gradelevel, u.label) LIKE :filter ";
			}
			if(in_array('username', $filterForCol)) {
                $queryOr .= "OR u.username LIKE :filter ";
			}
			$queryOr .= ") ";
			$query .= $queryOr;
		}

		if(!empty($sortColumn)) {
			if($sortColumn == 'grade') {
				$query .= "ORDER BY activeGrade";
			}
			else if($sortColumn == 'name') {
				$query .= "ORDER BY u.name";
			}
			else {
				$this->_logger->log('Unknown column to sort for',
					'Notice', Null, json_encode(array('col' => $sortColumn)));
			}
		}


		$stmt = $this->_pdo->prepare($query);
		$stmt->execute(array(
		    'syID' => $prepSchoolyear,
            'filter' => "%".$filter."%"
        ));
		$res = $stmt->fetchAll();

        return $res;
	}

    protected function pagecountGet($paginator) {

        $bookcount = count($paginator);
        // No division by zero, never show zero sites
        if($this->_usersPerPage != 0 && $bookcount > 0) {
            $pagecount = ceil($bookcount / (int)$this->_usersPerPage);
        }
        else {
            $pagecount = 1;
        }
        return $pagecount;
    }

	private function paidAmountChange($userId, $amount, $toPay) {

		try {
			$loanHelper = new \Babesk\Schbas\Loan($this->_dataContainer);
			$stmt = $this->_pdo->prepare("SELECT * FROM SystemUsers WHERE id = ?");
			$stmt->execute(array($userId));
			$user = $stmt->fetch();
			$schoolyear = $loanHelper->schbasPreparationSchoolyearGet();
			if(!isset($user)) {
				throw new Exception('User not found!');
			}
			$stmt = $this->_pdo->prepare("UPDATE SchbasAccounting SET payedAmount = :pa, amountToPay = :atp WHERE userId = :uid AND schoolyearId = :syid");
			$stmt->execute(array(
			    ':pa' => $amount,
                ':atp' => $toPay,
                ':uid' => $userId,
                ':syid' => $schoolyear
            ));
			$missing = $toPay - $amount;
			die(json_encode(array(
				'userId' => $userId, 'paid' => $amount, 'toPay' => $toPay, 'missing' => $missing
			)));

		} catch(Exception $e) {
			$this->_logger->log('Error updating the paid amount of an user',
				'error', Null, json_encode(array('uid' => $userId,
					'amount' => $amount, 'msg' => $e->getMessage())));
			http_response_code(500);
		}
	}

    private function formReturnedChange($userId, $returned){
        $loanHelper = new \Babesk\Schbas\Loan($this->_dataContainer);
        $schoolyear = \TableMng::query("SELECT value FROM SystemGlobalSettings WHERE name = 'schbasPreparationSchoolyearId'")[0]['value'];
        \TableMng::query(sprintf("UPDATE SchbasAccounting SET formReturned = %s WHERE userId = %s AND schoolyearId= %s ", $returned, $userId, $schoolyear));

        die(json_encode("Erfolgreich"));
    }

	/**
	 * Sends a pdf with an overview over the fetched users to the client
	 */
	private function pdfSend() {

		$pdfTitle = filter_input(INPUT_GET, 'pdf-title');
		$opt = [];
		$opt['specialFilter'] = filter_input(INPUT_GET, 'specialFilter');
		// We want all users to be printed into the pdf
		$this->_usersPerPage = 9999;
		$data = $this->userdataFetch(
			$_GET['filter'],
			$_GET['filterForColumns'],
			$_GET['sortColumn'],
			1,
			$opt
		);
		$this->_smarty->assign('title', $pdfTitle);
		$this->_smarty->assign('users', $data['users']);
		$html = $this->_smarty->fetch(
			PATH_SMARTY_TPL . '/pdf/schbas-record-receipt-user-list.pdf.tpl'
		);
		$pdf = new \GeneralPdf($this->_pdo);
		$pdf->create($pdfTitle, $html);
		$pdf->output();
	}

	///////////////////////////////////////////////////////////////////////
	//Attributes
	///////////////////////////////////////////////////////////////////////

	private $_usersPerPage = 10;
}

?>