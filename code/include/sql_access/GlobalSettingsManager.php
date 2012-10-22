<?php
require_once PATH_ACCESS . '/TableManager.php';

class GlobalSettingsManager extends TableManager {
	function __construct() {
		parent::__construct('global_settings');
	}
	
	/**
	 * returns the helptext found in the MySQL-table global_settings
	 * @throws MySQLVoidDataException
	 * @throws Other Exceptions (@see TableManager)
	 * @return string
	 */
	function getHelpText() {
		$entry_arr = $this->searchEntry('name="helptext"');
		if (!isset($entry_arr['value']) || !$entry_arr['value'])
			throw new MySQLVoidDataException('helptext is void!');
		return $entry_arr['value'];
	}
	
	/**
	 * Changes the HelpText
	 * @param string $str the text will be changed to this string
	 * @throws MySQLVoidDataException
	 * @throws some other things if somethings gone wrong
	 */
	function changeHelpText($str) {
		$entry_arr = $this->searchEntry('name="helptext"');
		if (!isset($entry_arr) || !count($entry_arr)) {
			throw new MySQLVoidDataException('searchEntry returned void helparray');
		}
		$help_id = $entry_arr['id'];
		$this->alterEntry($help_id, 'value', $str);
	}
	
	/**
	 * returns the religions found in the MySQL-table global_settings
	 * @throws MySQLVoidDataException
	 * @throws Other Exceptions (@see TableManager)
	 * @return string
	 */
	function getReligion() {
		$entry_arr = $this->searchEntry('name="religion"');
		if (!isset($entry_arr['value']) || !$entry_arr['value'])
			throw new MySQLVoidDataException('religion is void!');
		return $entry_arr['value'];
	}
	
	/**
	 * Sets the Religions
	 * @param string $str the text will be changed to this string
	 * @throws MySQLVoidDataException
	 * @throws some other things if somethings gone wrong
	 */
	function setReligion($str) {
		$entry_arr = $this->searchEntry('name="religion"');
		if (!isset($entry_arr) || !count($entry_arr)) {
			throw new MySQLVoidDataException('searchEntry returned void helparray');
		}
		$religion_id = $entry_arr['id'];
		$this->alterEntry($religion_id, 'value', $str);
	}
	
	/**
	 * returns the courses found in the MySQL-table global_settings
	 * @throws MySQLVoidDataException
	 * @throws Other Exceptions (@see TableManager)
	 * @return string
	 */
	function getCourse() {
		$entry_arr = $this->searchEntry('name="special_course"');
		if (!isset($entry_arr['value']) || !$entry_arr['value'])
			throw new MySQLVoidDataException('special_course is void!');
		return $entry_arr['value'];
	}
	
	/**
	 * Sets the Courses
	 * @param string $str the text will be changed to this string
	 * @throws MySQLVoidDataException
	 * @throws some other things if somethings gone wrong
	 */
	function setCourse($str) {
		$entry_arr = $this->searchEntry('name="special_course"');
		if (!isset($entry_arr) || !count($entry_arr)) {
			throw new MySQLVoidDataException('searchEntry returned void helparray');
		}
		$course_id = $entry_arr['id'];
		$this->alterEntry($course_id, 'value', $str);
	}
	
	
	/**
	 * returns the foreign languages found in the MySQL-table global_settings
	 * @throws MySQLVoidDataException
	 * @throws Other Exceptions (@see TableManager)
	 * @return string
	 */
	function getForeignLanguages() {
		$entry_arr = $this->searchEntry('name="foreign_language"');
		if (!isset($entry_arr['value']) || !$entry_arr['value'])
			throw new MySQLVoidDataException('foreign_lang is void!');
		return $entry_arr['value'];
	}
	
	/**
	 * Sets the foreign languages
	 * @param string $str the text will be changed to this string
	 * @throws MySQLVoidDataException
	 * @throws some other things if somethings gone wrong
	 */
	function setForeignLanguages($str) {
		$entry_arr = $this->searchEntry('name="foreign_language"');
		if (!isset($entry_arr) || !count($entry_arr)) {
			throw new MySQLVoidDataException('searchEntry returned void helparray');
		}
		$foreignLanguage_id = $entry_arr['id'];
		$this->alterEntry($foreignLanguage_id, 'value', $str);
	}

	/**
	 * returns the special courses found in the MySQL-table global_settings
	 * @throws MySQLVoidDataException
	 * @throws Other Exceptions (@see TableManager)
	 * @return string
	 */
	function getSpecialCourses() {
		$entry_arr = $this->searchEntry('name="special_course"');
		if (!isset($entry_arr['value']) || !$entry_arr['value'])
			throw new MySQLVoidDataException('special_course is void!');
		return $entry_arr['value'];
	}
	
	/**
	 * Sets the special courses
	 * @param string $str the text will be changed to this string
	 * @throws MySQLVoidDataException
	 * @throws some other things if somethings gone wrong
	 */
	function setSpecialCourses($str) {
		$entry_arr = $this->searchEntry('name="special_course"');
		if (!isset($entry_arr) || !count($entry_arr)) {
			throw new MySQLVoidDataException('searchEntry returned void helparray');
		}
		$specialCourse_id = $entry_arr['id'];
		$this->alterEntry($specialCourse_id, 'value', $str);
	}	
	
