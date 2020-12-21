<?php

namespace administrator\System\Users;
use Doctrine\ORM\AbstractQuery;

require_once PATH_ADMIN . '/System/System.php';

class Users extends \System {

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);
		$this->moduleTemplatePathSet();
		// We cant use PATCH in PHP, so use POST with an additional parameter
		if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['patch'])) {
			$this->updateSingleUser();
		}
		else if($_SERVER['REQUEST_METHOD'] == 'GET') {
			$id = filter_input(INPUT_GET, 'id');
			$ajax = isset($_GET['ajax']);
			if($id && $ajax) {
				$this->sendSingleUser($id);
			}
			else if($id) {
				$this->displaySingleUser($id);
			}
		}
		else {
			die('System/Users');
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function displaySingleUser($id) {

		$this->_smarty->assign('userId', $id);
		$this->displayTpl('displaySingle.tpl');
	}

	protected function sendSingleUser($id) {

		$stmt = $this->_pdo->prepare('SELECT * FROM SystemUsers WHERE ID = ?');
		$stmt->execute(array($id));
		$user = $stmt->fetch();

		if(!$user) {
			dieHttp('Konnte den Benutzer nicht finden', 400);
		}
		$activeGroups = $this->getSingleUserActiveGroups($user);
		$allGroups = $this->getSingleUserAllGroups();
		$bookAssignments = $this->getSingleUserBookAssignments($user);
		$schoolyears = $this->getSingleUserSchoolyears();
		$userdata = [
			'id' => $user['ID'],
			'forename' => $user['forename'],
			'surname' => $user['name'],
			'username' => $user['username'],
			'email' => $user['email'],
			'telephone' => $user['telephone'],
			'birthday' => $user['birthday'],
			'locked' => $user['locked'] == 1,
			'credit' => $user['credit'],
			'soli' => $user['soli'],
			'activeGroups' => $activeGroups,
			'bookAssignments' => $bookAssignments
		];
		dieJson([
			'user' => $userdata,
			'groups' => $allGroups,
			'schoolyears' => $schoolyears
		]);
	}

	protected function getSingleUserAllGroups() {

		try {
		    $stmt = $this->_pdo->prepare('SELECT id, name FROM SystemGroups g');
		    $stmt->execute();
		    $groups = $stmt->fetchAll();

		} catch(\Exception $e) {
			$this->_logger->logO('Could not fetch all groups',
				['sev' => 'error', 'moreJson' => $e->getMessage()]);
			dieHttp('Konnte die Gruppen nicht abrufen', 500);
		}
		return $groups;
	}

	protected function getSingleUserActiveGroups($user) {

		try {
		    $stmt = $this->_pdo->prepare('SELECT g.id
				FROM SystemGroups g
				INNER JOIN SystemUsers u WHERE u.ID = ?
			');
		    $stmt->execute(array($user['ID']));
		    $res = $stmt->fetchAll();

			//$res = $query->getResult(AbstractQuery::HYDRATE_ARRAY);
			$groups = array_map(function($group) {
				return $group['id'];
			}, $res);

		} catch(\Exception $e) {
			$this->_logger->logO('Could not fetch the groups for a single ' .
				'user', ['sev' => 'error', 'moreJson' => $e->getMessage()]);
			dieHttp('Konnte die Gruppen nicht abrufen', 500);
		}
		return $groups;
	}

	protected function getSingleUserBookAssignments($user) {

		try {
			$stmt = $this->_pdo->prepare(
				'SELECT usb.id as usbid, b.id as bid, b.title, sy.id as syid, sy.label
				FROM SchbasUsersShouldLendBooks usb
				INNER JOIN SchbasBooks b ON (b.id = usb.bookId)
				INNER JOIN SystemSchoolyears sy ON (usb.schoolyearId = sy.ID)
				WHERE usb.userId = ?
			');
			$stmt->execute(array($user['ID']));
			$res = $stmt->fetchAll();
			return $res;

		} catch(\Exception $e) {
			$this->_logger->logO('Could not fetch book-assignments for user',
				['sev' => 'error', 'moreJson' => $e->getMessage()]);
			dieHttp('Konnte Buchzuweisungen nicht abrufen', 500);
		}
	}

	protected function getSingleUserSchoolyears() {

		try {
		    $stmt = $this->_pdo->prepare('SELECT s.ID as id, s.label FROM SystemSchoolyears s');
			$stmt->execute();
			$res = $stmt->fetchAll();
			return $res;

		} catch(\Exception $e) {
			$this->_logger->logO('Could not fetch the schoolyears',
				['sev' => 'error', 'moreJson' => $e->getMessage()]);
			dieHttp('Konnte Schuljahre nicht abrufen', 500);
		}
	}

	protected function updateSingleUser() {

		$userId = filter_input(INPUT_POST, 'userId');
		if($userId) {
			$user = $this->_em->getReference('DM:SystemUsers', $userId);
			require_once 'PatchUser.php';
			$patcher = new PatchUser($this->_dataContainer);
			$patcher->patch($user, $_POST);
		}
		else {
			dieHttp('Keine Benutzer-ID übergeben.', 400);
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////
}
?>