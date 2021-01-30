<?php

namespace administrator\Elawa\Meetings\GenerateBare;

require_once PATH_ADMIN . '/Elawa/Meetings/Meetings.php';

class GenerateBare extends \administrator\Elawa\Meetings\Meetings {

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		parent::entryPoint($dataContainer);
		$numDeleted = $this->clearMeetings();
		$numCreated = $this->generate();
		$this->_interface->dieSuccess(
			'Die Sprechzeiten wurden erfolgreich erstellt. <br>' .
			'Es wurden ' . $numDeleted . ' alte Sprechzeiten gelöscht und ' .
			$numCreated . ' neue erstellt.'
		);
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	/**
	 * Removes all existing meetings.
	 * @return int    The number of meetings deleted
	 */
	protected function clearMeetings() {

		$query = $this->_pdo->prepare('DELETE FROM ElawaMeetings');
		$numDeleted = $query->execute();
		return $query->rowCount();
	}

	protected function generate() {

		$users = $this->getHosts();
		$defaultTimes = $this->_pdo->query("SELECT * FROM ElawaDefaultMeetingTimes")->fetchAll();
		$countCreated = 0;
		foreach($users as $user) {
			foreach($defaultTimes as $defaultTime) {
				$this->persistNewMeeting($user, $defaultTime);
				$countCreated++;
			}
		}
		return $countCreated;
	}


	protected function persistNewMeeting($user, $defaultTime) {

		$room = 0;
        $query = $this->_pdo->prepare("SELECT * FROM ElawaDefaultMeetingRooms WHERE hostId = ?");
        $query->execute(array($user['ID']));
        $defaultRooms = $query->fetchAll();
		if($defaultRooms && count($defaultRooms)) {
			foreach($defaultRooms as $defRoom) {
				if($defRoom['categoryId'] == $defaultTime['categoryId']) {
					$room = $defRoom['roomId'];
				}
			}
		}
		$query = $this->_pdo->prepare("INSERT INTO ElawaMeetings (visitorId, hostId, categoryId, roomId, time, length, isDisabled) 
                                                VALUES ('0', ?, ?, ?, ?, ?, '0')");
		$query->execute(array($user['ID'], $defaultTime['categoryId'], $room, $defaultTime['time'], $defaultTime['length']));
	}

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

}

?>