<?php

class AdminMealInterface extends AdminInterface {
	public function __construct($mod_path) {
		parent::__construct($mod_path);
		$this->parentPath = $this->tplFilePath . 'meals_header.tpl';
		$this->smarty->assign('mealParent', $this->parentPath);
		$this->smarty->assign('inh_path', $this->parentPath);
	}

	public function Menu() {
		$this->smarty->display($this->tplFilePath . 'meals_initial_menu.tpl');
	}

	public function AddMeal($pc_ids, $pc_names) {
		$this->smarty->assign('price_class_id', $pc_ids);
		$this->smarty->assign('price_class_name', $pc_names);
		$this->smarty->display($this->tplFilePath . 'add_meal.tpl');
	}

	public function ShowMeals($meals) {
		$this->smarty->assign('meals', $meals);
		$this->smarty->display($this->tplFilePath . 'show_meals.tpl');
	}

	public function ShowOrders ($num_orders, $orders, $ordering_date) {
		$this->smarty->assign('num_orders', $num_orders);
		$this->smarty->assign('orders', $orders);
		$this->smarty->assign('ordering_date', $ordering_date);
		$this->smarty->display($this->tplFilePath . 'show_orders.tpl');
	}

	/**
	 * Shows a form to the user to select the date of orders to be displayed
	 * @param array(day,month,year) $today
	 */
	public  function ShowOrdersSelectDate ($today) {
		$this->smarty->assign('today', $today);
		$this->smarty->display($this->tplFilePath . 'show_orders_select_date.tpl');
	}

	public function EditInfotexts($it1, $it2) {
		$this->smarty->assign('infotext1', $it1);
		$this->smarty->assign('infotext2', $it2);
		$this->smarty->display($this->tplFilePath. 'edit_infotext.tpl');
	}

	public function FinEditInfotexts ($it1, $it2) {
		$this->smarty->assign('infotext1', $it1);
		$this->smarty->assign('infotext2', $it2);
		$this->smarty->display($this->tplFilePath . 'edit_infotext_fin.tpl');
	}

	public  function  DuplicateMeal($pc_ids, $pc_names, $name, $description, $pc_ID, $max_orders, $date) {

		$this->smarty->assign('price_class_id', $pc_ids);
		$this->smarty->assign('price_class_name', $pc_names);

		$this->smarty->assign('name_str', $name);
		$this->smarty->assign('descr_str', $description);
		$this->smarty->assign('pc_str', $pc_ID);
		$this->smarty->assign('max_order_str', $max_orders);
		$this->smarty->assign('date_str', $date);

		$this->smarty->display($this->tplFilePath . 'add_meal.tpl');
	}

	public function maxOrderAmountSetting($amount) {

		$this->smarty->assign('amount', $amount);
		$this->smarty->display($this->tplFilePath . 'maxOrderAmount.tpl');
	}

	public function showOrdersPDF($num_orders, $sorted_orders, $date){
        $this->smarty->assign('num_orders', $num_orders);
        $this->smarty->assign('orders', $sorted_orders);
        $this->smarty->assign('ordering_date', $date);
        $pdf_content = $this->smarty->fetch(PATH_SMARTY_TPL.'/pdf/show_orders.pdf.tpl');
        return $pdf_content;
	}
}

?>