	/**
	 * Returns both of the infotexts of the table global_settings
	 * Enter description here ...
	 * @return array [0] = infotext1 [1] = infotext2
	 */
	function getInfoTexts() {
		$it_arr = array();
		$it1 = $this->searchEntry('name="menu_text1"');
		$it2 = $this->searchEntry('name="menu_text2"');
		
		$it_arr[0] = $it1['value'];
		$it_arr[1] = $it2['value'];
		
		if (!$it_arr[0] || !$it_arr[1]) {
			throw new MySQLVoidDataException('MySQL returned a void element!');
		}
		return $it_arr;
	}
	
	/**
	 * Changes infotext1 and infotext2 based on the arguments given
	 */
	function setInfoTexts($infotext1, $infotext2) {
		$this->alterEntry($this->getIDByValue('name', 'menu_text1'), 'value', $infotext1);
		$this->alterEntry($this->getIDByValue('name', 'menu_text2'), 'value', $infotext2);
	}
	
	/**
	 * changes the Last Order Time
	 * @param unknown_type $time
	 */
	function setLastOrderTime($time) {
		$this->alterEntry($this->getIDByValue('name', 'last_order_time'), 'value', $time);
	}
	
	/**
	 * returns the value of soli_price
	 * @throws UnexpectedValueException when soli_price is NULL
	 * @throws something else when MySQL has problems
	 * @return string the soli_price
	 */
	function getSoliPrice() {
		$pid = parent::searchEntry("name = 'soli_price'");
		$soli_price = parent::getEntryValue($pid['id'], 'value');
		if($soli_price === NULL)
			throw new UnexpectedValueException('soli_price has no value!');
		return $soli_price;
	}
	
	/**
	 * returns the key for Fits module
	 * @throws UnexpectedValueException when fits is NULL
	 * @throws something else when MySQL has problems
	 * @return string the fits_key
	 */
	function getFitsKey() {
		$pid = parent::searchEntry("name = 'fits_key'");
		$fits_key = parent::getEntryValue($pid['id'], 'value');
		if($fits_key === NULL)
			throw new UnexpectedValueException('fits_key has no value!');
		return $fits_key;
	}
	
	
	/**
	 * changes the Fits key
	 * @param unknown_type $key
	 */
	function setFitsKey($key) {
		$this->alterEntry($this->getIDByValue('name', 'fits_key'), 'value', $key);
	}
	
	
	/**
	 * returns the schoolyear for Fits module
	 * @throws UnexpectedValueException when schoolyear is NULL
	 * @throws something else when MySQL has problems
	 * @return string the schoolyear
	 */
	function getFitsYear() {
		$pid = parent::searchEntry("name = 'fits_year'");
		$fits_year = parent::getEntryValue($pid['id'], 'value');
		if($fits_year === NULL)
			throw new UnexpectedValueException('fits_year has no value!');
		return $fits_year;
	}

	
	
	/**
	 * changes the Fits year
	 * @param unknown_type $year
	 */
	function setFitsYear($year) {
		$this->alterEntry($this->getIDByValue('name', 'fits_year'), 'value', $year);
	}
	
	/**
	 * returns the class for Fits module
	 * @throws UnexpectedValueException when class is NULL
	 * @throws something else when MySQL has problems
	 * @return string the class
	 */
	function getFitsClass() {
		$pid = parent::searchEntry("name = 'fits_class'");
		$fits_class = parent::getEntryValue($pid['id'], 'value');
		if($fits_class === NULL)
			throw new UnexpectedValueException('fits_year has no value!');
		return $fits_class;
	}	
	
	
	/**
	 * changes the Fits class
	 * @param unknown_type $class
	 */
	function setFitsClass($class) {
		$this->alterEntry($this->getIDByValue('name', 'fits_class'), 'value', $class);
	}	
	
	/**
	 * returns the search method for Fits module
	 * @throws UnexpectedValueException when search method is NULL
	 * @throws something else when MySQL has problems
	 * @return boolean the search method
	 */
	function getFitsAllClasses() {
		$pid = parent::searchEntry("name = 'fits_all_classes'");
		$fits_class = parent::getEntryValue($pid['id'], 'value');
		if($fits_class === NULL)
			throw new UnexpectedValueException('fits_all_classes has no value!');
		return $fits_class;
	}
	
	
	/**
	 * changes the Fits search methof
	 * @param unknown_type $flag
	 */
	function setFitsAllClasses($flag) {
		$this->alterEntry($this->getIDByValue('name', 'fits_all_classes'), 'value', $flag);
	}	
	
	
	/**
	 * Changes the value of "soli_price" to the given value
	 * @throws something if something has gone wrong
	 */
	function changeSoliPrice($value) {
		$pid = parent::searchEntry("name = 'soli_price'");
		parent::alterEntry($pid['id'], 'value', $value);
	}
	
	/**
	* returns the value of last_order_time
	* @throws UnexpectedValueException when last_order_time is NULL
	* @throws something else when MySQL has problems
	* @return string the last_order_time
	*/
	function getLastOrderTime() {
		$pid = parent::searchEntry("name = 'last_order_time'");
		$lastOrderTime = parent::getEntryValue($pid['id'], 'value');
		if($lastOrderTime === NULL)
		throw new UnexpectedValueException('last_order_time has no value!');
		return $lastOrderTime;
	}
}
?>