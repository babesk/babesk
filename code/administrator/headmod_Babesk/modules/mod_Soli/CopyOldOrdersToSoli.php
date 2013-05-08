<?php

/**
 * A class containing functionality that Copies orders of Soli-using Users
 * from orders to soli_orders, solving conflicts caused by adding soli_coupons
 * afterwards
 *
 * @author  Pascal Ernst <pascal.cc.ernst@gmail.com>
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
	
			foreach(self::$_soliData as $soliData) {
			
				
			$result= TableMng::query(sprintf('SELECT count(id) FROM soli_coupons WHERE '.$soliData['userId'].'=UID AND (SELECT date from meals WHERE ID='.$soliData['existMealAndPriceclass'].
						') BETWEEN startdate AND enddate'),true); 
			if ($result[0]['count(id)']>0) {
				try {
					TableMng::query("INSERT INTO `soli_orders` (`ID`, `UID`, `date`, `IP`, `ordertime`, `fetched`, `mealname`, `mealprice`,
							 `mealdate`, `soliprice`) VALUES (NULL, '".$soliData['userId']."', '2013-05-01', '', CURRENT_TIMESTAMP, 
							 '0', 'test', '3.50', '2013-05-10', '1.00')",false);
				} catch (Exception $e) {
					self::$_interface->dieError('Fehler beim &Uuml;bertragen!');
				}
			}
			
				 
				
			}
			

		} catch (Exception $e) {
			self::$_interface->dieError('Konnte die Daten nicht abrufen');
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

		self::$_soliData = TableMng::query(sprintf(
			'SELECT u.ID AS userId, CONCAT(u.forename, " ", u.name) AS name,
			-- Does the Meal and the priceclass still exist?
			(SELECT m.ID FROM meals m
				JOIN price_classes pc ON m.price_class = pc.ID
				WHERE m.ID = o.MID
			) AS existMealAndPriceclass
			FROM users u,
			orders o WHERE o.UID = u.ID
			AND u.soli = 1 AND
(SELECT COUNT(*) FROM soli_orders so
				WHERE o.date = so.date -- Has same Date?
				AND o.UID = so.UID -- Has same UserId?
				AND (SELECT m.name FROM meals m WHERE o.MID = m.ID)
					= so.mealname -- Has same mealname?
			) = 0'), true);
	}

	/**
	 * Fetches all soli_coupons from the server
	 *
	 * @throws Exception if something has gone wrong while fetching the coupons
	 * @return void
	 */
	protected static function couponDataFetch() {

		$coupons = TableMng::query('SELECT * FROM soli_coupons', true);

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

	protected static function soliDataProcess() {

		foreach(self::$_soliData as $order) {

		}
	}

	/**
	 * Checks if the order has to be add to the soli_orders-Table
	 * @param  array() $order The order
	 * @return bool true if it has to be add
	 */
	protected static function soliDataCheck($order) {

		if($order['orderedAsSoli'] == true) {
			if($order['existMealAndPriceclass'] == true) {
				if(self::orderedWithCoupon($order)) {

				}
			}
			else {

			}
		}
		else {
			self::$_errors[] = sprintf('');
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
		$orderTimestamp = strtotime($order['date']);

		foreach($this->_couponData as $coupon) {
			if($coupon->getUserId() == $orderUserId) {
				if($coupon->timestampIsCovered($orderTimestamp)) {
					return true;
				}
			}
		}

		return false;
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

		if($this->datesSet()) {
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
	public function timestampIsCovered($timstamp) {

		if($this->_startdate <= $timestamp && $this->enddate >= $timestamp) {
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
