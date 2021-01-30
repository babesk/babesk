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
		    if($_POST['room'] == "---"){
                $query = $this->_pdo->prepare("UPDATE ElawaMeetings SET roomId = 0
                                                        WHERE hostId = ? AND categoryId = ?");
                $query->execute(array($_POST['host'], $_POST['category']));

            }else {
                $query = $this->_pdo->prepare("UPDATE ElawaMeetings SET roomId = (SELECT id FROM SystemRooms WHERE name = ?)
                                                        WHERE hostId = ? AND categoryId = ?");
                $query->execute(array($_POST['room'], $_POST['host'], $_POST['category']));
            }
		}else if(isset($_POST['hostId'])) {
			$this->sendHostMeetingData($_POST['hostId']);
		}
		else if(isset($_POST['meetingId'])) {
		    $query = $this->_pdo->prepare("UPDATE ElawaMeetings SET isDisabled = ? WHERE id = ?");
		    $query->execute(array($_POST['isDisabled'] == "true" ? 1 : 0, $_POST['meetingId']));
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


	protected function sendHostMeetingData($host) {


		$query = $this->_pdo->prepare("SELECT m.*, c.name FROM ElawaMeetings m
                                                JOIN ElawaCategories c ON (m.categoryId = c.id)
                                                WHERE m.hostId = ?
                                                ORDER BY m.time ASC");
		$query->execute(array($host));
		$meetings = $query->fetchAll();
		if($meetings && count($meetings)) {
			$roomArr = array();
			$roomArr[] = array(
					'id' => 0,
					'name' => "---"
			);
			$rooms = $this->_pdo->query("SELECT * FROM SystemRooms")->fetchAll();
			if(isset($rooms)){
				foreach ($rooms as $room){
					$roomArr[] = array(
						'id' => $room['id'],
						'name' => $room['name']
					);
				}
			}else{
				$this->_interface->showWarning("Es sind keine Räume vorhanden");
			}
			$selectedArr = array();
			$categories = $this->_pdo->query("SELECT * FROM ElawaCategories")->fetchAll();

			foreach ($categories as $category){
				$query = $this->_pdo->prepare("SELECT r.* FROM ElawaMeetings m 
                                                        JOIN SystemRooms r ON (m.roomId = r.id)
                                                        WHERE hostId = ? AND categoryId = ?");
				$query->execute(array($host, $category['id']));
				$selectedRoom = $query->fetch();
				if($selectedRoom){
					if($selectedRoom['id'] != 0)
						$selectedArr[$category['name']] = $selectedRoom['name'];
				}
			}
			$elawaEnabled = $this->isElawaEnabled();
			$sendingArr = array($meetings, $roomArr, $selectedArr, $elawaEnabled, $categories);
			die(json_encode($sendingArr));
		}
		else {
			http_response_code(204);
			die();
		}
	}

}