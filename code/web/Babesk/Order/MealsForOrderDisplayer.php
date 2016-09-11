<?php

require_once 'MealToDisplay.php';
require_once 'Mealweek.php';

/**
 * Displays the Meallist from which the Meals can be ordered
 */
class MealsForOrderDisplayer {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct() {

	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function display($dataContainer, $smartypath) {

		$this->_pdo = $dataContainer->getPdo();
		$this->_interface = $dataContainer->getInterface();
		$this->_smarty = $dataContainer->getSmarty();
		$this->_isSolipriceEnabled = $this->isSolipriceEnabledGet();
		$this->settingsFetch();
		$this->infotextsFetch();
		$meals = $this->mealsToBeDisplayedGet();
		$this->meallistCreate($meals);
		$this->_smarty->assign("displayMealsStartdate",
			$this->_displayMealsStartdate);
		$this->_smarty->assign("displayMealsEnddate",
			$this->_displayMealsEnddate);
		$this->_smarty->assign("orderEnddate", $this->_orderEnddate);
		$this->_smarty->assign("ordercancelEnddate",
			$this->_ordercancelEnddate);
		$this->_smarty->assign("mealweeklist", $this->_mealweeklist);
		$this->_smarty->assign("infotext1", $this->_infotext1);
		$this->_smarty->assign("infotext2", $this->_infotext2);

		$this->_smarty->display($smartypath . 'order.tpl');
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function settingsFetch() {

		$data = TableMng::query('SELECT * FROM SystemGlobalSettings
			WHERE name = "displayMealsStartdate" OR
				name = "displayMealsEnddate" OR
				name = "orderEnddate" OR
				name = "ordercancelEnddate";');

		$this->settingsParse($data);
	}

	protected function settingsParse($data) {

		$this->_nowWithoutHoursMinsSecs = strtotime(date('Y-m-d'));

		if(count($data)) {
			foreach($data as $entry) {
				if($entry['name'] == 'orderEnddate') {
					$this->_orderEnddate = $entry['value'];
				}
				else {
					$varname = '_' . $entry['name'];
					$this->$varname = $this->dateformatToDate($entry['value']);
				}
			}
		}
		else {
			throw new Exception('Could not fetch all needed data to validate what meals are allowed to get ordered');
		}
		if(!$this->settingsParseCheck()) {
			throw new Exception('Could not fetch all needed data to validate what meals are allowed to get ordered');
		}
	}

	protected function settingsParseCheck() {

		if(isset($this->_displayMealsStartdate, $this->_displayMealsEnddate, $this->_orderEnddate, $this->_ordercancelEnddate)) {
			return true;
		}
		else {
			return false;
		}
	}

	protected function dateformatToDate($value) {

		$fixedNow = $this->sundayWeekFix(
			$value,
			$this->_nowWithoutHoursMinsSecs);
		$time = date('Y-m-d', strtotime($value, $fixedNow));

		if($time !== false) {
			return $time;
		}
		else {
			throw new Exception(
				'displayMealsStartdate is not well formatted!');
		}
	}

	/**
	 * Solves the Problem that PHP thinks of Sunday as the beginning of a week
	 *
	 * Using "Monday last week" in strtotime when it is sunday will result in
	 * the Value of _next_ Monday, not the Monday of the last week. This
	 * function changes (fixes) the behaviour
	 * Usage:
	 * If you want the fix to be applied, put a "fixSundayWeek" into your
	 * dateParam
	 *
	 * @param  String $dateParam The String that is supposed to be used by
	 * strtotime to change the Date. May or may not contain fixSundayWeek
	 * @param  int $now The Timestamp to check for if it is Sunday
	 * @return int The fixed $now
	 */
	protected function sundayWeekFix($dateParam, $now) {

		if(strstr($dateParam, 'fixSundayWeek') !== false) {
			$dateParamCleaned = str_replace('fixSundayWeek', '', $dateParam);

			if('sunday' == date('l', $time)) {
				return strtotime('-1 week', $now);
			}
			else {
				return $now;
			}
		}

		return $now;
	}


	/**
	 * Checks if the User has a valid Solicoupon for the given Meal
	 *
	 * @param  int $mealId The ID of the Meal
	 * @return boolean True if the User has a valid Coupon, else false
	 */
	protected function userHasValidCoupon($mealId) {

		$hasCoupon = TableMng::query("SELECT COUNT(*) AS count
				FROM BabeskSoliCoupons sc
				JOIN BabeskMeals m ON m.ID = $mealId
				WHERE m.date BETWEEN sc.startdate AND sc.enddate AND
				UID = $_SESSION[uid]");

		return $hasCoupon[0]['count'] != '0';
	}

	protected function mealsToBeDisplayedGet() {

		$startdate = $this->_displayMealsStartdate;
		$enddate = $this->_displayMealsEnddate;

		try {

			$hasSoli = TableMng::query("SELECT soli FROM SystemUsers
					WHERE ID = $_SESSION[uid]");

			$meals = TableMng::query("SELECT m.*, pc.price AS price,
					pc.pc_ID AS priceclassId, pc.name AS priceclassName
				FROM BabeskMeals m
				JOIN SystemUsers u ON u.ID = $_SESSION[uid]
				JOIN BabeskPriceClasses pc
					ON m.price_class = pc.pc_ID AND pc.GID = u.GID
				WHERE date BETWEEN '$startdate' AND '$enddate'
					ORDER BY date, price_class");

			if ($this->_isSolipriceEnabled && $hasSoli[0]['soli']=="1") {

				$soliPrice = TableMng::query('SELECT value FROM SystemGlobalSettings
				WHERE name LIKE "soli_price"');

				foreach ($meals as &$meal) {
					if ($this->userHasValidCoupon($meal['ID'])) {
						$meal['price'] = $this->soliPriceGet($meal['price_class']);
					}
				}
				unset($meal);
			}

		} catch (Exception $e) {
			throw new Exception('Konnte die Mahlzeiten nicht abrufen!', 0, $e);
		}

		return $this->mealsToBeDisplayedToObjects($meals);
	}

	protected function mealsToBeDisplayedToObjects($meals) {

		$mealObjs = array();

		foreach($meals as $meal) {
			$mealObjs[] = new MealToDisplay($meal['ID'], $meal['date'],
						$meal['name'], $meal['price'], $meal['priceclassId'],
						$meal['priceclassName'], $meal['description']);
		}

		return $mealObjs;
	}

	protected function meallistCreate($meals) {

		$this->mealweeksGenerate();
		foreach($meals as $meal) {
			foreach($this->_mealweeklist as &$mealweek) {
				$mealWeeknum = date('W', strtotime($meal->date));
				if($mealweek->mealweeknumberGet() == $mealWeeknum) {
					$mealweek->mealAdd($meal);
				}
			}
		}
	}

	protected function mealweeksGenerate() {

		$weeknumbers = array();

		$days = $this->daysBetweenMealDisplayStartdateAndEnddateGenerate();
		foreach($days as $day) {
			$yearOfDay = date('Y', strtotime($day));
			$weekOfDay = date('W', strtotime($day));
			if(!in_array($weekOfDay, $weeknumbers)) {
				$weeknumbers[$weekOfDay]['year'] = $yearOfDay;
				$weeknumbers[$weekOfDay]['week'] = $weekOfDay;
			}
		}

		foreach($weeknumbers as $week) {
			$date = date(
				'Y-m-d',
				strtotime("{$week['year']}W{$week['week']}"));
			$this->_mealweeklist[] = new Mealweek($date);
		}
	}

	protected function daysBetweenMealDisplayStartdateAndEnddateGenerate() {

		$days = array();

		$dateIter = strtotime($this->_displayMealsStartdate);
		$endToken = strtotime($this->_displayMealsEnddate);
		while($dateIter <= $endToken) {
			$days[] = date('Y-m-d', $dateIter);
			$dateIter = strtotime('+1 day', $dateIter);
		}

		return $days;
	}

	protected function infotextsFetch() {

		$data = TableMng::query('SELECT * FROM SystemGlobalSettings
			WHERE name = "menu_text1" OR name = "menu_text2"');

		if(count($data) == 2) {
			foreach($data as $infotext) {
				if($infotext['name'] == 'menu_text1') {
					$this->_infotext1 = $infotext['value'];
				}
				else {
					$this->_infotext2 = $infotext['value'];
				}
			}
		}
		else {
			throw new Exception('Could not fetch Infotexts');
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
			$this->_interface->dieError(
				_g('Could not check if the Soliprice is enabled!'));
		}
	}
	
	/**
	 * 
	 * @param unknown $pc_ID
	 * @throws Exception
	 * @return unknown
	 */
	
	protected function soliPriceGet($pc_ID) {
		$seperatePricesEnabled = TableMng::query('SELECT * FROM SystemGlobalSettings
			WHERE name = "seperateSoliPrices"');
		if($seperatePricesEnabled[0]['value'] == "1"){
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

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

	protected $_displayMealsStartdate;
	protected $_displayMealsEnddate;
	protected $_orderEnddate;
	protected $_ordercancelEnddate;

	protected $_infotext1;
	protected $_infotext2;

	protected $_mealweeklist;

	protected $_nowWithoutHoursMinsSecs;

	protected $_isSolipriceEnabled;
}

?>
