<?php

/**
 * A class containing functionality that Copies orders of Soli-using Users
 * from orders to soli_orders, solving conflicts caused by adding soli_coupons
 * afterwards
 */
class CopyOldOrdersToSoli {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public static function init($interface) {

		self::$_interface = $interface;

		self::$_errors = array();
		self::$_copied = array();
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public static function execute() {

		try {
			self::soliDataFetch();
			self::couponDataFetch();
			self::solipriceFetch();
			self::upload();

			self::errorsShow();
			self::$_interface->dieMsg('Die Bestellungen wurden erfolgreich verarbeitet.');

		} catch (Exception $e) {
			self::$_interface->dieError('Konnte die alten Bestellungen nicht verarbeiten.' . $e->getMessage());
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	/**
	 * Fetches the data of Soli-Users to be processed
	 *
	 * @throws Exception if something has gone wrong while fetching the data
	 * @return array(...)
	 */
	protected static function soliDataFetch() {

		try {
			self::$_soliData = TableMng::query(sprintf(
				'SELECT u.ID AS userId, o.ID AS orderId, o.fetched AS fetched,
					m.name AS mealname, pc.price AS price, m.date AS mealdate,
					o.ID AS orderId,
					CONCAT(u.forename, " ", u.name) AS userWholename,
					o.ordertime AS ordertime,
					/*Does the Meal and the priceclass still exist?*/
					(SELECT m.ID FROM BabeskMeals m
						JOIN BabeskPriceClasses pc ON m.price_class = pc.pc_ID
						WHERE m.ID = o.MID
						GROUP BY pc.pc_ID
					) AS existMealAndPriceclass
				FROM SystemUsers u
				JOIN BabeskOrders o ON o.UID = u.ID
				/*We want to check if meal exists manually (for error-output), so using LEFT JOIN instead of JOIN*/
				LEFT JOIN BabeskMeals m ON o.MID = m.ID
				/*Fetch the price of the meal for the user*/
				LEFT JOIN
					(SELECT ID, pc_ID, GID, price FROM BabeskPriceClasses) pc
						ON pc.pc_ID = m.price_class AND pc.GID = u.GID
				WHERE /*does the order already exist in soli_orders?*/
						(SELECT COUNT(*) FROM BabeskSoliOrders so
					 	WHERE o.ID = so.ID) = 0'), true);

		} catch (MySQLVoidDataException $e) {
			self::$_interface->dieError('Alle passenden Bestellungen wurden schon korrekt abgelegt oder es gibt keine Bestellungen mit soli-Status');
		}

	}

	/**
	 * Fetches all soli_coupons from the server
	 *
	 * @throws Exception if something has gone wrong while fetching the coupons
	 * @return void
	 */
	protected static function couponDataFetch() {

		$coupons = TableMng::query('SELECT * FROM BabeskSoliCoupons');

		foreach($coupons as $coupon) {
			try {
				$couponObj = new CopyOldOrdersToSoliCoupon($coupon);

			} catch (Exception $e) {
				self::$_errors[] = sprintf('Konnte einen Coupon nicht verarbeiten.');
				continue;
			}

			self::$_couponData[] = $couponObj;
		}
	}

	/**
	 * Processes the data and uploads them to the Db. On error, nothing gets
	 * comitted
	 */
	protected static function upload() {

		TableMng::getDb()->autocommit(false);

		$stmt = TableMng::getDb()->prepare(
			'INSERT INTO `BabeskSoliOrders`
				(`ID`, `UID`, `date`, `IP`, `ordertime`, `fetched`,
					`mealname`, `mealprice`, `mealdate`, `soliprice`)
			VALUES (?, ?, ?, "", ?, ?, ?, ?, ?, ?)');

		foreach(self::$_soliData as $order) {
			if(self::soliDataCheck($order)) {

				$price = (isset(self::$_soliprice) && self::$_soliprice != '')
					? self::$_soliprice : 0;

				$stmt->bind_param('sssssssss', $order['orderId'], $order['userId'],
					$order['mealdate'], $order['ordertime'], $order['fetched'],
					$order['mealname'], $order['price'], $order['mealdate'],
					$price);
				if($stmt->execute()) {
					//good for us
				}
				else {
					echo $stmt->error;
					throw new Exception(
						'Could not execute an upload successfully');
				}
			}
		}
		$stmt->close();

		TableMng::getDb()->autocommit(true);
	}

	/**
	 * Checks if the order has to be add to the soli_orders-Table
	 * @param  array() $order The order
	 * @return bool true if it has to be add
	 */
	protected static function soliDataCheck($order) {

		if($order['existMealAndPriceclass'] == true) {
			if(self::orderedWithCoupon($order)) {

				return true;
			}
		}
		else {
			self::$_errors[] = sprintf('The Meal or Priceclass does not exist anymore for the order with the ID %s',
				$order['orderId']);

			return false;
		}
	}

	/**
	 * Checks if an order was ordered while the User had a active coupon
	 *
	 * @param  array() $order The order
	 * @return bool true if the order was ordered while having an active coupon
	 */
	protected static function orderedWithCoupon($order) {

		$orderUserId = $order['userId'];
		$orderTimestamp = strtotime($order['mealdate']);

		foreach(self::$_couponData as $coupon) {
			if($coupon->getUserId() == $orderUserId) {
				if($coupon->timestampIsCovered($orderTimestamp)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Fetches the Price that soli-users should pay when ordering meals
	 *
	 * Saves it into the protected variable $_soliprice
	 */
	protected static function solipriceFetch() {

		try {
			$res = TableMng::query('SELECT value FROM SystemGlobalSettings
				WHERE name = "soli_price"');

		} catch (Exception $e) {
			throw new Exception('Could not fetch the soliprice');
		}

		self::$_soliprice = $res[0]['value'];
	}

	/**
	 * Shows all of the non-scriptkilling Errors to the User
	 */
	protected static function errorsShow() {

		foreach(self::$_errors as $error) {
			self::$_interface->showError($error);
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	protected static $_interface;

	/**
	 * All orders ordered by Soli-Users
	 *
	 * @var array(array(...))
	 */
	protected static $_soliData;

	/**
	 * An array of CopyOldOrdersToSoliCoupon-objects. It contains all of the
	 * Soli-Coupons
	 *
	 * @var array(CopyOldOrdersToSoliCoupon)
	 */
	protected static $_couponData;

	/**
	 * An array of strings telling the user that something went wrong with this
	 * specific order
	 *
	 * @var array(string)
	 */
	protected static $_errors;

	/**
	 * An array of strings telling the user what has finished successfully
	 *
	 * @var array(string)
	 */
	protected static $_copied;

	/**
	 * The Price of meals for soli-users
	 * @var float
	 */
	protected static $_soliprice;

}

class CopyOldOrdersToSoliCoupon {

	/**
	 * Constructs a new Coupon and sets its startdate- and enddate-timestamps
	 *
	 * @param array(...) $coupon An Array describing a Coupon
	 * @throws Exception if the $coupon could not be parsed
	 */
	public function __construct($coupon) {

		$this->_coupon = $coupon;

		if(!$this->datesSet()) {
			throw new Exception('Konnte die Daten des Coupons nicht parsen');
		}
	}

	/**
	 * Returns the Coupon-data
	 *
	 * @return array(...) the data of the Coupon itself
	 */
	public function couponGet() {
		return $this->_coupon;
	}

	/**
	 * Returns the UserId linked wit the Coupon
	 *
	 * @return numeric
	 */
	public function getUserId () {

		return $this->_coupon['UID'];
	}

	/**
	 * Checks if the given timestamp is between the dates of this Coupon
	 *
	 * @return bool true if the timestamp is between the start- and enddate of
	 * this coupon
	 */
	public function timestampIsCovered($timestamp) {

		if($this->_startdate <= $timestamp && $this->_enddate >= $timestamp) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Parses the Date-strings of the Coupons to timestamps
	 *
	 * @return bool false if one of the strings could not be parsed, true on
	 * success
	 */
	protected function datesSet() {

		$this->_startdate = strtotime($this->_coupon['startdate']);
		$this->_enddate = strtotime($this->_coupon['enddate']);

		if($this->_startdate === false || $this->_enddate === false) {
			return false;
		}
		return true;
	}

	protected $_coupon;
	protected $_startdate;
	protected $_enddate;
}

?>
