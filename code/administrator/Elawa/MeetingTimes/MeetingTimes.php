<?php

namespace administrator\Elawa\MeetingTimes;

use Babesk\ORM\ElawaDefaultMeetingTime;
use Babesk\ORM\ElawaCategory;
require_once PATH_ADMIN . '/Elawa/Elawa.php';

class MeetingTimes extends \administrator\Elawa\Elawa {

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		parent::entryPoint($dataContainer);
		if(isset($_GET['action'])){
			switch ($_GET['action']){
				case 2:
					if(isset($_GET['id']))
						$this->deleteMeetingTime($_GET['id']);
					return;
				case 3:
					if(isset($_POST['category'])&&isset($_POST['start'])&&isset($_POST['length']))
						$this->addMeetingTime($_POST['category'], $_POST['start'], $_POST['length']);
					break;
				case 4:
					if(isset($_POST['id'])&&isset($_POST['start'])&&isset($_POST['length']))
						$this->editMeetingTime($_POST['id'], $_POST['start'], $_POST['length']);
					break;
			}
		}
		if(isset($_POST['category']))
			$this->showTimes($_POST['category']);
		else
			$this->showTimes();
	}
	
	public function showTimes($catId = NULL){
		$categories = $this->_em->getRepository("DM:ElawaCategory")->findAll();
		if(!count($categories))
			$this->_interface->dieError("Es sind keine Kategorien vorhanden!");
		if (!isset($catId))
			$catId = $categories[0]->getId();
		$query = $this->_em->createQuery("
				SELECT t FROM DM:ElawaDefaultMeetingTime t 
				WHERE t.category = :category");
		$query->setParameter('category', $catId);
		$times = $query->getResult();
		$elawaEnabled = $this->_em->getRepository('DM:SystemGlobalSettings')->findOneByName('elawaSelectionsEnabled');
		$this->_smarty->assign('elawaEnabled', $elawaEnabled);
		$this->_smarty->assign('times', $times);
		$this->_smarty->assign('categories', $categories);
		$this->_smarty->assign('catId', $catId);
		$this->displayTpl('main.tpl');
	}
	
	protected function addMeetingTime($categoryId, $start, $length){
		$category = $this->_em->getRepository("DM:ElawaCategory")->findOneById($categoryId);
		$meetingTime = new ElawaDefaultMeetingTime();
		$meetingTime->setCategory($category);
		$meetingTime->setLength(new \DateTime($length));
		$meetingTime->setTime(new \DateTime($start));
		$this->_em->persist($meetingTime);
		$this->_em->flush();
	}
	
	protected function deleteMeetingTime($id) {
		$time = $this->_em->getRepository("DM:ElawaDefaultMeetingTime")->find($id);
		$categoryId = $time->getCategory()->getId();
		if(isset($time)){
			$this->_em->remove($time);
			$this->_em->flush();
		}
		$this->showTimes($categoryId);
	}
	
	protected function editMeetingTime($id, $start, $length){
		$time = $this->_em->getRepository("DM:ElawaDefaultMeetingTime")->findOneById($id);
		$time->setTime(new \DateTime($start));
		$time->setLength(new \DateTime($length));
		$this->_em->persist($time);
		$this->_em->flush();
	}
}