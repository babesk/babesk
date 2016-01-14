<?php
/**
 * Provides Functions to manage the price classes of the system
 */

require_once PATH_ACCESS . '/TableManager.php';

/**
 * Manages the price classes, provides methods to add/modify price classes or to get price class data
 */
class PriceClassManager extends TableManager {

	function __construct () {
		TableManager::__construct('BabeskPriceClasses');
	}

	/**
	 *Returns the requested pricefield on the based on the User-ID and the Meal-ID
	 *
	 *@return: string
	 *returns false if nothing found, else the priceData
	 */
	function getPrice ($uid, $mid) {
		require_once PATH_ACCESS . '/UserManager.php';
		require_once PATH_ACCESS . '/MealManager.php';

		$userManager = new UserManager();
		$mealManager = new MealManager('BabeskMeals');

		$gid = $userManager->getEntryData($uid, 'GID');
		$gid = $gid['GID'];
		//this is pc_ID in the table, not ID!
		$priceclass_ID = $mealManager->getEntryData($mid, 'price_class');
		$priceclass_ID = $priceclass_ID['price_class'];
		if (!$priceclass_ID)
			throw new Exception('');
		$priceData = $this->searchEntry('GID = ' . $gid . ' AND pc_ID = ' . $priceclass_ID);
		if (!isset($priceData['price']))
			throw new Exception('invalid Data gained from MySQL-Server:' . var_dump($priceData));
		return $priceData['price'];
	}

	function changePriceClass ($old_ID, $name, $GID, $price, $ID) {
		TableManager::alterEntry($old_ID, 'name', $name, 'GID', $GID, 'price', $price, 'ID', $ID);
	}

	/**
	 * Combines all priceclasses with the same pc_ID and returns these with their names as an array
	 * @return array(pc_ID, name)
	 */
	function getAllPriceClassesPooled () {
		$pc_pooled_arr = array();
		$priceclasses = $this->getTableData();
		foreach ($priceclasses as $priceclass) {
			foreach ($pc_pooled_arr as $pc_pooled) {
				if (isset($pc_pooled['pc_ID']) && $priceclass['pc_ID'] == $pc_pooled['pc_ID'])
					continue 2;
			}
			//priceclass not already added, add entry
			$pc_pooled_arr[] = array(
				'pc_ID'	 => $priceclass['pc_ID'],
				'name'	 => $priceclass['name']);
		}
		return $pc_pooled_arr;
	}

	/**
	 * Adds a Price Class to the System
	 *
	 * The Function creates a new entry in the price_class Table
	 * consisting of the given Data
	 *
	 * If 4 Params ar given, the ID will not be automatically incremented, but
	 * will be the 4th param given
	 *
	 * @param name The name of the priceclass
	 * @param GID The group-ID of the priceclass
	 * @param price The price
	 * @param ID The ID of the price class, this one is optional (else MySQL will autoincrement)
	 */
	function addPriceClass ($name, $GID, $price, $pc_ID, $ID = '') {
		try {
			if (!$ID) { //nothing for ID given
				TableManager::addEntry('name', $name, 'GID', $GID, 'price', $price, 'pc_ID', $pc_ID);
			}
			else {
				TableManager::addEntry('name', $name, 'GID', $GID, 'price', $price, 'pc_ID', $pc_ID, 'ID', $ID);
			}
		} catch (Exception $e) {
			echo ERR_ADD_PRICECLASS;
			throw $e;
		}
	}

	function getPriceClassName ($pc_ID) {
		try {
			$pcn = TableManager::getTableData(sprintf("pc_ID=%s LIMIT 1", $pc_ID));
		} catch (Exception $e) {
			echo "Error getting priceclass: " . $e->getMessage();
			die();
		}
		return $pcn;
	}

	public function getHighestPriceclassID () {

		$highest_pc_ID = 0;

		try {
			$priceclasses = $this->getTableData();
		} catch (MySQLVoidDataException $e) {
			return $highest_pc_ID;
		}

		foreach ($priceclasses as $priceclass) {
			if ($priceclass['pc_ID'] > $highest_pc_ID) {
				$highest_pc_ID = $priceclass['pc_ID'];
			}
		}

		return $highest_pc_ID;
	}
	
	public function getColor($pc_ID) {
		try {
			$color = TableManager::getTableData(sprintf("pc_ID=%s LIMIT 1", $pc_ID));
		} catch (Exception $e) {
			echo "Error getting priceclass: " . $e->getMessage();
			die();
		}
		return $color[0]['color'];
	}
	
	public function setColor($pc_ID, $color) {
		try {
			$ids = TableManager::getTableData(sprintf("pc_ID=%s", $pc_ID));
			foreach ($ids as &$id){
				TableManager::alterEntry($id['ID'], 'color', $color);
			}
		} catch (Exception $e) {
			echo "Error getting priceclass: " . $e->getMessage();
			die();
		}
	}
}
?>