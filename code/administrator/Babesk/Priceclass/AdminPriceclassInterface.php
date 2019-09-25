<?php

class AdminPriceclassInterface extends AdminInterface {
	
	public function __construct($mod_path) {
		
		parent::__construct($mod_path);
		$this->parentPath = $this->tplFilePath . 'header_priceclass.tpl';
		$this->smarty->assign('pcParent', $this->parentPath);
	}
	
	public function NewPriceclass($groups) {
		$this->smarty->assign('groups', $groups);
		$this->smarty->display($this->tplFilePath . 'new_priceclass_all_groups.tpl');
	}
	
	public function ChangePriceclass($priceclass, $groups, $current_group_name) {
		$this->smarty->assign('ID', $priceclass['ID']);
		$this->smarty->assign('name', $priceclass['name']);
		$this->smarty->assign('price', $priceclass['price']);
		$this->smarty->assign('max_orders', $priceclass['orders_per_user']);
		$this->smarty->assign('groups', $groups);
		$this->smarty->assign('current_group_name', $current_group_name);
		$this->smarty->display($this->tplFilePath . 'change_priceclass.tpl');
	}
	
	public function ShowPriceclasses($priceclasses) {
		$this->smarty->assign('priceclasses', $priceclasses);
		$this->smarty->display($this->tplFilePath . 'show_priceclasses.tpl');
	}
	
	public function Menu() {
		$this->smarty->display($this->tplFilePath . 'priceclass_menu.tpl');
	}
	
}

?>