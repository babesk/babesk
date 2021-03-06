<?php
require_once PATH_ACCESS . '/TableManager.php';

class PVauManager extends TableManager {
	function __construct() {
		parent::__construct('pvau');
	}
	
	/**
	 * prepares the user in the pvau table
	 * @throws MySQLVoidDataException
	 * @throws Other Exceptions (@see TableManager)
	 * @return boolean
	 */
	function prepUser($uid) {
		if ($this->existsEntry("ID", $uid)) {
			
			return true;
		} else {
			$this->addEntry("ID", $uid,"searchterms","");
			return false;
			
		}
	}
	
	/**
	 * returns pvau searchterms f�r user ID
	 * @throws MySQLVoidDataException
	 * @throws Other Exceptions (@see TableManager)
	 * @return string
	 */
	function  getSearchterms($uid) {
		if ($this->prepUser($uid)) {
			if ($this->getEntryValue($uid, 'searchterms')!="") return $this->getEntryValue($uid, 'searchterms');
		} else {
			return "";
		}
	}

	
	/**
	 * sets pvausearchterms for user ID
	 *
	 *@throws MySQLConnectionException if a problem with MySQL happened
	 */
	function SetSearchterms($uid,$searchterms) {
		$searchterms = str_replace('"', '\'', $searchterms);

		if(isset($uid)) {
			if ($this->existsEntry("ID", $uid)) {
				parent::alterEntry($uid, 'searchterms', sql_prev_inj($searchterms));
			} else {
				$this->addEntry("ID", $uid,"searchterms",sql_prev_inj($searchterms));
				return false;
					
			}
			
			
		}
	}
}
?>