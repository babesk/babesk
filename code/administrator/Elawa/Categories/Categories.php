<?php

namespace administrator\Elawa\Categories;

use Babesk\ORM\ElawaCategory;
require_once PATH_ADMIN . '/Elawa/Elawa.php';

class Categories extends \administrator\Elawa\Elawa {

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		parent::entryPoint($dataContainer);
		if(isset($_GET['action'])){
			switch ($_GET['action']){
				case 2:
					if(isset($_GET['id']))
						$this->deleteCategory($_GET['id']);
					break;
				
				case 3:
					if(isset($_POST['name']))
						$this->addCategory($_POST['name']);
					break;
				case 4:
					if(isset($_POST['name']) && isset($_POST['id']))
						$this->editCategory($_POST['id'], $_POST['name']);
					break;
			}
		}
		$this->showCategories();
	}

	public function showCategories(){
		$categories = $this->_em->getRepository("DM:ElawaCategory")->findAll();
		$elawaEnabled = $this->_em->getRepository('DM:SystemGlobalSettings')->findOneByName('elawaSelectionsEnabled');
		$this->_smarty->assign('elawaEnabled', $elawaEnabled);
		$this->_smarty->assign('categories', $categories);
		$this->displayTpl('main.tpl');
	}
	
	protected function deleteCategory($id){
		$category = $this->_em->getRepository("DM:ElawaCategory")->find($id);
		if(isset($category)){
			$defaultMeetings = $this->_em->getRepository("DM:ElawaDefaultMeetingTime")->findByCategory($category);
			if(isset($defaultMeetings)){
				foreach ($defaultMeetings as $defaultMeeting){
					$this->_em->remove($defaultMeeting);
				}
			}
			$meetings = $this->_em->getRepository("DM:ElawaMeeting")->findByCategory($category);
			if(isset($meetings)){
				foreach ($meetings as $meeting){
					$this->_em->remove($meeting);
				}
			}
			$this->_em->remove($category);
			$this->_em->flush();
		}
	}
	
	protected function addCategory($name){
		$category = new ElawaCategory();
		$category->setName($name);
		
		$this->_em->persist($category);
		$this->_em->flush();
	}
	
	protected function editCategory($id, $name){
		$cat = $this->_em->getRepository("DM:ElawaCategory")->findOneById($id);
		$cat->setName($name);
		$this->_em->persist($cat);
		$this->_em->flush();
	}
}

?>