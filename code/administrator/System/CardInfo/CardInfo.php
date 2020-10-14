<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_ADMIN . '/System/System.php';

class CardInfo extends System {

	////////////////////////////////////////////////////////////////////////
	//Attributes
	////////////////////////////////////////////////////////////////////////

	////////////////////////////////////////////////////////////////////////
	//Constructor
	////////////////////////////////////////////////////////////////////////
	public function __construct($name, $display_name, $path) {
		parent::__construct($name, $display_name, $path);
	}

	////////////////////////////////////////////////////////////////////////
	//Methods
	////////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {
		//no direct access
		defined('_AEXEC') or die("Access denied");
		parent::entryPoint($dataContainer);
		parent::initSmartyVariables();

		require_once 'AdminCardInfoProcessing.php';
		require_once 'AdminCardInfoInterface.php';

		$cardInfoInterface = new AdminCardInfoInterface($this->relPath);
		$cardInfoProcessing = new AdminCardInfoProcessing($cardInfoInterface);

		if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['card_ID'])) {
			$this->cardinfoDisplay($_POST['card_ID']);
			$uid = $cardInfoProcessing->CheckCard($_POST['card_ID']);
			$userData = $this->getUserData($uid);
			$this->_smarty->assign('cardID', $_POST['card_ID']);
			$this->_smarty->assign('name', $userData['name']);
			$this->_smarty->assign('forename', $userData['forename']);
			$this->_smarty->assign('class', $userData['class']);
			$this->_smarty->assign('locked', $userData['locked']);
			$this->displayTpl('result.tpl');
		}
		else{
			$this->displayTpl('form.tpl');
		}
	}

	////////////////////////////////////////////////////////////////////////
	//Implements
	////////////////////////////////////////////////////////////////////////

	protected function getUserData($userId) {

		try {
			$stmt = $this->_pdo->prepare(
				'SELECT u.*, CONCAT(g.gradelevel, g.label) AS class
					FROM SystemUsers u
					LEFT JOIN SystemAttendances uigs
						ON uigs.userId = u.ID
						AND uigs.schoolyearId = @activeSchoolyear
					LEFT JOIN SystemGrades g ON g.ID = uigs.gradeId
					WHERE u.ID = :userId
			');
			$stmt->execute(array('userId' => $userId));
			return $stmt->fetch();

		} catch (PDOException $e) {
			$this->_logger->log('Error fetching the user',
				'Notice', Null, json_encode(array('uid' => $userId)));
			$this->_interface->dieError(
				'Ein Fehler ist beim Abrufen des Benutzers aufgetreten!'
			);
		}
	}

	private function cardinfoDisplay($cardnumber) {

		$stmt = $this->_pdo->prepare("SELECT * FROM BabeskCards c
												JOIN useractiveclass u ON (u.ID = c.UID)
												WHERE cardnumber = ?");
		$stmt->execute(array($cardnumber));
		$user = $stmt->fetch();
		if($user) {
            $this->_smarty->assign('user', $user);
            $this->displayTpl('result.tpl');
		}
		else {
			$this->_interface->dieError('Karte ist nicht vergeben.');
		}
		die();
	}
}

?>
