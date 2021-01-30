<?php

namespace administrator\Elawa\Categories;

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
		$categories = $this->_pdo->query("SELECT * FROM ElawaCategories")->fetchAll();
		$this->_smarty->assign('elawaEnabled', $this->isElawaEnabled());
		$this->_smarty->assign('categories', $categories);
		$this->displayTpl('main.tpl');
	}
	
	protected function deleteCategory($id){
	    $this->_pdo->prepare("DELETE FROM ElawaDefaultMeetingTimes WHERE categoryId = ?")->execute(array($id));
        $this->_pdo->prepare("DELETE FROM ElawaDefaultMeetingRooms WHERE categoryId = ?")->execute(array($id));
	    $this->_pdo->prepare("DELETE FROM ElawaMeetings WHERE categoryId = ?")->execute(array($id));
        $this->_pdo->prepare("DELETE FROM ElawaCategories WHERE id = ?")->execute(array($id));

	}
	
	protected function addCategory($name){
	    $this->_pdo->prepare("INSERT INTO ElawaCategories (name) VALUES (?)")->execute(array($name));
	}
	
	protected function editCategory($id, $name){
	    $this->_pdo->prepare("UPDATE ElawaCategories SET name = ? WHERE id = ?")->execute(array($name, $id));
	}
}

?>