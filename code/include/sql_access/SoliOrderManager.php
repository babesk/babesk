<?php

require_once PATH_ACCESS . '/TableManager.php';

class SoliOrderManager extends TableManager {

	function __construct() {
		parent::__construct('BabeskSoliOrders');
	}

	/**
	 * Adds a order to the soli_order-table
	 * Enter description here ...
	 * @param unknown_type $orderID
	 * @param unknown_type $UID
	 * @param unknown_type $IP
	 * @param unknown_type $date
	 * @param unknown_type $mealname
	 * @param unknown_type $mealprice
	 * @param unknown_type $mealdate
	 * @param unknown_type $soliprice
	 */
	function addSoliOrder($orderID, $UID, $IP, $date, $mealname, $mealprice, $mealdate, $soliprice) {
		str_replace(',', '.', $soliprice);
		parent::addEntry('ID', $orderID, 'UID', $UID, 'IP', $IP, 'ordertime', date("Y-m-d h:i:s"), 'date', $date,
						 'mealname', $mealname, 'mealprice', $mealprice, 'mealdate', $mealdate, 'soliprice',
						 $soliprice);
	}

	/**
	 * Checks if an order with the given ID is existing
	 * @param numeric_string $ID The ID of the order
	 */
	function isExisting($ID) {
		try {
			$this->searchEntry('ID='.$ID);
		} catch (MySQLVoidDataException $e) {
			return false;
		}
		return true;
	}

	/**
	 * Returns all Orders of soli_orders sorted by date
	 */
	function getSortedOrders() {
		$orders = array();
		$query = sql_prev_inj(sprintf('SELECT * FROM %s ORDER BY %s', $this->tablename, 'date'));
		$result = $this->db->query($query);
		if (!$result)
			throw new MySQLConnectionException($this->db->error);
		while ($order = $result->fetch_assoc()) {
			$orders[] = $order;
		}
		if (!$orders || !count($orders)) {
			throw new MySQLVoidDataException('No Orders found in getSortedOrers');
		}
		return $orders;
	}

	/**
	 * returns the Orders that are dated [mealdate] between the first and the second Date
	 * Format can be timestamp or YYYY-MM-DD
	 */
	function getOrdersBetMealdate($startdate, $enddate) {
		///@todo sicher das Timestamp hier von MySQL automatisch konvertiert wird?
		return parent::getTableData(sprintf('date BETWEEN "%s" AND "%s"', $startdate, $enddate));
	}

	function getOrdersByUserandMealdate($uid, $date) {
		return $this->getTableData(sprintf('UID = "%s" AND mealdate = "%s"', $uid, $date));
	}
}

?>