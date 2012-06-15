<?php

require_once PATH_INCLUDE . '/Module.php';

class Order extends Module {

	////////////////////////////////////////////////////////////////////////////////
	//Attributes
	private $smartyPath;
	private $modulePath;
	private $webInterface;
	
	////////////////////////////////////////////////////////////////////////////////
	//Constructor
	public function __construct($name, $display_name, $path) {
		parent::__construct($name, $display_name, $path);
		$this->modulePath = $path;
		$this->smartyPath = PATH_SMARTY . '/templates/web' . $path;
		require_once PATH_SITE . '/web/WebInterface.php';
	}
	
	////////////////////////////////////////////////////////////////////////////////
	//Methods
	public function execute() {
		//No direct access
		defined('_WEXEC') or die("Access denied");
		require_once 'order_constants.php';
		require_once PATH_ACCESS . '/GlobalSettingsManager.php';
		require_once PATH_ACCESS . '/SoliOrderManager.php';
		require_once PATH_ACCESS . '/SoliCouponsManager.php';
		require_once PATH_ACCESS . '/MealManager.php';
		require_once PATH_ACCESS . '/UserManager.php';
		require_once PATH_ACCESS . '/OrderManager.php';
		require_once PATH_ACCESS . '/PriceClassManager.php';
		global $smarty;
		global $logger;

		$this->webInterface = new WebInterface($smarty);
		
		$mealManager = new MealManager();
		$userManager = new UserManager();
		$orderManager = new OrderManager();
		$soliOrderManager = new SoliOrderManager();
		$soliCouponManager = new SoliCouponsManager();
		$priceClassManager = new PriceClassManager();
		$gbManager = new GlobalSettingsManager();
		
		$smarty->assign('babesk_version', file_get_contents("../version.txt"));
		
		if (isset($_GET['order'])) {
		
			$payment = NULL;
		
			try {//checking mealdata
				inputcheck($_GET['order'], 'id');
				$meal = $mealManager->getEntryData($_GET['order']);
			} catch (WrongInputException $e) {
				$this->webInterface->dieError(ERR_INP_MID);
			} catch (Exception $e) {
				$logger->log('WEB|order', 'MODERATE',
						sprintf('failed fetchign data with MID %s; %s', $_GET['order'], $e->getMessage()));
				$this->webInterface->dieError(ERR_ORDER);
			}
			$meal['price'] = $priceClassManager->getPrice($_SESSION['uid'], $meal['ID']);
		
			if ('POST' == $_SERVER['REQUEST_METHOD']) {
				////////////////////////////////////////////////////
				//Pay for meal
				try {
					try {
						//"Pay", substract the price for the menu from the users account
						$payment = $priceClassManager->getPrice($_SESSION['uid'], $_GET['order']);
					} catch (Exception $e) {
						$logger->log('WEB|order', 'MODERATE',
								sprintf('The function getPrice failed with UID %s and order %s; %s', $_SESSION['uid'],
										$_GET['order'], $e->getMessage()));
						$this->webInterface->dieError(ERR_ORDER);
						die();
					}
		
					if ($soliCouponManager->HasValidCoupon($_SESSION['uid'], $meal['date'])) {
						$payment = $gbManager->getSoliPrice();
					}
		
					// 			if ($userManager->isSoli($_SESSION['uid'])) {
					// 				$payment = $gbManager->getSoliPrice();
					// 			}
		
					if (!$userManager->changeBalance($_SESSION['uid'], -$payment)) {
						$smarty->display($this->smartyPath . 'failed.tpl');
						die();
					}
				} catch (Exception $e) {
					$logger->log('WEB|order', 'MODERATE',
							sprintf('Error while handling the order for ' . 'UID %s; Order: %s; Error: %s',
									$_SESSION['uid'], $_GET['order'], $e->getMessage()));
					$this->webInterface->dieError(ERR_ORDER);
					die();
				}
				//////////////////////////////////////////////////
				//add order
				try {
					$orderID = $orderManager->getNextAutoIncrementID();
					$orderManager->addOrder($_GET['order'], $_SESSION['uid'], $_SERVER['REMOTE_ADDR'], $meal['date']);
					if ($soliCouponManager->HasValidCoupon($_SESSION['uid'], $meal['date']))
						$soliOrderManager->addSoliOrder($orderID, $_SESSION['uid'], $_SERVER['REMOTE_ADDR'], $meal['date'],
								$meal['name'], $meal['price'], $meal['date'],
								$gbManager->getSoliPrice());
				} catch (Exception $e) {
		
					//meal couldn't be ordered so give the user his money back
					$userManager->changeBalance($_SESSION['uid'], $payment);
					$logger->log('WEB|ORDER', 'MODERATE',
							sprintf('Error while handling the order for ' . 'UID %s; Order: %s; Error: %s',
									$_SESSION['uid'], $_GET['order'], $e->getMessage()));
					$this->webInterface->dieError(ERR_ORDER);
					die();
				}
				//////////////////////////////////////////////////
				//finished
				$smarty->display('web/header.tpl');
				echo 'Am ' . formatDate($meal['date']) . ' das Men&uuml; ' . $meal['name']
				. ' erfolgreich bestellt. <a href="index.php">Weiter</a>';
				$smarty->display('web/footer.tpl');
			} else {
				//////////////////////////////////////////////////
				//show confirm-order-form
				$smarty->display('web/header.tpl');
				if (strtotime($result['date']) < strtotime(date('Y-m-d')))
					exit('Error: Fehlerhaftes Datum');
				echo 'Am ' . formatDate($result['date']) . ' das Men&uuml; ' . $result['name'] . ' bestellen?<br />';
				echo '<form method="POST" action="index.php?section=order&order=' . $_GET['order']
				. '">
				<input type="submit" value="Bestellen">
				</form>';
				$smarty->display('web/footer.tpl');
			}
		}
		//Show list of meals that can be ordered
		else {
			$mealManager = new MealManager();
			$pcManager = new PriceClassManager();
			$gsManager = new GlobalSettingsManager();
			$userManager = new UserManager();
		
			$hour = date('H:i', time());
			// To change the timewindow the orders can be ordered, just change $enddate (and $last_order_time)
			//first date to show the meals
			$date = time();
			//last date where meals are shown
			$enddate = strtotime('+2 week', strtotime('last Sunday'));
			/*
			 * $meallist consists of multiple arrays:
			* 1. The weeks (The weeknumber of the week in the year; -> Compatible only when beginning of date and end of date
					* 		is not more than 1 year (which is a pretty damn long time).
					* 2. Every week consists of days (Here: 1 = Monday, 2 = Tuesday, ... 7 = Sunday.)
					* 		Additionaly, there is an index named "date", which lists the dates for each individual day
					* 3. Every day has meals
					* 4. Every meal has mealdata (like ID, description, name, ...)
					*/
			//Ordering only possible until $last_order_time
		
			$last_order_time = $gsManager->getLastOrderTime();
			if (str_replace(":", "", $hour) > str_replace(":", "", $last_order_time)) {
				$date += $day_in_secs;
			}
			try {
				$sql_meals = $mealManager->get_meals_between_two_dates(date('Y-m-d', $date), date('Y-m-d', $enddate),
						'date, price_class');
			} catch (MySQLVoidDataException $e) {
				$this->webInterface->dieError(ERR_NO_ORDERS);
			} catch (Exception $e) {
				$smarty->assign('message', ERR_MYSQL . '<br>' . $e->getMessage());
			}
		
			//////////////////////////////////////////////////
			//Sort the meals
		
			foreach ($sql_meals as &$meal) {
				$meal_day = date('N', strtotime($meal['date']));
				$meal_weeknum = date('W', strtotime($meal['date']));
				if ($soliCouponManager->HasValidCoupon($_SESSION['uid'], $meal['date']))
					$meal['price'] = $gsManager->getSoliPrice();
				else
					$meal['price'] = $pcManager->getPrice($_SESSION['uid'], $meal['ID']);
		
				$meallist[$meal_weeknum][$meal_day][] = $meal;
				//The date of the beginning of the week (here monday). +7 because of negative meal_day setting the date 1 week behind
				$meallist[$meal_weeknum]['date'][1] = date('d.m.Y',
						strtotime(sprintf('+%s day', -$meal_day + 1),
								strtotime($meal['date'])));
				$meallist[$meal_weeknum]['date'][2] = date('d.m.Y',
						strtotime(sprintf('+%s day', -$meal_day + 2),
								strtotime($meal['date'])));
				$meallist[$meal_weeknum]['date'][3] = date('d.m.Y',
						strtotime(sprintf('+%s day', -$meal_day + 3),
								strtotime($meal['date'])));
				$meallist[$meal_weeknum]['date'][4] = date('d.m.Y',
						strtotime(sprintf('+%s day', -$meal_day + 4),
								strtotime($meal['date'])));
				$meallist[$meal_weeknum]['date'][5] = date('d.m.Y',
						strtotime(sprintf('+%s day', -$meal_day + 5),
								strtotime($meal['date'])));
				//Saturday and Sunday may be important in the future?
				$meallist[$meal_weeknum]['date'][6] = date('d.m.Y',
						strtotime(sprintf('+%s day', -$meal_day + 6),
								strtotime($meal['date'])));
				$meallist[$meal_weeknum]['date'][7] = date('d.m.Y',
						strtotime(sprintf('+%s day', -$meal_day + 7),
								strtotime($meal['date'])));
			}
			try {
				$itxt_arr = $gsManager->getInfoTexts();
			} catch (Exception $e) {
				$this->webInterface->dieError('Error getting infotexts:' . $e->getMessage());
			}
			$smarty->assign('meallist', $meallist);
			$smarty->assign('infotext', $itxt_arr);
			$smarty->display($this->smartyPath . 'order.tpl');
		}
	}
}
?>