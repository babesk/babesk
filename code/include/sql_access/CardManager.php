<?php
/**
 * Provides functions for the RFID Cards
 */

require_once PATH_ACCESS . '/TableManager.php';

class CardManager extends TableManager {
	function __construct() {
		parent::__construct('BabeskCards');
	}
	/**
	 * Validates the card ID
	 * Enter description here ...
	 * @param numeric $card_ID The Cardnumber
	 * @return boolean
	 */
	function valid_card_ID($card_ID) {
		/**
		 * @todo rename this function. this valids the cardnumber not the cardid
		 */

		if(!preg_match('/\A[0-9a-zA-Z]{4,10}\z/',$card_ID)){
			return false;
		}
		return true;
	}

	/**
	 * This function adds an entry to the card-table
	 * Enter description here ...
	 * @param numeric string $cardnumber The number of the card
	 * @param numeric string $UID The User-id of the card
	 */
	function addCard($cardnumber, $UID) {
		parent::addEntry('cardnumber', $cardnumber, 'UID', $UID);
	}

	/**
	 * This function checks if the Card is already existing in the database (and if yes, it returns the card-ID
	 * Enter description here ...
	 * @param unknown_type $cardnumber
	 */
	function is_card_existing($cardnumber) {
		require PATH_ACCESS . '/databaseDistributor.php';
		$query = sql_prev_inj(sprintf('SELECT * FROM %s WHERE cardnumber="%s"',
									$this->tablename, $cardnumber));
		$result = $this->db->query($query);

		$card = $result->fetch_assoc();
		if(!$card) {
			return false;
		}
		return true;
	}

	function getUserID($cardnumber) {
		require PATH_ACCESS . '/databaseDistributor.php';
		$query = sql_prev_inj(sprintf('SELECT * FROM %s WHERE cardnumber="%s"',
		$this->tablename, $cardnumber));
		$result = $this->db->query($query);
		$card = $result->fetch_assoc();
		if(!$card) {
			throw new MySQLVoidDataException('MySQL returned no data!');
		}
		//$user = parent::getEntryData($card['UID'], 'UID');//test if user exists
		//return $user['UID'];
		return $card['UID'];
	}

	/**
	 * changes the cardnumber of the ID of the table-entry to change into the given cardnumber
	 * Enter description here ...
	 * @param numeric_string $ID The ID of the table-entry to change
	 * @param numeric_string $cardnumber the new cardnumber of the table-entry
	 */
	function changeCardnumber($ID, $cardnumber) {
		if($this->is_card_existing($cardnumber)) {
			throw new InvalidArgumentException('The Cardnumber is already existing!');
		}
		parent::alterEntry($ID, 'cardnumber', $cardnumber);
	}

	/**
	 * If CardID was changed, this function adds 1 to changed_cardID on the MySQL-Server
	 * Enter description here ...
	 *
	 * @param $ID The ID of the object in the cards-table, which cardnumber was changed
	 */
	function addCardIdChange($ID) {
		$card = parent::getEntryData($ID);

		if($card != NULL) {
			try {
				parent::alterEntry($ID, 'changed_cardID', $card['changed_cardID']+1);
			} catch (Exception $e) {
				throw new Exception('could not alter the card-entry:'.$e->getMessage());
			}
		} else {
			throw new Exception('could not get the card!');
		}
	}

	/**
	 * Returns the number of CardID-changes
	 * Enter description here ...
	 * @param numeric_string $ID
	 * @return numeric value of changed_cardID for the given object
	 * @throws Exception if something has gone wrong
	 */
	function getCardIDChanges($ID) {
		$card = parent::getEntryData($ID);
		if(isset($card ['changed_cardID'])) {
			return $card ['changed_cardID'];
		} else {
			throw new Exception('could not get the value changed_cardID');
		}
	}

	function getCardnumberByUserID($ID) {
		require PATH_ACCESS . '/databaseDistributor.php';
		$query = sql_prev_inj(sprintf('SELECT * FROM %s WHERE UID=%s',$this->tablename, $ID));
		$result = $this->db->query($query);
		$card = $result->fetch_assoc();
		if(!$card) {
			throw new MySQLVoidDataException('MySQL returned no data!');
		}
		if($result->fetch_assoc() && $result != NULL) {
			//MySQL found two entries with the same user. Bad!
			throw new UnexpectedValueException('The User has two or more cardnumbers! fix it first!');
		}
		return $card['cardnumber'];
	}

	/**
	 * This function returns the ID of the Card which has the given UserID
	 * Enter description here ...
	 * @param numeric_string $ID The ID of the User
	 * @throws MySQLVoidDataException If there is no card with this UserID
	 * @throws UnexpectedValueException If MySQL found a User with multiple Cardnumbers
	 * @return numeric_string the CardID
	 */
	function getCardIDByUserID($ID) {
		require PATH_ACCESS . '/databaseDistributor.php';
		$query = sql_prev_inj(sprintf('SELECT * FROM %s WHERE UID=%s',$this->tablename, $ID));
		$result = $this->db->query($query);
		$card = $result->fetch_assoc();
		if(!$card) {
			throw new MySQLVoidDataException('MySQL returned no data!');
		}
		if($result->fetch_assoc() && $result != NULL) {
			//MySQL found two entries with the same user. Bad!
			throw new UnexpectedValueException('The User has two or more cardnumbers! fix it first!');
		}
		return $card['ID'];
	}

	// function getIDByUserID($uid) {
	// 	require PATH_ACCESS . '/databaseDistributor.php';
	// 	$query = sql_prev_inj(sprintf('SELECT * FROM %s WHERE UID=%s',$this->tablename, $uid));
	// 	$result = $this->db->query($query);
	// 	$card = $result->fetch_assoc();
	// 	if(!$card) {
	// 		throw new MySQLVoidDataException('MySQL returned no data!');
	// 	}
	// 	if($result->fetch_assoc() && $result != NULL) {
	// 		//MySQL found two entries with the same user. Bad!
	// 		throw new UnexpectedValueException('The User has two or more cardnumbers! fix it first!');
	// 	}
	// 	return $card['ID'];
	// }
}
?>
