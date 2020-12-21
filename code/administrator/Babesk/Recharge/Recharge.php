<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_ADMIN . '/Babesk/Babesk.php';

class Recharge extends Babesk {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	/**
	 * Constructs the Module
	 *
	 * @param string $name         The Name of the Module
	 * @param string $display_name The Name that should be displayed to the
	 *                             User
	 * @param string $path         A relative Path to the Module
	 */
	public function __construct($name, $display_name, $path) {

		parent::__construct($name, $display_name, $path);
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	/**
	 * Executes the Module, does things based on ExecutionRequest
	 *
	 * @param  DataContainer $dataContainer contains data needed by the Module
	 */
	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);
		$execReq = $dataContainer->getExecutionCommand()->pathGet();
		if($this->submoduleCountGet($execReq)) {
			$this->submoduleExecuteAsMethod($execReq);
		}
		else {
			$this->displayTpl('mainmenu.tpl');
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	/**
	 * The Entry-Point of this Module, initializes the needed Data
	 *
	 * Dies displaying a Message when User tries to access from outside
	 *
	 * @param DataContainer $dataContainer contains data needed by the Module
	 */
	protected function entryPoint($dataContainer) {

		parent::entryPoint($dataContainer);
		parent::initSmartyVariables();
	}

	/********************************************************************
	 * Allows the User to recharge the Credits of a User by his Card
	 */
	protected function submoduleRechargeCardExecute() {

		if(isset($_POST['card_ID'])) {
			$this->changeAmountDisplay($_POST['card_ID']);
		}
		else if(isset($_POST['amount'], $_POST['uid'])) {
			$this->rechargeUsercredits($_POST['amount'], $_POST['uid'], false);
		}
		else {
			$this->displayTpl('form1.tpl');
		}
	}

	/**
	 * Displays a form allowing the User to Change the Recharge-Amount
	 *
	 * Dies displaying a Message on Error
	 *
	 * @param  int    $cardId The ID of the Card
	 */
	protected function changeAmountDisplay($cardId) {

		$userId = $this->userIdGetByCardId($cardId);

		if($this->isUseraccountUnlockedCheck($userId)) {

			$maxRechargeAmount = $this->getMaxRechargeAmountOfUser($userId);
			$maxRechargeAmount = sprintf('%01.2f', $maxRechargeAmount);

			$isSoliRecharge = $this->userHasValidSoliCoupon(
				$userId, date('Y-m-d')
			);

			$this->_smarty->assign('max_amount', $maxRechargeAmount);
			$this->_smarty->assign('uid', $userId);
			$this->_smarty->assign('isSoliRecharge', $isSoliRecharge);
			$this->displayTpl('form2.tpl');
		}
		else {
			$this->_interface->dieError(_g('Useraccount is locked!'));
		}
	}

	/**
	 * Fetches the User-ID by the given Card-ID
	 *
	 * Dies displaying a Message if Error connecting to the DB happened
	 *
	 * @param int    $cardId The Card-ID of the User-Id to get
	 * @return string The User-ID if found else false
	 */
	protected function userIdGetByCardId($cardId) {

		try {
			$stmt = $this->_pdo->prepare(
				'SELECT UID FROM BabeskCards WHERE cardnumber = :cardnumber'
			);

			$stmt->execute(array('cardnumber' => $cardId));
			return $stmt->fetchColumn();

		} catch (PDOException $e) {
			$this->_interface->dieError(
				_g('Could not fetch the User by Card-ID %1$s', $cardId));
		}

		return $stmt->fetchColumn();
	}

	/**
	 * Checks if the Useraccount is locked or not
	 *
	 * Dies displaying a Message if connecting to Database failed
	 *
	 * @param  int     $userId The ID of the Useraccount to check
	 * @return boolean         true if it is not locked or false if Account
	 * locked
	 */
	protected function isUseraccountUnlockedCheck($userId) {

		try {
			$stmt = $this->_pdo->prepare(
				'SELECT locked FROM SystemUsers WHERE ID = :userId'
			);

			$stmt->execute(array('userId' => $userId));
			$data = $stmt->fetchColumn();

		} catch (PDOException $e) {
			$this->_interface->dieError(_g('Could not check if the ' .
				'Useraccount of User-ID %1$s is locked or not', $userId));
		}

		if($data === false) {
			$this->_interface->dieError(_g('Could not check if the ' .
				'Useraccount of User-ID %1$s is locked or not. Could ' .
				'not find the User!', $userId));
		}
		else {
			return $data == '0';
		}
	}

	/**
	 * Fetches the Max allowed Amount that the user can recharge
	 *
	 * Dies displaying a Message on Error
	 *
	 * @param  int    $userId The ID of the User
	 * @return int            The Maximum allowed amount to recharge
	 */
	protected function getMaxRechargeAmountOfUser($userId) {

		try {
			$stmt = $this->_pdo->prepare(
				'SELECT g.max_credit AS maxCredits, u.credit AS credits
				FROM SystemUsers u
				JOIN BabeskPriceGroups g ON u.GID = g.ID
				WHERE u.ID = :userId');

			$stmt->execute(array('userId' => $userId));

		} catch (PDOException $e) {
			$this->_interface->dieError(_g('Could not fetch the Max ' .
				'Credits for the User with the ID %1$s', $userId));
		}

		$data = $stmt->fetch();
		if($data) {
			return $data['maxCredits'] - $data['credits'];
		}
		else {
			$this->_interface->dieError(_g('Could not fetch the ' .
				'Max Credits for the User with the ID %1$s; It looks ' .
				'like the User is not in any Pricegroup?', $userId));
		}
	}

	/**
	 * Recharges the Card of the User by the $rechargeAmount
	 *
	 * Dies displaying a Message on Error
	 *
	 * @param  int    $userId         The ID of the User which Card to recharge
	 * @param  float  $rechargeAmount The Amount to recharge [add]
	 */
	protected function rechargeUsercredits($rechargeAmount, $userId, $userform) {

		$rechargeAmount = floatval(str_replace(',', '.', $rechargeAmount));

		if($rechargeAmount <= $this->getMaxRechargeAmountOfUser($userId)) {

			$this->_pdo->beginTransaction();

			$this->amountAddToUsercredits($rechargeAmount, $userId);
			$this->trackRechargeAdd($rechargeAmount, $userId);

			$this->_pdo->commit();
		}
		else {
			$this->_interface->backlink(
				'administrator|Babesk|Recharge'
			);
			$this->_interface->dieError(_g('The given amount to recharge added to the Credits is more than the Maximum Amount of Credits allowed for the Users Pricegroup!'));
		}

		$this->rechargeSuccessDisplay($rechargeAmount, $userId, $userform);
	}

	/**
	 * Adds the given Amount to the Users Credits
	 *
	 * Dies displaying a Message on Error
	 *
	 * @param float  $amount The Amount of Credits to add
	 * @param int    $userId The ID of the User which credits to change
	 */
	protected function amountAddToUsercredits($amount, $userId) {

		try {
			$stmt = $this->_pdo->prepare('UPDATE SystemUsers
				SET credit = credit + :amount WHERE ID = :userId');

			$stmt->execute(array('amount' => $amount, 'userId' => $userId));

		} catch (PDOException $e) {
			$this->_interface->dieError(_g('Could not upload the recharge ' .
				'of the credits!') . $e->getMessage());
		}
	}

	/**
	 * Adds an Row to the Table that tracks the Recharges
	 *
	 * Dies displaying a Message on Error
	 *
	 * @param  float  $amount The Amount of Credits to recharge
	 * @param  int    $userId The User-ID of the Card to recharge
	 */
	protected function trackRechargeAdd($amount, $userId) {

		$isSoli = $this->userHasValidSoliCoupon($userId, date('Y-m-d'));

		try {
			$stmt = $this->_pdo->prepare('INSERT INTO BabeskUsercreditsRecharges
				(userId, rechargingUserId, rechargeAmount, datetime, soli, isSoli)
				VALUES (:userId, :rechargingUserId, :rechargeAmount,
					:datetime, :isSoli, :isSoli
				)');
			$stmt->execute(array(
				'userId' => $userId,
				'rechargingUserId' => $_SESSION['UID'],
				'rechargeAmount' => $amount,
				'datetime' => date( 'Y-m-d H:i:s'),
				'isSoli' => ($isSoli) ? 1 : 0
			));

		} catch (PDOException $e) {

			$this->_interface->dieError(_g('Could not track the Recharge!'));
		}
	}

	/**
	 * Displays a Success-Message to the User that the Recharge was successfull
	 *
	 * Dies displaying a Message
	 *
	 * @param  float  $amount The Amount that was reloaded
	 * @param  int    $userId The ID of the User
	 */
	protected function rechargeSuccessDisplay($amount, $userId, $userform) {

		$stmt = $this->_pdo->prepare(
			'SELECT CONCAT(forename, " ", name) FROM SystemUsers
			WHERE ID = :userId');
		$stmt->execute(array('userId' => $userId));

		if(!($username = $stmt->fetchColumn())) {
			$username = _g('Username not found!');
		}

		$this->_smarty->assign('username', $username);
		$this->_smarty->assign('amount', sprintf('%01.2f', $amount));
		$this->_smarty->assign('userform', $userform);
		$this->displayTpl('recharge_success.tpl');
	}

	/********************************************************************
	 * Allows the User to Print a Balance of the Recharges done
	 */
	protected function submodulePrintRechargeBalanceExecute() {

		if(isset($_POST['date'], $_POST['interval'])) {
			$this->rechargeBalancePdfPrint();
		}
		else {
			$this->timeConfigurationDisplay();
		}
	}

	/**
	 * Prints a PDF with all Recharges in the Interval the User selected
	 */
	protected function rechargeBalancePdfPrint() {

		extract($this->timestampsFromIntervalInputGet($_POST['date']));
		$rechargesToPrint = $this->rechargesFetchBetween(
			date('Y-m-d H:i:s', $start), date('Y-m-d H:i:s', $end));
		$table = $this->rechargesAsHtmlTable($rechargesToPrint);
		$sum = $this->rechargesSum($rechargesToPrint);
		$table .= '<p></p><p></p><b>' . _g('Sum:') . ' ' . $sum . '</b>';

		$title = _g('Balance-Print for the days from %1$s to %2$s',
			date('d.m.Y', $start), date('d.m.Y', $end - 1));
		$html = "<p align='center'><h2>{$title}</h2></p><br />" . $table;

		require_once PATH_INCLUDE . '/pdf/GeneralPdf.php';
		$pdf = new GeneralPdf($this->_pdo);
		$pdf->create($title, $html);
		$pdf->output();
	}

	/**
	 * Returns the Beginning and End Timestamps for the Interval
	 *
	 * @param  $selectedDate The Date that is in the Interval
	 * @return array  The Beginning and End in an Array
	 */
	protected function timestampsFromIntervalInputGet($selectedDate) {

		$midnight = strtotime(date('Y-m-d', strtotime($selectedDate)));

		switch($_POST['interval']) {
			case 'day':
				return array('start' => strtotime('now', $midnight),
					'end' => strtotime('+1 day', $midnight));
				break;
			case 'week':
				return array('start' => strtotime('now', $midnight),
					'end' => strtotime('+1 week', $midnight));
				break;
			case 'month':
				return array('start' => strtotime('now', $midnight),
					'end' => strtotime('+1 month', $midnight));
				break;
			case 'year':
				$val = $_POST['interval'];
				return array(
					'start' => strtotime("first day of this $val", $midnight),
					'end' => strtotime("first day of next $val", $midnight));
				break;
			default:
				$this->_interface->dieError(
					_g('Could not parse the given Interval'));
				break;
		}
	}

	/**
	 * Fetches all Recharges made between $startdate and $enddate
	 *
	 * Dies displaying a Message if the Recharges could not be fetched
	 *
	 * @param  string $startdate The Startdate as datetime
	 * @param  string $enddate   The Enddate as datetime
	 * @return array             The Recharges made
	 */
	protected function rechargesFetchBetween($startdate, $enddate) {

		try {
			$stmt = $this->_pdo->prepare('SELECT ur.*,
				CONCAT(u.forename, " ", u.name) AS name,
				CONCAT(ru.forename, " ", ru.name) AS rechargedBy,
				isSoli
				FROM BabeskUsercreditsRecharges ur
				JOIN SystemUsers u ON ur.userId = u.ID
				JOIN SystemUsers ru ON ur.rechargingUserId = ru.ID
				WHERE datetime BETWEEN :startdate AND :enddate');

			$stmt->execute(array(
				'startdate' => $startdate,
				'enddate' => $enddate
			));

			return $stmt->fetchAll();

		} catch (PDOException $e) {
			$this->_interface->dieError(_g('Could not fetch the Recharges!'));
		}
	}

	/**
	 * Creates a Html-Table describing the Recharges given
	 *
	 * @param  array  $recharges The Recharges to be displayed as Html
	 * @return string            The Html-Table
	 */
	protected function rechargesAsHtmlTable($recharges) {

		$html = '<table style="text-align:center">
			<tr>
				<th style="height:50px"><b>Name</b></th>
				<th><b>Betrag</b></th>
				<th><b>Datum</b></th>
				<th><b>aufgeladen von</b></th>
				<th><b>Teilhabepaket</b></th>
			</tr>';

		foreach($recharges as $recharge) {
			$soliStr = ($recharge['isSoli']) ? _g('Yes') : _g('No');
			$html .= "
				<tr>
					<td>$recharge[name]</td>
					<td>$recharge[rechargeAmount]</td>
					<td>$recharge[datetime]</td>
					<td>$recharge[rechargedBy]</td>
					<td>$soliStr</td>
				</tr>";
		}

		$html .= '</table>';

		return $html;
	}

	/**
	 * Sums all the rechargeAmounts
	 *
	 * @param  array  $recharges The Rechargedata
	 * @return int               The sum of the rechargeAmounts
	 */
	protected function rechargesSum($recharges) {

		$sum = 0.;

		foreach($recharges as $recharge) {
			$sum += $recharge['rechargeAmount'];
		}

		return $sum;
	}

	/**
	 * Displays a Form in which the User can select the Data of the PDF-Print
	 *
	 * Dies displaying a form
	 */
	protected function timeConfigurationDisplay() {

		$this->_smarty->assign('intervals', array(
			'day' => _g('Day'),
			'week' => _g('Week'),
			'month' => _g('Month'),
			'year' => _g('Year')
		));
		$this->displayTpl('printRechargeBalanceSelect.tpl');
	}

	/**
	 * Checks if the User has a valid Solicoupon at the given Date
	 *
	 * @param int $userId The ID of the User
	 * @param string $date The Date to check
	 * @return boolean True if the User has a Valid Coupon at that date, else
	 * false
	 */
	protected function userHasValidSoliCoupon($userId, $date) {

		try {
			$stmt = $this->_pdo->prepare(
				'SELECT IF(COUNT(*) > 0, 1, 0) AS hasValidCoupon
				FROM BabeskSoliCoupons sc
				JOIN SystemUsers u ON u.ID = :userId
				WHERE sc.UID = :userId
					AND :date BETWEEN sc.startdate AND sc.enddate
					AND u.soli = 1
				');

			$stmt->execute(array('userId' => $userId, 'date' => $date));

			return $stmt->fetchColumn() == 1;

		} catch (PDOException $e) {
			$this->_interface->showError(
				_g('Could not check if the User is Soli or not.'));
			return false;
		}
	}
	
	protected function submoduleRechargeUserExecute(){
		if(isset($_POST['id'])){
			$maxRechargeAmount = $this->getMaxRechargeAmountOfUser($_POST['id']);
			$maxRechargeAmount = sprintf('%01.2f', $maxRechargeAmount);
			
			$isSoliRecharge = $this->userHasValidSoliCoupon(
					$_POST['id'], date('Y-m-d')
					);
			echo json_encode(['maxRecharge' => $maxRechargeAmount, 'soli' => $isSoliRecharge]);
		}elseif(isset($_POST['amount']) && isset($_POST['uid'])){
			$this->rechargeUsercredits($_POST['amount'], $_POST['uid'], true);
		}else{
			$stmt = $this->_pdo->prepare("SELECT u.ID, u.forename, u.name, g.name as groupname, gradelevel, grades.label FROM SystemUsers u
													JOIN BabeskPriceGroups g ON (u.GID = g.ID)
													LEFT JOIN 
														(SELECT a.* FROM SystemAttendances a 
														JOIN SystemSchoolyears y ON (a.schoolyearId = y.ID) 
														WHERE active = 1) sub
													ON (sub.userId = u.ID)
													LEFT JOIN SystemGrades grades ON (grades.ID = sub.gradeId) ");
			$stmt->execute();
			$users = $stmt->fetchAll();
			$usersArr = array();
			foreach ($users as &$user){
				$row['ID'] = $user['ID'];
				$row['forename'] = $user['forename'];
				$row['name'] = $user['name'];
				$row['priceGroup'] = $user['groupname'];
				if($user['gradelevel'] != null){
					$row['grade'] = $user['gradelevel'] . $user['label'];
				}else{
					$row['grade'] = "Keine Klasse";
				}
				$usersArr[] = $row;

			}
			$this->_smarty->assign('users', $usersArr);
			$this->displayTpl('rechargeUser.tpl');
		}
	}
	
	

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	protected $_interface;
}

?>
