<?php

class AdminGChangePasswordProcessing {

	////////////////////////////////////////////////////////////////////////////////
	//Attributes
	private $cardManager;
	private $userManager;
	private $cardInfoInterface;
	private $msg;

	////////////////////////////////////////////////////////////////////////////////
	//Constructor
	public function __construct ($cardInfoInterface) {

		require_once PATH_ACCESS . '/CardManager.php';
		require_once PATH_ACCESS . '/UserManager.php';

		require_once 'AdminGChangePasswordInterface.php';

		$this->cardManager = new CardManager();
		$this->userManager = new UserManager();
		$this->cardInfoInterface = $cardInfoInterface;

		$this->msg = array(
			'err_card_id'			 => 'Diese Karte ist nicht vergeben!',
			'err_get_user_by_card'	 => 'Anhand der Kartennummer konnte kein Benutzer gefunden werden.',
			'err_no_orders'			 => 'Es sind keine Bestellungen für diesen Benutzer vorhanden.',
			'err_meal_not_found'	 => 'Ein Menü konnte nicht gefunden werden!',
			'err_connection'		 => 'Ein Fehler ist beim Verbinden zum MySQL-Server aufgetreten',
			'msg_order_fetched'		 => 'Die Bestellung wurde schon abgeholt',);
	}

	////////////////////////////////////////////////////////////////////////////////
	//Methods
	/**
	 * Displays the names of all orders for today
	 * @param string $card_id The ID of the Card
	 */
	public function CheckCard ($card_id) {

		if (!$this->cardManager->valid_card_ID($card_id))
			$this->cardInfoInterface->dieError(sprintf($this->msg['err_card_id'], $card_id));

		$uid = $this->GetUser($card_id);
		return  $uid;
	}

	////////////////////////////////////////////////////////////////////////////////
	//Implementations
	/**
	 * Looks the user for the given CardID up, checks if the Card is locked and returns the UserID
	 * @param string $card_id The ID of the Card
	 * @return string UserID
	 */
	public function GetUser ($card_id) {
		try {
			return $this->cardManager->getUserID($card_id);
		} catch (Exception $e) {
			$this->cardInfoInterface->dieError(sprintf($this->msg['err_card_id'], $card_id));
		}

	}

	/**
	 * Returns some generic user data for identifying a card
	 */
	public function GetUserData($uid) {

		try {
			$data = TableMng::query(sprintf(
				'SELECT u.*,
				(SELECT CONCAT(g.gradelevel, g.label) AS class
					FROM SystemAttendances uigs
					LEFT JOIN SystemGrades g ON uigs.gradeId = g.ID
					WHERE uigs.userId = u.ID AND
						uigs.schoolyearId = @activeSchoolyear) AS class
				FROM SystemUsers u WHERE ID = %s', $uid), true);

		} catch (MySQLVoidDataException $e) {
			$this->cardInfoInterface->dieError('Der Benutzer wurde nicht gefunden');

		} catch (Exception $e) {
			$this->cardInfoInterface->dieError('Der Benutzer konnte nicht von der Datenbank abgerufen werden!');
		}

		return $data[0];
	}

	public function pwChange ($pwNew, $pwNewRep,$uid) {
		require_once PATH_ACCESS . '/UserManager.php';
		$userManager = new UserManager();
		try {
			inputcheck ($pwNew, 'password', 'Passwort');
		} catch (WrongInputException $e) {
			$this->cardInfoInterface->DieError ('Das Passwort enthält nicht korrekte Zeichen oder ist zu kurz.');
		}
		if ($pwNew != $pwNewRep) {
			$this->cardInfoInterface->DieError ('Das Passwort stimmt nicht mit der Wiederholung überein. Bitte versuche es noch einmal.');
		}

		try {
			$userManager->changePassword ($uid, $pwNew);
		} catch (Exception $e) {
			$this->cardInfoInterface->DieError ('Konnte das Passwort nicht verändern; Ein interner Fehler ist aufgetreten');
		}

		$this->cardInfoInterface->showMsg ('Das Passwort wurde erfolgreich verändert');
	}
}

?>
