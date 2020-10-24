<?php

namespace administrator\System\Rooms;

require_once PATH_ADMIN . '/System/System.php';
require_once PATH_INCLUDE . '/Module.php';

class Rooms extends \System {

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		parent::entryPoint($dataContainer);
		$this->moduleTemplatePathSet();
		if(isset($_GET['action'])){
			switch ($_GET['action']){
				case 2:
					if(isset($_GET['id']))
						$this->deleteRoom($_GET['id']);
					break;
				
				case 3:
					if(isset($_POST['name']))
						$this->addRoom($_POST['name']);
					break;
				case 4:
					if(isset($_POST['name']) && isset($_POST['id']))
						$this->editRoom($_POST['id'], $_POST['name']);
					break;
			}
		}
		$this->showRooms();
	}

	public function showRooms(){
	    $rooms = $this->_pdo->query("SELECT * FROM SystemRooms")->fetchAll();
		$this->_smarty->assign('rooms', $rooms);
		$elawaEnabled = $this->_pdo->query("SELECT * FROM SystemGlobalSettings WHERE name = 'elawaSelectionsEnabled'")->fetch();
		$this->_smarty->assign('elawaEnabled', $elawaEnabled);
		$this->displayTpl('main.tpl');
	}
	
	protected function deleteRoom($id){
	    $stmt = $this->_pdo->prepare("DELETE FROM SystemRooms WHERE id = ?");
	    $stmt->execute(array($id));
	}
	
	protected function addRoom($name){
		$stmt = $this->_pdo->prepare("INSERT INTO SystemRooms(name) VALUES (?)");
		$stmt->execute(array($name));
	}
	
	protected function editRoom($id, $name){
	    $stmt = $this->_pdo->prepare("UPDATE SystemRooms SET name = ? WHERE id = ?");
	    $stmt->execute(array($name, $id));
	}
}

?>