<?php

class AdminCheckoutInterface extends AdminInterface {
	
	////////////////////////////////////////////////////////////////////////////////
	//Attributes
	
	////////////////////////////////////////////////////////////////////////////////
	//Constructor
	public function __construct($folder_path) {
		
		parent::__construct($folder_path);
		
		$this->parentPath = $this->tplFilePath  . 'mod_checkout_header.tpl';
		$this->smarty->assign('checkoutParent', $this->parentPath);
	}
	
	////////////////////////////////////////////////////////////////////////////////
	//Methods
	/**
	 * Shows a "card-is-locked"-Dialog
	 */
	public function CardLocked() {
		$this->smarty->display(PATH_SMARTY_CHECKOUT.'/checkout_locked.tpl');
	}
	
	public function Checkout($mealnames) {
		$this->smarty->assign('orders', $mealnames);
		$this->smarty->display($this->tplFilePath  . 'checkout.tpl');
	}
	
	public function CardId() {
		$this->smarty->display($this->tplFilePath . 'form.tpl');
	}
	
	public function ShowInitialMenu(){
		$this->smarty->display($this->tplFilePath . 'checkout_initial_menu.tpl');
	}
	
	public function ShowSettings($count){
		$this->smarty->assign('count', $count);
		$this->smarty->display($this->tplFilePath . 'show_settings.tpl');
	}
	
	public function ShowColorSettings($pcs){
		$this->smarty->assign('pcs', $pcs);
		$this->smarty->display($this->tplFilePath . 'show_color_settings.tpl');
	}
	
}

?>