<?php 


class AdminSoliInterface extends AdminInterface {
	function __construct($mod_path){
		parent::__construct($mod_path);
		$this->parentPath = $this->tplFilePath . 'header_soli.tpl';
		$this->smarty->assign('soliParent', $this->parentPath);
	}
	
	function ShowInitialMenu() {
		$this->smarty->display($this->tplFilePath.'soli_initial_menu.tpl');
	}
	
	function AddCoupon($solis) {
		$this->smarty->assign('solis', $solis);
		$this->smarty->display($this->tplFilePath.'add_coupon.tpl');
	}
	
	function ShowCoupon($coupons) {
		$this->smarty->assign('coupons', $coupons);
		$this->smarty->display($this->tplFilePath.'show_coupons.tpl');
	}
	
	function ConfirmDelCoupon($id, $username) {
		$this->smarty->assign('id', $id);
		$this->smarty->assign('username', $username);
		$this->smarty->display($this->tplFilePath.'conf_del_coupon.tpl');
	}
	
	function AskWeekForPdf() {
		$this->smarty->display($this->tplFilePath.'week_for_pdf.tpl');
	}
	
	/**
	 * This function shows specific orders of soli-Users
	 * 
	 * @param Array $orders The orders-array. it has 2 dimensions; the first dimension is every single order, the second
	 * the specific order-data (@see show_orders.tpl)
	 * @param integer $weeknum the number of the week
	 * @param string $username The Username whose orders should be displayed
	 * @param float $sum_pricediff The overall Pricedifference of all orders (difference between soli_price and standard-
	 * mealprice
	 */
	function ShowSpecOrders($orders, $startdate, $enddate, $username, $sum_pricediff) {
		$this->smarty->assign('orders', $orders);
		$this->smarty->assign('start', $startdate);
        $this->smarty->assign('end', $enddate);
		$this->smarty->assign('name', $username);
		$this->smarty->assign('sum', $sum_pricediff);
		$this->smarty->display($this->tplFilePath.'show_orders.tpl');
	}
	
	function ShowNoOrdersFound($startdate, $enddate, $username) {
		$this->smarty->assign('start', $startdate);
		$this->smarty->assign('end', $enddate);
		$this->smarty->assign('name', $username);
		$this->smarty->display($this->tplFilePath.'show_no_orders_found.tpl');
	}
	
	function AskShowSoliUser($solis) {
		$this->smarty->assign('solis', $solis);
		$this->smarty->display($this->tplFilePath.'show_orders_select_date.tpl');
	}
	
	function ShowSoliUser($solis) {
		$this->smarty->assign('users', $solis);
		$this->smarty->display($this->tplFilePath.'show_soli.tpl');
	}
	
	function ChangeSettings($soli_price, $priceclasses) {
		$this->smarty->assign('old_price', $soli_price);
		$this->smarty->assign('priceclasses', $priceclasses);
		$this->smarty->display($this->tplFilePath.'show_settings.tpl');
	}
	
	function AskCopyOldOrdersToSoli() {
		$this->smarty->display($this->tplFilePath.'conf_copy_old_orders.tpl');
	}
}


?>