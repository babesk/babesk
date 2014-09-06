<?php

namespace administrator\Schbas\SchbasAccounting;

require_once PATH_INCLUDE . '/Module.php';
require_once 'SchbasAccounting.php';

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
			$this->_entityManager->createQuery(
				'SELECT a FROM Babesk:SchbasAccounting a'
			)->getResult();

		} catch(\Exception $e) {
			die('NOPE: ' . $e->getMessage());
		}


		if(isset($_POST['filter'])) {
			$this->userdataAjaxSend();
		}
		else if(isset($_POST['userId'], $_POST['amount'])) {
			$this->paidAmountChange($_POST['userId'], $_POST['amount']);
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

		$showOnlyMissing = ($_POST['showOnlyMissing'] == 'true')
			? true : false;
		$data = $this->userdataFetch(
			$_POST['filter'], $_POST['activePage'], $showOnlyMissing
		);
		if(count($data['users'])) {
			die(json_encode(array(
				'users' => $data['users'], 'pagecount' => $data['pagecount']
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
	private function userdataFetch($filter, $pagenum, $showOnlyMissing) {

		$query = $this->userdataQueryCreate(
			$filter, $pagenum, $showOnlyMissing
		);
		$paginator = new \Doctrine\ORM\Tools\Pagination\Paginator(
			$query, $fetchJoinCollection = true
		);
		$users = array();
		if(count($paginator)) {
			foreach($paginator as $page) {
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
				$users[] = $user;
			}
			$pagecount = ceil((int) count($paginator) / $this->_usersPerPage);
			$pagecount = ($pagecount != 0) ? $pagecount : 1;
			return array('users' => $users, 'pagecount' => $pagecount);
		}
		else {
			return array('users' => array(), 'pagecount' => 1);
		}
	}

	/**
	 * Creates the Query with which to fetch the userdata
	 * @param  string $filter  Filters the users. Can be void
	 * @param  string $pagenum The number of page requested
	 * @return Query           A doctrine query object for fetching the users
	 */
	private function userdataQueryCreate($filter, $pagenum, $showOnlyMissing) {

		$queryBuilder = $this->_entityManager->createQueryBuilder()
			->select(
				'partial u.{id, forename, name, username}, c.cardnumber, ' .
				'a.payedAmount, a.amountToPay, lc.name AS loanChoice, ' .
				'lc.abbreviation AS loanChoiceAbbreviation, ' .
				'a.amountToPay - a.payedAmount AS missingAmount'
			)->from('Babesk:SystemUsers', 'u')
			->join('u.schbasAccounting', 'a')
			->leftJoin('u.cards', 'c')
			->join('a.loanChoice', 'lc');
		if($showOnlyMissing) {
			$queryBuilder->having('missingAmount > 0');
		}
		if(!empty($filter)) {
			$queryBuilder->andWhere(
					'u.username LIKE :filter OR c.cardnumber LIKE :filter'
				)->setParameter('filter', "%${filter}%");
		}
		$queryBuilder->setFirstResult(($pagenum - 1) * $this->_usersPerPage)
			->setMaxResults($this->_usersPerPage);
		$query = $queryBuilder->getQuery();
		//For performance, paginator eats arrays, too
		$query->setHydrationMode(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
		return $query;
	}

	private function paidAmountChange($userId, $amount) {

		try {
			$user = $this->_entityManager->getRepository('Babesk:SystemUsers')
				->findOneById($userId);
			if(!isset($user)) {
				throw new Exception('User not found!');
			}
			$user->getSchbasAccounting()->setPayedAmount($amount);
			$paid = $user->getSchbasAccounting()->getPayedAmount();
			$toPay = $user->getSchbasAccounting()->getAmountToPay();
			$missing = $toPay - $paid;
			$this->_entityManager->persist($user);
			$this->_entityManager->flush();
			die(json_encode(array(
				'userId' => $userId, 'paid' => $paid, 'missing' => $missing
			)));

		} catch(Exception $e) {
			$this->_logger->log('Error updating the paid amount of an user',
				'Moderate', Null, json_encode(array('uid' => $userId,
					'amount' => $amount, 'msg' => $e->getMessage())));
			http_response_code(500);
		}
	}

	///////////////////////////////////////////////////////////////////////
	//Attributes
	///////////////////////////////////////////////////////////////////////

	private $_usersPerPage = 10;
}

?>