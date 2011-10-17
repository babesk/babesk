<?php
/**
  *@file menu_functions.php
  *some functions of mod_menu
  */
	
/**
  *gets the date of this week's day
  *@param day what day in this week is meant. Format is like 'w' in date(), so 0 like Sunday up to 6 for Saturday
  */
function get_weekday($day) {
	include_once 'menu_constants.php';
	if($day < 0 OR $day > 6){echo F_ARGUMENT_GET_WEEKDAY; return false;}
	$weekdaynow = date('w');
	$timestampnow = time();
	if($weekdaynow != 6)
		$weekday = $timestampnow - (($weekdaynow - $day - 1) * 60 * 60 * 24);
	else if($weekdaynow == 6)//weekend, so show next week
		$weekday = $timestampnow - (($weekdaynow - $day - 7 - 1) * 60 * 60 * 24);
	$dateday = date("Y-m-d",$weekday);
	return $dateday;
}

/**
  *reorganizes the meallist to: meallistweeksorted [Menu] [day]
  *@param meallist the meallist that should be reorganized
  *@return returns the reorganized mealnamelist
  */
function sort_meallist($meallist) {
	if(!$meallist)return false;
	$weekday_name = array(0 => 'monday','tuesday','wednesday','thursday','friday');
	$weekday_date = array();//at which day which date is, 0 is Monday, 1 Tuesday...
	for($i = 0; $i < 5; $i++) {
		$weekday_date [$i] = get_weekday($i);
	}
	$meallistweeksorted = array(array());
	$counter = 0;
	foreach($meallist as $meal) {
		for($i = 0; $i < 5; $i++) {
			if($meal["date"] == $weekday_date[$i]) {
				while(isset($meallistweeksorted[$counter][$weekday_name[$i]]))$counter++;
				$meallistweeksorted[$counter][$weekday_name[$i]] = $meal["name"];
			}
			$counter = 0;
		}
	}
	return $meallistweeksorted;
}

/**
  *Converts a Y-m-d date to a good readable date
  *@param date the date that should be converted
  *@return returns the converted date as a string
  */
function date_to_european_date($date) {
	if(!$date)return false;
	$date_parts = explode('-', $date);
	$fin_date = $date_parts[2] . '.' . $date_parts[1] . '.' . $date_parts[0];
	return $fin_date;
}
?>