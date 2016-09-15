<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_WEB . '/Babesk/Babesk.php';

class Cancel extends Babesk {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct($name, $display_name, $path) {
		parent::__construct($name, $display_name, $path);
		$this->smartyPath = PATH_SMARTY_TPL . '/web' . $path;
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);

		TableMng::sqlEscape($_GET['id']);
		$this->orderdataLoad($_GET['id']);

		if($this->ordercancelLegalCheck()) {
			$this->orderCancel();
		}

		$this->_interface->dieSuccess(
			'Die Mahlzeit wurde erfolgreich gelöscht.'
		);
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	/**
	 * Initializes the Variables of this Class
	 *
	 * @param  dataContainer $dataContainer contains needed Data
	 */
	protected function entryPoint($dataContainer) {

		parent::entryPoint($dataContainer);
		$this->_interface = $dataContainer->getInterface();
	}

	/**
	 * Fetches the Data of the Order from the Server
	 *
	 * Also contains the data of the Priceclass and the Meal
	 *
	 * @param  int $orderId The ID of the Order
	 */
	protected function orderdataLoad($orderId) {

		$stmt = $this->_pdo->prepare(
			'SELECT o.*, u.credit AS userCredits,
				m.ID AS mealId, sc.ID AS solicouponId,
				pc.price AS price
			FROM BabeskOrders o
			JOIN SystemUsers u ON u.ID = :userId
			JOIN BabeskMeals m ON o.MID = m.ID
			LEFT JOIN BabeskSoliCoupons sc ON sc.UID = u.ID AND
				m.date BETWEEN sc.startdate AND sc.enddate
			JOIN BabeskPriceClasses pc ON m.price_class = pc.pc_ID AND u.GID = pc.GID
			WHERE o.ID = :orderId
			-- If multiple BabeskSoliCoupons at same time active, group to one
			GROUP BY sc.UID;
		');
		$stmt->execute(array(
			'userId' => $_SESSION['uid'],
			'orderId' => $orderId
		));
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if(count($data)) {
			$this->_orderData = $data[0];
			$this->_orderData['hasValidCoupon'] =
				$this->_orderData['solicouponId'] !== NULL;
		}
		else {
			throw new Exception('Could not fetch the Orderdata');
		}
	}

	/**
	 * Checks if the cancelling of the order by the User is Allowed
	 *
	 * Displays an Error and dies when the ordercancelling is not allowed
	 *
	 * @return boolean True if the Order-Cancel is legal
	 */
	protected function orderCancelLegalCheck() {

		if(!$this->_orderData['fetched']) {
			if($this->ordercancelLastLegalDateCheck()) {
				return true;
			}
			else {
				$this->_interface->dieError('Es ist zu spät diese Bestellung abzubestellen!');
			}
		}
		else {
			$this->_interface->dieError(
				'Die Bestellung wurde bereits abgeholt!');
		}
	}

	/**
	 * Checks if the User is allowed to cancel the Order at the time
	 *
	 * @return boolean True if he is allowed, else false
	 */
	protected function ordercancelLastLegalDateCheck() {

		$datemod = $this->lastOrdercancelDatemodGet();
		$mealdate = strtotime($this->_orderData['date']);
		$timestamp = strtotime($datemod, $mealdate);

		return $timestamp >= time();
	}

	/**
	 * Fetches and returns the date-modifier the last order is allowed
	 *
	 * Dies displaying an Error when data could not be fetched
	 *
	 * @return String The date-modifier, usable by strtotime
	 */
	protected function lastOrdercancelDatemodGet() {

		try {
			$data = TableMng::query('SELECT * FROM SystemGlobalSettings
				WHERE name = "ordercancelEnddate"');

		} catch (Exception $e) {

			$this->_interface->dieError('Error fetching ordercancelEnddate!');
		}

		if(count($data)) {
			return $data[0]['value'];
		}
		else {
			$this->_interface->dieError('ordercancelEnddate ist nicht gesetzt! Administrator verständigen.');
		}
	}

	/**
	 * Cancels the Order and repays the money to the User
	 */
	protected function orderCancel() {

		$this->_isSoli = $this->userHasValidCoupon();
		$this->_isSolipriceEnabled = $this->isSolipriceEnabledGet();

		try {
			$amount = $this->amountToRepayGet();
			TableMng::getDb()->autocommit(false);
			$this->repay($amount);
			$this->orderDbEntryDelete($this->_orderData['ID']);
			TableMng::getDb()->autocommit(true);

		} catch (Exception $e) {
			$this->_interface->dieError(
				'Konnte die Bestellung nicht abbrechen' . $e->getMessage());
		}
	}

	/**
	 * Calculates the Amount that should be repayed to the User
	 *
	 * @return int The Amount to be repayed
	 */
	protected function amountToRepayGet() {

		if($this->_isSoli && $this->_isSolipriceEnabled) {
			return $this->soliPriceGet();
		}
		else {
			return $this->_orderData['price'];
		}
	}

	/**
	 * Fetches the Mealprice for Soli-Users and returns it
	 *
	 * @throws Exception If Soliprice could not be fetched
	 * @return int The Price of the Meal for Soli-Users
	 */
	protected function soliPriceGet() {

	$seperatePricesEnabled = TableMng::query('SELECT * FROM SystemGlobalSettings
			WHERE name = "seperateSoliPrices"');
		if($seperatePricesEnabled[0]['value'] == "1"){
			$mid = $this->_orderData['MID'];
			$pc_ID = TableMng::query("SELECT price_class FROM BabeskMeals WHERE ID = $mid ");
			$pc_ID = $pc_ID[0]['price_class'];
			$soliPrice = TableMng::query("SELECT soliPrice FROM BabeskPriceClasses WHERE pc_ID = $pc_ID GROUP BY pc_ID");
			if(count($soliPrice)) {
				return $soliPrice[0]['soliPrice'];
			}
			else {
				throw new Exception('Soli-Price not set, but Coupon used!');
			}
		}else{
			$soliPrice = TableMng::query('SELECT * FROM SystemGlobalSettings
				WHERE name = "soli_price"');
			if(count($soliPrice)) {
				return $soliPrice[0]['value'];
			}
			else {
				throw new Exception('Soli-Price not set, but Coupon used!');
			}
		}
	}

	/**
	 * Checks if the User has a valid Solicoupon for the Order
	 *
	 * @param  int $mealId The ID of the Meal
	 * @return boolean True if the User has a Valid Coupon, else false
	 */
	protected function userHasValidCoupon() {

		$hasCoupon = TableMng::query("SELECT COUNT(*) AS count
			FROM BabeskSoliCoupons sc
			JOIN BabeskMeals m ON m.ID = {$this->_orderData['mealId']}
			JOIN SystemUsers u ON u.ID = sc.UID
			WHERE m.date BETWEEN sc.startdate AND sc.enddate AND
				sc.UID = $_SESSION[uid] AND u.soli = 1");

		return $hasCoupon[0]['count'] != '0';
	}

	/**
	 * Repays the Money of the cancelled Order back to the User
	 *
	 * @param  int $amount The Amount of money to repay
	 */
	protected function repay($amount) {

		$newBalance = $this->_orderData['userCredits'] + $amount;
		$newBalanceStr = str_replace(',', '.', (string) $newBalance);
		TableMng::query("UPDATE SystemUsers SET credit = '$newBalanceStr' WHERE $_SESSION[uid] = ID");
	}

	/**
	 * Deletes the Order-Entry (and SoliOrder-Entry) in the Db
	 *
	 * @param  int $orderId The ID of the Order to delete
	 */
	protected function orderDbEntryDelete($orderId) {

		TableMng::query("DELETE FROM BabeskOrders WHERE ID = $orderId");

		if($this->_isSoli) {
			TableMng::query("DELETE FROM BabeskSoliOrders WHERE ID = $orderId");
		}
	}

	/**
	 * Fetches if the soliprice is enabled
	 *
	 * Dies displaying a Message on Error
	 */
	protected function isSolipriceEnabledGet() {

		try {
			$stmt = $this->_pdo->query("SELECT `value` FROM `SystemGlobalSettings`
				WHERE `name` = 'solipriceEnabled'");

			return $stmt->fetchColumn() == '1';

		} catch (PDOException $e) {
			$this->_interface->dieError(_g(
				'Could not check if the Soliprice is enabled!'));
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	private $smartyPath;

	protected $_orderData;

	protected $_interface;

	protected $_smarty;

	protected $_isSoli;

	protected $_isSolipriceEnabled;
}
?>
