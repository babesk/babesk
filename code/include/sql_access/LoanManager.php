<?php
/**
 * Provides a class to manage the booklist of the system
 */

require_once PATH_ACCESS . '/TableManager.php';

/**
 * Manages the lending list, provides methods to add/modify the lending list or to get information from the lending list.
 */
class LoanManager extends TableManager{

	public function __construct() {
		parent::__construct('schbas_lending');
	}
	
	
	/* Sorts the lending list for a UserID it gets from MySQL-table and returns them
	 * Used by mod_retour !!
	*/
	function getLoanlistByUID($uid) {

		require_once PATH_ACCESS . '/dbconnect.php';
		$res_array = array();
		$query = sql_prev_inj(sprintf('SELECT * FROM %s WHERE user_id = "%s"', $this->tablename, $uid));
		$result = $this->db->query($query);
		if (!$result) {		
			throw DB_QUERY_ERROR.$this->db->error;
		}
		while($buffer = $result->fetch_assoc())
			$res_array[] = $buffer;
		return $res_array;
			
			
	}
		
	/**
	 * Sorts a list of books, which should lend for a User.
	 * Used by mod_loan!!
	 */
	function getLoanByUID($uid, $ajax) {
		require_once PATH_ACCESS . '/dbconnect.php';
		require_once PATH_ACCESS . '/UserManager.php';
		require_once PATH_ACCESS . '/BookManager.php';
		require_once PATH_ACCESS . '/InventoryManager.php';
		$userManager = new UserManager;
		$bookManager = new BookManager;
		$inventoryManager = new InventoryManager;
		$details = $userManager->getUserDetails($uid);
		$lang = array('LA'=>'LA','FR'=>'FR','RU'=>'RU');
		$reli = array('EV'=>'EV','WUN'=>'WUN');
		unset($lang[$details['foreign_language']]);
		unset($reli[$details['religion']]);
		$books = $bookManager->getBooksByClass($details['class']);
		$counter = 0;
		if ($books){
			foreach ($books as &$book){
				if ((in_array($book['subject'], $lang) OR (in_array($book['subject'], $reli)))){
					unset($books[$counter]);
				}
				$counter++;
			}
		}
		$query = sql_prev_inj(sprintf('SELECT inventory_id FROM %s WHERE user_id=%s', $this->tablename, $uid));
		$result = $this->db->query($query);
		if (!$result) {
			throw DB_QUERY_ERROR.$this->db->error;
		}
		while($buffer = $result->fetch_assoc())
			$minusbooksinvarr[] = $buffer;
		if (isset($minusbooksinvarr)) {
			foreach ($minusbooksinvarr as &$minusbooksinva){
				$minusbooksinv[] = $minusbooksinva['inventory_id'];
			}
			
			foreach ($minusbooksinv as &$minusbookinv){
				$minusbooksarr[] = $inventoryManager->getBookIDByInvID($minusbookinv);
			}
			
			foreach ($minusbooksarr as &$minusbooksa){
				$minusbook[] = $minusbooksa['book_id'];
			}
			
			$counter = 0;
			if ($books) {
				foreach ($books as &$book){
					$match = array_search($book['id'], $minusbook);
					if (!is_bool($match)) {
						unset($books[$counter]);
					}
					$counter++;
				}
			}
		}
		return $books;
	}
	
	/**
	 * Remove an entry in the loan list by a given user id and inventory id
	 */
	function RemoveLoanByIDs($inventoryID, $uid) {
		require_once PATH_ACCESS . '/dbconnect.php';	
		$query = sql_prev_inj(sprintf('user_id = %s AND inventory_id = %s' , $uid, $inventoryID));
		$result = parent::delEntryNoID($query);
	}
	
	/**
	 * Search, whether an user_id exists.
	 */
	function isUserEntry($uid) {
		$match = parent::existsEntry('user_id', $uid);
		return $match;
		}
	
	/**
	 * Add an entry in the loan list by a given user id and inventory id
	 */
	function AddLoanByIDs($inventoryID, $uid) {
		require_once PATH_ACCESS . '/dbconnect.php';
		$result = parent::addEntry('user_id', $uid, 'inventory_id', $inventoryID);
		return $result;
	}
	
	/**
	 * Search, whether an inventory_id is scanned yet.
	 */
	function isEntry($inventory_id) {
		$match = parent::existsEntry('inventory_id', $inventory_id);
		return $match;
		}
}
?>