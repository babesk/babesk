<?php

namespace administrator\Elawa\Meetings\ChangeDisableds;

require_once PATH_ADMIN . '/Elawa/Meetings/Meetings.php';
use Babesk\ORM\SystemRoom;
use Babesk\ORM\SystemUsers;
use Babesk\ORM\ElawaCategory;
use Babesk\ORM\ElawaMeeting;

class ChangeDisableds extends \administrator\Elawa\Meetings\Meetings {

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		parent::entryPoint($dataContainer);
		if(isset($_POST['room']) && isset($_POST['host']) && isset($_POST['category'])){
			$room = $this->_em->getRepository("DM:SystemRoom")->findOneByName($_POST['room']);
			$host = $this->_em->getRepository("DM:SystemUsers")->findOneById($_POST['host']);
			$cat = $this->_em->getRepository("DM:ElawaCategory")->findOneByName($_POST['category']);
			$times = $this->_em->getRepository("DM:ElawaMeeting")->findAll();
			foreach ($times as $time){
				if($time->getHost() == $host && $time->getCategory()==$cat)
					$time->setRoom($room);
				$this->_em->persist($time);
			}
			$this->_em->flush();
		}else if(isset($_POST['hostId'])) {
			$host = $this->_em->getReference(
				'DM:SystemUsers', $_POST['hostId']
			);
			$this->sendHostMeetingData($host);
		}
		else if(isset($_POST['meetingId'])) {
			$meeting = $this->_em->getReference(
				'DM:ElawaMeeting', $_POST['meetingId']
			);
			$isDisabled = $_POST['isDisabled'] == 'true';
			$meeting->setIsDisabled($isDisabled);
			$this->_em->persist($meeting);
			$this->_em->flush();
		}
		else {
			$hosts = $this->getHosts();
			$this->_smarty->assign('hosts', $hosts);
			$this->displayTpl('changeDisableds.tpl');
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function getHosts() {
		
		$query = $this->_em->createQuery(
			'SELECT u, m FROM DM:SystemUsers u
			INNER JOIN u.elawaMeetingsHosting m
		');
		$users = $query->getResult();
		if($users && count($users)) {
			return $users;
		}
		else {
			$this->_interface->dieError(
				'Es gibt keine Benutzer, die Sprechzeiten haben.'
			);
		}
	}
	
	protected function getRooms() {
	
		$query = $this->_em->createQuery(
				'SELECT u FROM DM:SystemRoom u
		');
		$rooms = $query->getResult();
		if($rooms && count($rooms)) {
			return $rooms;
		}
	}

	protected function sendHostMeetingData($host) {

		$query = $this->_em->createQuery(
			'SELECT m, c FROM DM:ElawaMeeting m
			INNER JOIN m.category c
			WHERE m.host = :host
			ORDER BY m.time ASC
		');
		$query->setParameter('host', $host);
		$meetings = $query->getResult();
		if($meetings && count($meetings)) {
			$meetingAr = array();
			foreach($meetings as $meeting) {
				$meetingAr[] = array(
					'id' => $meeting->getId(),
					'time' => $meeting->getTime()->format('H:i:s'),
					'length' => $meeting->getLength()->format('H:i:s'),
					'category' => $meeting->getCategory()->getName(),
					'isDisabled' => $meeting->getIsDisabled()
				);
			}
			$roomArr = array();
			$roomArr[] = array(
					'id' => 0,
					'name' => "---"
			);
			$rooms = $this->getRooms();
			if(isset($rooms)){
				foreach ($rooms as $room){
					$roomArr[] = array(
						'id' => $room->getId(),
						'name' => $room->getName()
					);
				}
			}else{
				$this->_interface->showWarning("Es sind keine Räume vorhanden");
			}
			$selectedArr = array();
			$categories = $this->_em->getRepository("DM:ElawaCategory")->findAll();
			foreach ($categories as $category){
				$selectedRoom = $this->_em->getRepository("DM:ElawaMeeting")->findOneBy(array('host' => $host,
																								'category' => $category
				));
				if(isset($selectedRoom)){
					if($selectedRoom->getRoom()->getId() != 0)
						$selectedArr[$category->getName()] = $selectedRoom->getRoom()->getName();
				}
			}
			$elawaEnabled = $this->_em->getRepository('DM:SystemGlobalSettings')->findOneByName('elawaSelectionsEnabled');
			$sendingArr = array($meetingAr, $roomArr, $selectedArr, $elawaEnabled->getValue() != "0");
			die(json_encode($sendingArr));
		}
		else {
			http_response_code(204);
			die();
		}
	}

}