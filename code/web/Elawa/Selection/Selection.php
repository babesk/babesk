<?php

namespace web\Elawa\Selection;

require_once PATH_WEB . '/Elawa/Elawa.php';

class Selection extends \web\Elawa\Elawa {

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		parent::entryPoint($dataContainer);
		if($this->checkIsSelectionGloballyEnabled()) {
			if(isset($_POST['meetingId'])) {
				$this->registerSelection($_POST['meetingId']);
			}
			else if(isset($_GET['hostId'])) {
				$host = $this->_em->getReference(
					'DM:SystemUsers', $_GET['hostId']
				);
				$this->displaySelection($host);
			}
			else {
				$this->displayHostSelection();
			}
		}
		else {
			$this->_interface->dieError(
				'Die Wahlen finden momentan nicht statt.'
			);
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function displaySelection($host) {

		$query = $this->_em->createQuery(
			'SELECT m, r, c FROM DM:ElawaMeeting m
			LEFT JOIN m.category c
			LEFT JOIN m.room r
			WHERE m.host = :host
			ORDER BY m.time ASC
		');
		$query->setParameter('host', $host);
		$meetings = $query->getResult();
		$this->_smarty->assign('meetings', $meetings);
		$this->_smarty->assign('host', $host);
		$this->displayTpl('selection.tpl');
	}

	protected function registerSelection($meetingId) {

		$meeting = $this->_em->getReference('DM:ElawaMeeting', $meetingId);
		$query = $this->_em->createQuery(
			'SELECT m, h FROM DM:ElawaMeeting m
			LEFT JOIN m.host h
			WHERE m = :meeting
		');
		$query->setParameter('meeting', $meeting);
		$meeting = $query->getOneOrNullResult();
		$this->_interface->moduleBacklink('web|Elawa');
		if(!$meeting) {
			$this->_interface->dieError('Diese Sprechzeit existiert nicht!');
		}
		$user = $this->_em->getReference('DM:SystemUsers', $_SESSION['uid']);
		$this->checkRegisterSelectionValid($meeting, $user);

		$meeting->setVisitor($user);
		$this->_em->persist($meeting);
		$this->_em->flush();
		$this->_interface->dieSuccess(
			'Die Sprechzeit wurde erfolgreich angemeldet'
		);
	}

	protected function checkRegisterSelectionValid($meeting, $user) {

		$countQuery = $this->_em->createQuery(
			'SELECT COUNT(m.id) FROM DM:ElawaMeeting m
			INNER JOIN m.visitor v
			INNER JOIN m.host h
			WHERE v = :visitor AND h = :host
		');
		$countQuery->setParameter('visitor', $user);
		$countQuery->setParameter('host', $meeting->getHost());
		$count = $countQuery->getSingleScalarResult();
		if($count) {
			$this->_interface->dieError(
				'Sie sind bereits bei dieser Person angemeldet!'
			);
		}
		if($meeting->getIsDisabled()) {
			$this->_interface->dieError('Diese Sprechzeit ist deaktiviert!');
		}
		if($meeting->getVisitor()->getId() != 0) {
			$this->_interface->dieError(
				'Diese Sprechzeit ist leider schon vergeben. ' .
				'Da war wohl jemand schneller.'
			);
		}
	}

	/**
	 * Displays a list of hosts to choose from
	 */
	protected function displayHostSelection() {

		$userSelf = $this->_em->getReference(
			'DM:SystemUsers', $_SESSION['uid']
		);
		$hostsQuery = $this->_em->createQuery(
			'SELECT u FROM DM:SystemUsers u
			INNER JOIN u.elawaMeetingsHosting h
			ORDER BY u.name
		');
		$hosts = $hostsQuery->getResult();
		//Get all hosts for which the user already has made a selection
		$votedHostsQuery = $this->_em->createQuery(
			'SELECT m FROM DM:ElawaMeeting m
			WHERE m.visitor = :user
			GROUP BY m.host
		');
		$votedHostsQuery->setParameter('user', $userSelf);
		$meetingsOfVotedHosts = $votedHostsQuery->getResult();
		$hostsAr = array();
		foreach($hosts as $host) {
			$status = "";
			$selectable = true;
			if($host == $userSelf) {
				$status = "Du selbst";
				$selectable = false;
			}
			foreach($meetingsOfVotedHosts as $meetingOfVotedHost) {
				if($host == $meetingOfVotedHost->getHost()) {
					$status = "Bereits Termin gewählt";
					$selectable = false;
				}
				continue;
			}
			$hostsAr[] = array(
				'statusText' => $status,
				'selectable' => $selectable,
				'host' => $host
			);
		}
		$this->_smarty->assign('hosts', $hostsAr);
		$this->displayTpl('host_selection.tpl');
	}

	/**
	 * Checks if the selections are globally enabled
	 * @return boolean Returns true if selections are enabled, else false
	 */
	protected function checkIsSelectionGloballyEnabled() {

		$enabledRow = $this->_em->getRepository('DM:SystemGlobalSettings')
			->findOneByName('elawaSelectionsEnabled');
		if($enabledRow) {
			if($enabledRow->getValue() != '0') {
				return true;
			}
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////
}

?>