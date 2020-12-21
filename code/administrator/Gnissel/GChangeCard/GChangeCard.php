<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_ADMIN . '/Gnissel/Gnissel.php';

class GChangeCard extends Gnissel {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct($name, $display_name, $path) {
		parent::__construct($name, $display_name, $path);
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);

		if (isset($_GET['action'])) {
			switch ($_GET['action']) {
				case 'changeCard':
					$this->changeCard();
					break;
				default:
					$this->_interface->dieError('Unknown action value');
					break;
			}
		}
		else {
			if('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['username'])) {
				$this->changeCardShow();
			}
			else{
				$this->displayTpl('getUsername.tpl');
			}
		}

	}

	protected function entryPoint($dataContainer) {

		parent::entryPoint($dataContainer);
		$this->initSmartyVariables();
	}

	protected function changeCardShow() {

		$stmt = $this->_pdo->prepare("SELECT ID, name, forename FROM SystemUsers WHERE username = ?");
		$stmt->execute(array($_POST['username']));
		$user = $stmt->fetch();

		$stmt = $this->_pdo->prepare("SELECT g.gradelevel, g.label FROM SystemGrades g
												JOIN SystemAttendances a ON (a.gradeId = g.ID)
												JOIN SystemSchoolyears s ON (s.ID = a.schoolyearId)
												WHERE s.active = 1 AND a.userId=?");
		$stmt->execute(array($user['ID']));
		$grade = $stmt->fetch();
		if($user) {
			$this->_smarty->assign('uid', $user['ID']);
			$this->_smarty->assign('name', $user['name']);
			$this->_smarty->assign('forename', $user['forename']);
		}
		else {
			$this->_interface->dieError(
				"Der Benutzer '$_POST[username]' wurde nicht gefunden!"
			);
		}
		if($grade) {
			$this->_smarty->assign('class', $grade['gradelevel'] . $grade['label']);
		}
		else {
			$this->_smarty->assign('class', '---');
		}
		$this->displayTpl('changeCard.tpl');
	}

	/**
	 * Changes the users cardnumber based on the input
	 */
	protected function changeCard() {
		$stmt = $this->_pdo->prepare("SELECT cardnumber FROM BabeskCards WHERE UID = ?");
		$stmt->execute(array($_POST['uid']));
		$oldCard = $stmt->fetchAll();

		if(count($oldCard) == 1) {
			$this->_interface->backlink('administrator|Gnissel|GChangeCard');
			$oldCardnumber = $oldCard[0]['cardnumber'];
			$newCardnumber = $_POST['newCard'];
			$this->changeCardCheckInput($oldCardnumber, $newCardnumber);

			$stmt = $this->_pdo->prepare("UPDATE BabeskCards SET cardnumber=? WHERE UID=?");
			$stmt->execute(array($newCardnumber, $_POST['uid']));

			$this->_interface->dieSuccess(
				"Die Kartennummer wurde erfolgreich von '$oldCardnumber' auf" .
				" '$newCardnumber' geändert."
			);
		}
		else if(count($oldCard) == 0) {
			$this->_interface->dieError(
				'Der Benutzer hat noch keine Karte, die verändert werden kann.'
			);
		}
		else {
			$this->_logger->log('Error changing cardnumber. User has ' .
				'multiple cards', 'Notice', NULL,
				json_encode(array('uid' => $_POST['uid'])));
			$this->_interface->dieError('Der Benutzer hat mehrere Karten! ' .
				'Kann die Karte nicht wechseln.');
		}
	}

	protected function changeCardCheckInput($oldCardnumber, $newCardnumber) {

		try {
			inputcheck($newCardnumber, 'card_id', 'Kartennummer');
		} catch (WrongInputException $e) {
			$this->_interface->dieError(
				"Die Kartennummer '$oldCardnumber' wurde nicht korrekt " .
				'eingegeben.'
			);
		}
		if($oldCardnumber == $newCardnumber) {
			$this->_interface->dieMsg(
				"Die neue Kartennummer '$newCardnumber' ist gleich der " .
				'alten. Es wurde nichts verändert.'
			);
		}
		$stmt = $this->_pdo->prepare("SELECT * FROM BabeskCards WHERE cardnumber = ?");
		$stmt->execute(array($newCardnumber));
		$newCardExists = $stmt->fetch();
		if($newCardExists) {
			$this->_interface->dieError(
				"Die Kartennummer '$newCardnumber' ist bereits vergeben."
			);
		}
	}
}

?>
