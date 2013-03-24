<?php

require_once PATH_ADMIN . '/AdminInterface.php';

class MessageTemplateInterface extends AdminInterface {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct ($modPath, $smarty) {

		parent::__construct($modPath, $smarty);
		$this->parentPath = $this->tplFilePath . 'header.tpl';
		$this->smarty->assign('inh_path', $this->parentPath);
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function mainMenu($templates) {

		$this->smarty->assign('templates', $templates);
		$this->smarty->display($this->tplFilePath . 'mainMenu.tpl');
	}

	public function templateCreateForm() {

		$this->smarty->display($this->tplFilePath . 'createTemplate.tpl');
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////
	//Attributes
	/////////////////////////////////////////////////////////////////////

}

?>