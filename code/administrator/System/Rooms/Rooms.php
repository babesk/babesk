<?php

namespace administrator\System\Rooms;

use Babesk\ORM\SystemRoom;
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
		$rooms = $this->_em->getRepository("DM:SystemRoom")->findAll();
		$this->_smarty->assign('rooms', $rooms);
		$elawaEnabled = $this->_em->getRepository('DM:SystemGlobalSettings')->findOneByName('elawaSelectionsEnabled');
		$this->_smarty->assign('elawaEnabled', $elawaEnabled);
		$this->displayTpl('main.tpl');
	}
	
	protected function deleteRoom($id){
		$room = $this->_em->getRepository("DM:SystemRoom")->find($id);
		if(isset($room)){
			$this->_em->remove($room);
			$this->_em->flush();
		}
	}
	
	protected function addRoom($name){
		$room = new SystemRoom();
		$room->setName($name);
		
		$this->_em->persist($room);
		$this->_em->flush();
	}
	
	protected function editRoom($id, $name){
		$room = $this->_em->getRepository("DM:SystemRoom")->findOneById($id);
		$room->setName($name);
		$this->_em->persist($room);
		$this->_em->flush();
	}
}

?>