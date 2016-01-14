<?php

class AdminCheckoutProcessing {

	////////////////////////////////////////////////////////////////////////////////
	//Attributes
	private $cardManager;
	private $userManager;
	private $orderManager;
	private $mealManager;
	private $globalSettingsManager;
	private $priceClassManager;
	private $checkoutInterface;
	private $msg;

	////////////////////////////////////////////////////////////////////////////////
	//Constructor
	public function __construct ($checkoutInterface) {

		require_once PATH_ACCESS . '/GlobalSettingsManager.php';
		require_once PATH_ACCESS . '/CardManager.php';
		require_once PATH_ACCESS . '/UserManager.php';
		require_once PATH_ACCESS . '/OrderManager.php';
		require_once PATH_ACCESS . '/MealManager.php';
		require_once PATH_ACCESS . '/PriceClassManager.php';
		require_once 'AdminCheckoutInterface.php';

		$this->globalSettingsManager = new GlobalSettingsManager();
		$this->cardManager = new CardManager();
		$this->userManager = new UserManager();
		$this->orderManager = new OrderManager();
		$this->mealManager = new MealManager();
		$this->priceClassManager = new PriceClassManager();
		$this->checkoutInterface = $checkoutInterface;

		$this->msg = array(
			'err_card_id'			 => 'Dies ist keine gültige Karten-ID ("%s")',
			'err_get_user_by_card'	 => 'Anhand der Kartennummer konnte kein Benutzer gefunden werden.',
			'err_no_orders'			 => 'Es sind keine Bestellungen für diesen Benutzer vorhanden.',
			'err_meal_not_found'	 => 'Ein Menü konnte nicht gefunden werden!',
			'err_connection'		 => 'Ein Fehler ist beim Verbinden zum MySQL-Server aufgetreten',
			'msg_order_fetched'		 => 'Die Bestellung wurde schon abgeholt',);
	}

	////////////////////////////////////////////////////////////////////////////////
	//Methods
	/**
	 * Displays the names of all orders for today
	 * @param string $card_id The ID of the Card
	 */
	public function Checkout ($card_id) {
		if ($card_id == null) {
			$meals = array();
			if(isset($_COOKIE['meals']))
				$meals = json_decode($_COOKIE['meals'], true);
			$last_meals_cnt = $this->globalSettingsManager->valueGet('checkout_last_meals_counter');
			if (count($meals)>$last_meals_cnt)
				$meals = array_slice($meals, $last_meals_cnt*(-1));
			foreach ($meals as &$meal){
				$meal['color'] = $this->priceClassManager->getColor($meal['pc']);
			}
			$this->checkoutInterface->Checkout($meals);
			return;
		}
		if (!$this->cardManager->valid_card_ID($card_id))
			$this->checkoutInterface->showMsg(sprintf($this->msg['err_card_id'], $card_id));

		$uid = $this->GetUser($card_id);
		$orders = $this->GetOrders($uid);
		if(!isset($_COOKIE['meals']))
			$meals = array();
		else 
			$meals = json_decode($_COOKIE['meals'], true);
		
		//if(isset($orders)){
		foreach ($orders as $order) {
			$mealname = $this->GetMealName($order['MID']);
			//$mealname = $this->OrderFetched($order['ID'], $mealname);
			$row['name'] = $this->userManager->getForename($uid)." ".$this->userManager->getName($uid);
			$pc = $this->mealManager->getPriceclass($order['MID']);
			$row['pc'] = $pc;
			$row['menu'] = $this->priceClassManager->getPriceClassName($pc)[0]['name'];
			$row['meal'] = $mealname;
			$row['color'] = $this->priceClassManager->getColor($pc);
			if($this->orderManager->OrderFetched($order['ID']))
				$this->checkoutInterface->showWarning($this->msg['msg_order_fetched'].": ".$row['menu']);
			$this->orderManager->setOrderFetched($order['ID']);
			$meals[] = $row;
		}//}
		$last_meals_cnt = $this->globalSettingsManager->valueGet('checkout_last_meals_counter');
		
		if (count($meals)>$last_meals_cnt)
			$meals = array_slice($meals, $last_meals_cnt*(-1));
		
		foreach ($meals as &$meal){
			$meal['color'] = $this->priceClassManager->getColor($meal['pc']);
		}
		setcookie('meals', json_encode($meals));
		

		$this->checkoutInterface->Checkout($meals);
	}

