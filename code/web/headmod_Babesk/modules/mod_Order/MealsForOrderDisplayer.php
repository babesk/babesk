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

	public function display($smarty, $smartyPath) {

		$this->settingsFetch();
		$this->infotextsFetch();
		$meals = $this->mealsToBeDisplayedGet();
		$this->meallistCreate($meals);
		$smarty->assign("displayMealsStartdate",
						$this->_displayMealsStartdate);
		$smarty->assign("displayMealsEnddate", $this->_displayMealsEnddate);
		$smarty->assign("orderEnddate", $this->_orderEnddate);
		$smarty->assign("ordercancelEnddate", $this->_ordercancelEnddate);
		$smarty->assign("mealweeklist", $this->_mealweeklist);
		$smarty->assign("infotext1", $this->_infotext1);
		$smarty->assign("infotext2", $this->_infotext2);

		$smarty->display($smartyPath . 'order.tpl');
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function settingsFetch() {

		$data = TableMng::query('SELECT * FROM global_settings
			WHERE name = "displayMealsStartdate" OR
				name = "displayMealsEnddate" OR
				name = "orderEnddate" OR
				name = "ordercancelEnddate";', true);

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

	protected function mealsToBeDisplayedGet() {

		$startdate = $this->_displayMealsStartdate;
		$enddate = $this->_displayMealsEnddate;

		try {
			$meals = TableMng::query("SELECT m.*, pc.price AS price,
					pc.pc_ID AS priceclassId, pc.name AS priceclassName
				FROM meals m
				JOIN users u ON u.ID = $_SESSION[uid]
				JOIN price_classes pc
					ON m.price_class = pc.pc_ID AND pc.GID = u.GID
				WHERE date BETWEEN '$startdate' AND '$enddate'
					ORDER BY date, price_class", true);

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

		$data = TableMng::query('SELECT * FROM global_settings
			WHERE name = "menu_text1" OR name = "menu_text2"', true);

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
}

?>
