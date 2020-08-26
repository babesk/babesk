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

		$user = $this->_em->find('DM:SystemUsers', $id);
		if(!$user) {
			dieHttp('Konnte den Benutzer nicht finden', 400);
		}
		$activeGroups = $this->getSingleUserActiveGroups($user);
		$allGroups = $this->getSingleUserAllGroups();
		$bookAssignments = $this->getSingleUserBookAssignments($user);
		$schoolyears = $this->getSingleUserSchoolyears();
		$userdata = [
			'id' => $user->getId(),
			'forename' => $user->getForename(),
			'surname' => $user->getName(),
			'username' => $user->getUsername(),
			'email' => $user->getEmail(),
			'telephone' => $user->getTelephone(),
			'birthday' => $user->getBirthday(),
			'locked' => $user->getLocked(),
			'credit' => $user->getCredit(),
			'soli' => $user->getSoli(),
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
			$query = $this->_em->createQuery(
				'SELECT partial g.{id, name} FROM DM:SystemGroups g
			');
			$groups = $query->getResult(AbstractQuery::HYDRATE_ARRAY);

		} catch(\Exception $e) {
			$this->_logger->logO('Could not fetch all groups',
				['sev' => 'error', 'moreJson' => $e->getMessage()]);
			dieHttp('Konnte die Gruppen nicht abrufen', 500);
		}
		return $groups;
	}

	protected function getSingleUserActiveGroups($user) {

		try {
			$query = $this->_em->createQuery(
				'SELECT partial g.{id}
				FROM DM:SystemGroups g
				INNER JOIN g.users u WITH u = :user
			');
			$query->setParameter('user', $user);
			$res = $query->getResult(AbstractQuery::HYDRATE_ARRAY);
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
			$query = $this->_em->createQuery(
				'SELECT partial usb.{id}, partial b.{id, title},
					partial sy.{id, label}
				FROM DM:SchbasUserShouldLendBook usb
				INNER JOIN usb.book b
				INNER JOIN usb.schoolyear sy
				WHERE usb.user = :user
			');
			$query->setParameter('user', $user);
			$res = $query->getResult(AbstractQuery::HYDRATE_ARRAY);
			return $res;

		} catch(\Exception $e) {
			$this->_logger->logO('Could not fetch book-assignments for user',
				['sev' => 'error', 'moreJson' => $e->getMessage()]);
			dieHttp('Konnte Buchzuweisungen nicht abrufen', 500);
		}
	}

	protected function getSingleUserSchoolyears() {

		try {
			$query = $this->_em->createQuery(
				'SELECT s FROM DM:SystemSchoolyears s'
			);
			$res = $query->getResult(AbstractQuery::HYDRATE_ARRAY);
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