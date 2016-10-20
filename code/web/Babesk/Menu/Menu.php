<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_WEB . '/Babesk/Babesk.php';

class Menu extends Babesk {

	////////////////////////////////////////////////////////////////////////////////
	//Attributes
	private $smartyPath;

	/**
	 * The DateModifier for strtotime
	 * @var [type]
	 */
	protected $_lastCancel;

	////////////////////////////////////////////////////////////////////////////////
	//Constructor
	public function __construct($name, $display_name, $path) {
		parent::__construct($name, $display_name, $path);
		$this->smartyPath = PATH_SMARTY_TPL . '/web' . $path;
	}

	////////////////////////////////////////////////////////////////////////////////
	//Methods
	public function execute($dataContainer) {
		//No direct access
		defined('_WEXEC') or die("Access denied");

		require_once PATH_ACCESS . '/GlobalSettingsManager.php';
		require_once PATH_ACCESS . '/OrderManager.php';
		require_once PATH_ACCESS . '/MealManager.php';

		$smarty = $dataContainer->getSmarty();

		$orderManager = new OrderManager('BabeskOrders');
		$mealManager = new MealManager('BabeskMeals');

		$meal = array();
		$orders_existing = true;
		$meal_problems = false;
		$mealHistory = array();

		try {
			$orders = $orderManager->getAllOrdersOfUser($_SESSION['uid'], strtotime(date('Y-m-d')));
		} catch (MySQLVoidDataException $e) {
			$smarty->assign('error', 'Keine Bestellungen vorhanden.');
			$orders_existing = false;
		} catch (Exception $e) {
			die('Error: ' . $e);
		}
		if ($orders_existing) {
			$today = date('Y-m-d');
			$hour = date('H', time());
			$this->_lastCancel = $this->lastOrdercancelDatemodGet();
			foreach ($orders as $order) {
				try {
					$mealname = $mealManager->getEntryData($order['MID'], 'name');
				} catch (MySQLVoidDataException $e) {
					$meal_problems = true;
					$smarty->assign('error', '<p class="error">Zu bestimmten Bestellung(-en) fehlen Daten einer Mahlzeit! Bitte benachrichtigen sie den Administrator!</p>');
					continue;
				}
				if (!$order['fetched'] AND $order['date'] >= $today) {

					//fetch last_order_time from database and compare with actual time
					$hour = date('H:i', time());
					$cancelAllowed = $this->isAllowedToCancel($order['date']);

					$meal[] = array(
						'date' => formatDate($order["date"]),
						'name' => $mealname["name"],
						'orderID' => $order['ID'],
						'cancel' => $cancelAllowed);
				} else {
					$mealHistory[] = array(
						'date' => formatDate($order["date"]),
						'name' => $mealname["name"],
						'orderID' => $order['ID'],
						'cancel' => false,
						'fetched' =>$order['fetched']);
				}
			}
		}
		if (!count($meal) && !count($mealHistory) && !$meal_problems) {
			//no new meals there
			$smarty->assign('error', 'Keine Bestellungen vorhanden.');
		}
		$smarty->assign('meal', $meal);
		$smarty->assign('mealHistory', $mealHistory);
		$smarty->display($this->smartyPath . 'menu.tpl');
	}

	/**
	 * Fetches a date-modifier indicating when the Orders are canceable
	 *
	 * @return string The Date
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
	 * Checks if the User is allowed to Cancel the Order
	 * @param  date    $mealdate The date of the meal
	 * @return boolean           if the User is allowed to cancel this Meal
	 */
	protected function isAllowedToCancel($mealdate) {

		$mealdate = strtotime($mealdate);
		$timestamp = strtotime($this->_lastCancel, $mealdate);

		return $timestamp >= time();
	}
}
?>
