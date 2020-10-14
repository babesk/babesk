<?php

class SchbasAccountingInterface extends AdminInterface {
	
	////////////////////////////////////////////////////////////////////////////////
	//Attributes
	
	////////////////////////////////////////////////////////////////////////////////
	//Constructor
	public function __construct($folder_path) {
		
		parent::__construct($folder_path);
		
		$this->parentPath = $this->tplFilePath  . 'mod_SchbasAccounting_header.tpl';
		$this->smarty->assign('checkoutParent', $this->parentPath);
	}
	
	////////////////////////////////////////////////////////////////////////////////
	//Methods
	
	public function MainMenu($listOfClasses, $listOfClassesRebmemer) {
		$this->smarty->assign('listOfClasses', $listOfClasses);
		$this->smarty->assign('listOfClassesRebmemer', $listOfClassesRebmemer);
		$this->smarty->display($this->tplFilePath . '/menu.tpl');
	}
	
	public function Scan() {
		$this->smarty->display($this->tplFilePath . '/scan.tpl');
	}
	
	public function showRememberList($schueler1, $class, $title, $date, $schuelerTotalNr){
		$this->smarty->assign('schuelerTotalNr', $schuelerTotalNr);
		$this->smarty->assign('schueler1', $schueler1);
		$this->smarty->assign('class', $class);
		$this->smarty->assign('title', $title);
		$this->smarty->assign('date', $date);
		$this->smarty->display($this->tplFilePath . 'showRememberList.tpl');
	}
	
	public function showRememberList2($name, $forename, $books, $nr, $className, $listOfClasses){
		$this->smarty->assign('name', $name);
		$this->smarty->assign('forename', $forename);
		$this->smarty->assign('books', $books);
		$this->smarty->assign('nr', $nr);
		$this->smarty->assign('className', $className);
		$this->smarty->assign('listOfClasses', $listOfClasses);
		$this->smarty->display($this->tplFilePath . 'showRememberList2.tpl');
	}
	
	public function showRebmemerList2($name, $forename, $books, $nr, $className, $listOfClasses){
		$this->smarty->assign('name', $name);
		$this->smarty->assign('forename', $forename);
		$this->smarty->assign('books', $books);
		$this->smarty->assign('nr', $nr);
		$this->smarty->assign('className', $className);
		$this->smarty->assign('listOfClasses', $listOfClasses);
		$this->smarty->display($this->tplFilePath . 'showRebmemerList2.tpl');
	}
	
	function reminderSent(){
		$this->smarty->display($this->tplFilePath . 'reminderSent.tpl');
	}
	
	function showDelete(){
		$this->smarty->display($this->tplFilePath . 'scanForRemoving.tpl');
	}
	function showDeleteSuccess(){
		$this->dieSuccess("Antrag erfolgreich gel&ouml;scht!");
	}
}

?>
