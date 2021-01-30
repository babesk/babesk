<?php

namespace administrator\Elawa\MeetingTimes;

use Babesk\ORM\ElawaDefaultMeetingTime;
use Babesk\ORM\ElawaCategory;
use MongoDB\Driver\Query;

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
		$categories = $this->_pdo->query("SELECT * FROM ElawaCategories")->fetchAll();
		if(!count($categories))
			$this->_interface->dieError("Es sind keine Kategorien vorhanden!");
		if (!isset($catId))
			$catId = $categories[0]['id'];

		$times = $this->_pdo->prepare("SELECT * FROM ElawaDefaultMeetingTimes WHERE categoryId = ?");
		$times->execute(array($catId));
		$times = $times->fetchAll();

		$elawaEnabled = $this->_pdo->query("SELECT value FROM SystemGlobalSettings WHERE name = 'elawaSelectionsEnabled'")->fetch();

		$this->_smarty->assign('elawaEnabled', $elawaEnabled);
		$this->_smarty->assign('times', $times);
		$this->_smarty->assign('categories', $categories);
		$this->_smarty->assign('catId', $catId);
		$this->displayTpl('main.tpl');
	}
	
	protected function addMeetingTime($categoryId, $start, $length){
		$query = $this->_pdo->prepare("INSERT INTO ElawaDefaultMeetingTimes(categoryId, time, length) VALUES (?,?,?)");
		$query->execute(array($categoryId, $start, $length));
	}
	
	protected function deleteMeetingTime($id) {
		$query = $this->_pdo->prepare("SELECT categoryId FROM ElawaDefaultMeetingTimes WHERE id = ?");
		$query->execute(array($id));
		$categoryId = $query->fetch();
		$query = $this->_pdo->prepare("DELETE FROM ElawaDefaultMeetingTimes WHERE id = ?");
		$query->execute(array($id));
		$this->showTimes($categoryId['categoryId']);
	}
	
	protected function editMeetingTime($id, $start, $length){
		$query = $this->_pdo->prepare("UPDATE ElawaDefaultMeetingTimes SET time = ?, length = ? WHERE id = ?");
		$query->execute(array($start, $length, $id));
	}
}