	////////////////////////////////////////////////////////////////////////////////
	//Implementations
	/**
	 * Looks the user for the given CardID up, checks if the Card is locked and returns the UserID
	 * @param string $card_id The ID of the Card
	 * @return string UserID
	 */
	public function GetUser ($card_id) {

		try {
			$uid = $this->cardManager->getUserID($card_id);
			if ($this->userManager->checkAccount($uid)) {
				$this->checkoutInterface->CardLocked();
			}
		} catch (Exception $e) {
			$uid = NULL;
			$this->checkoutInterface->showError(_g('Could not find the User by Cardnumber %1$s', $card_id));
		}
		return $uid;
	}

	/**
	 * Gets all orders of today for the User with the ID $uid
	 * @param string $uid The UserID
	 * @return array() The Orders
	 */
	public function GetOrders ($uid) {

		$date = date("Y-m-d");
		try {
			$orders = $this->orderManager->getAllOrdersOfUserAtDate($uid, $date);
		} catch (MySQLVoidDataException $e) {
			$orders = array();
			$this->checkoutInterface->showError($this->msg['err_no_orders']);
		}
		catch (Exception $e) {
			$this->checkoutInterface->showError($e->getMessage);
		}
		return $orders;
	}

	/**
	 * Fetches the Mealname for the given MealID $mid from the MySQL-Server
	 * @param string $mid The ID of the meal
	 * @return string The mealname
	 */
	public function GetMealName ($mid) {

		try {
			$mealname = $this->mealManager->GetMealName($mid);
		} catch (MySQLVoidDataException $e) {
			/**
			 * @FIXME Error should not kill whole Process, just one Menu couldnt be fetched!
			 */
			$this->checkoutInterface->dieError($this->msg['err_meal_not_found']);
		}
		catch (Exception $e) {
			$this->checkoutInterface->dieError($this->msg['err_connection'] . '<br>' . $e->getMessage());
		}
		return $mealname;
	}

	/**
	 * Looks up if the Order is already fetched. If no,it sets it to fetched.
	 * If yes, it changes the Mealname to let the User know that it is already fetched.
	 * @param string $order_id The ID of the Order
	 * @param string $mealname The Mealname
	 * @return string The Final Mealname
	 */
	public function OrderFetched ($order_id, $mealname) {

		$final_mealname;
		if (!$this->orderManager->OrderFetched($order_id)) {
			$final_mealname = $mealname;
			$this->orderManager->setOrderFetched($order_id);
		} else {
			$final_mealname = $this->msg['msg_order_fetched'] . ' : ' . $mealname;
		}
		return $final_mealname;
	}
	
	public function ShowSettings() {
		$count = $this->globalSettingsManager->valueGet('checkout_last_meals_counter');
		$this->checkoutInterface->ShowSettings($count);
	}
	
	public function SaveSettings($count) {
		$count = $this->globalSettingsManager->valueSet('checkout_last_meals_counter', $count);
		$this->checkoutInterface->showInitialMenu();
	}
	
	public function ShowColorSettings() {
		$pcs = $this->priceClassManager->getAllPriceClassesPooled();
		foreach ($pcs as &$pc){
			$pc['color'] = $this->priceClassManager->getColor($pc['pc_ID']);
		}
		$this->checkoutInterface->ShowColorSettings($pcs);
	}
	
	public function SaveColorSettings($colors) {
		$pcs = $this->priceClassManager->getAllPriceClassesPooled();
		foreach ($pcs as $pc){
			//if(isset($colors[$pc['pc_ID']]))
			$this->priceClassManager->setColor($pc['pc_ID'], $colors[$pc['pc_ID']]);
		}
		$this->checkoutInterface->showInitialMenu();
	}
}

?>
