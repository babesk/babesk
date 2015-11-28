<?php

require_once 'AdminSoliInterface.php';
require_once PATH_ACCESS . '/SoliCouponsManager.php';
require_once PATH_ACCESS . '/SoliOrderManager.php';

class AdminSoliProcessing {

	function __construct($soliInterface) {
		$this->soliInterface = $soliInterface;
		$this->soliCouponManager = new SoliCouponsManager();
		$this->soliOrderManager = new SoliOrderManager();
		$this->msg = array('ERR_SQL_NO_DATA' => 'fehlerhafter ID-Eintrag',
				'ERR_SQL' => 'Ein Fehler ist bei der Verbindung zum SQL-Server aufgetreten',
				'ERR_SQL_NO_ORDERS' => 'Es wurden keine Bestellungen aufgegeben',
				'ERR_ORDERS_NOT_FOUND' => 'Es wurden keine Bestellungen für den angegebenen Zeitraum gefunden',
				'ERR_USER_NO_SOLI' => 'Der angegebene Benutzer ist nicht Soli-berechtigt',
				'ERR_USER_NO_SOLI_FOUND' => 'Es konnten keine Soli-Benutzer gefunden werden',
				'ERR_ADD_COUPON' => 'Ein Fehler ist beim Hinzufügen eines Coupons aufgetreten',
				'ERR_GET_COUPON' => 'Es wurden keine Coupons gefunden',
				'ERR_DEL_COUPON' => 'Ein Fehler ist beim löschen des Coupons aufgetreten',
				'FIN_DEL_COUPON' => 'Der Coupon wurde erfolgreich gelöscht',
				'ERR_FETCH_COUPON_INF' => 'Ein Fehler ist beim abholen der Daten aufgetreten',
				'ERR_INP' => 'Ein falscher Wert wurde eingegeben', 'COUPON_ADDED' => 'Der Coupon wurde hinzugefügt',
				'SOLI_ERR_PRICE' => 'Konnte den Preis für das Teilhabepaket nicht abrufen!',
				'SOLI_ERR_INP_PRICE' => 'Der Preis für das Teilhabepaket wurde nicht korrekt eingegeben.',
				'SOLI_ERR_CHANGE_PRICE' => 'Konnte den Preis für das Teilhabepaket nicht ändern!',
				'SOLI_FIN_CHANGE' => 'Der Soli-Preis wurde erfolgreich verändert',);
	}

	/**
	 * Adds a Coupon
	 * This function adds a coupon to the coupon-table of the MySQL-Server if the Parameters are not NULL.
	 * If one or more of the parameters are NULL, AddCoupon just shows an add-Coupon-form to the user
	 * @param string $beg_date format: YYYY-MM-DD
	 * @param string $end_date format: YYYY-MM-DD
	 * @param numeric_string $uid The ID of the User to whom the card belongs to
	 */
	function AddCoupon($beg_date, $end_date, $uid) {
		require_once PATH_ACCESS . '/UserManager.php';
		$userManager = new UserManager();
		if ($beg_date && $end_date && $uid) {
			/*
			 * add Coupon to table
			 */
			try {
				//check input if there are any errors
				inputcheck($beg_date, 'birthday', 'Datum');
				inputcheck($end_date, 'birthday', 'Datum');
				inputcheck($uid, 'id', 'BenutzerID');
			} catch (WrongInputException $e) {
				$this->soliInterface->dieError($this->msg['ERR_INP'] . ': ' . $e->getFieldName());
			}
			if (!$userManager->isSoli($uid))//is the user soli?
				$this->soliInterface->dieError($this->msg['ERR_USER_NO_SOLI']);
			try {
				$this->soliCouponManager->addCoupon($beg_date, $end_date, $uid);
			} catch (Exception $e) {
				$this->soliInterface->dieError($this->msg['ERR_ADD_COUPON']);
			}
			$this->soliInterface->dieMsg($this->msg['COUPON_ADDED']);
		} else {
			/*
			 *Show Add-Coupon-Interface
			 */
			require_once PATH_ACCESS . '/UserManager.php';

			$userManager = new UserManager();

			try {
				$solis_arr = $userManager->getAllSoli();
			} catch (MySQLVoidDataException $e) {
				$this->soliInterface->dieError($this->msg['ERR_USER_NO_SOLI_FOUND']);
			}
			$this->soliInterface->AddCoupon($solis_arr);
		}
	}

	/**
	 * Shows the Coupons
	 * ShowCoupons() shows all SoliCoupons that are existing in the coupon-table to the user
	 */
	function ShowCoupons() {

		require_once PATH_ACCESS . '/UserManager.php';

		try {
			$coupons = $this->soliCouponManager->getAllCoupons();
		} catch (Exception $e) {
			$this->soliInterface->dieError($this->msg['ERR_GET_COUPON']);
		}
		$userManager = new UserManager();

		foreach ($coupons as &$coupon) {
			$coupon['username'] = $userManager->getForename(($coupon['UID'])) . '.'
					. $userManager->getName(($coupon['UID']));
			$coupon['startdate'] = formatDate($coupon['startdate']);
			$coupon['enddate'] = formatDate($coupon['enddate']);
		}

		$this->soliInterface->ShowCoupon($coupons);
	}

	/**
	 * Deletes a coupon with the ID $id if confirmed is true or shows a Confirmation-Dialog
	 * @param numeric_string $id The ID of the Coupon to delete
	 * @param boolean $confirmed Shows a confirmation-dialog if set to false
	 */
	function DeleteCoupon($id, $confirmed) {
		if ($confirmed) {

			//delete the Coupon
			try {
				$this->soliCouponManager->delEntry($id);
			} catch (Exception $e) {
				$this->soliInterface->dieError($this->msg['ERR_DEL_COUPON']);
			}
			$this->soliInterface->dieMsg($this->msg['FIN_DEL_COUPON']);
		} else {

			//Show Confirmation-Dialog
			require_once PATH_ACCESS . '/UserManager.php';
			try {
				$userManager = new UserManager();
				$uid = $this->soliCouponManager->getEntryValue($id, 'UID');
				$username = $userManager->getForename($uid) . '.' . $userManager->getName($uid);
			} catch (Exception $e) {
				$this->soliInterface->dieError(ERR_FETCH_COUPON_INF);
			}
			$this->soliInterface->ConfirmDelCoupon($id, $username);
		}
	}

	/**
	 *
	 * Enter description here ...
	 */
	function ShowUsers() {

		require_once PATH_ACCESS . '/UserManager.php';
		require_once PATH_ACCESS.'/GroupManager.php';

		$groupManager = new GroupManager();
		$userManager = new UserManager();

		try {
			$soli_users = $userManager->getAllSoli();
		} catch (MySQLVoidDataException $e) {
			$this->soliInterface->dieError($this->msg['ERR_USER_NO_SOLI_FOUND']);
		} catch (Exception $e) {
			$this->soliInterface->dieError($this->msg['ERR_SQL'] . ':' . $e->getMessage());
		}

		foreach ($soli_users as &$soli_user) {
			$soli_user ['groupname'] = $groupManager->getGroupName($soli_user['GID']);
		}

		$this->soliInterface->ShowSoliUser($soli_users);
	}

	/**
	 *
	 * Enter description here ...
	 */
	function ShowAllSoliOrders() {
		require_once PATH_ACCESS . '/UserManager.php';
		require_once PATH_ACCESS . '/MealManager.php';
		require_once PATH_ACCESS . '/GlobalSettingsManager.php';
		require_once PATH_ACCESS . '/PriceClassManager.php';

		$soliOrderManager = $this->soliOrderManager;
		$soliCouponManager = $this->soliCouponManager;
		$gsManager = new GlobalSettingsManager();
		$userManager = new UserManager();
		$mealManager = new MealManager();
		$pcManager = new PriceClassManager();

		try {
			$soli_orders = $soliOrderManager->getSortedOrders();
		} catch (MySQLVoidDataException $e) {
			$this->soliInterface->dieError($this->msg['ERR_SQL_NO_ORDERS']);
		}

		$orders = array();

		foreach ($soli_orders as $soli_order) {

			if (!$soli_order)
				continue;

			//ID of order itself
			$id = $soli_order['ID'];

			try { //Username
				$username = sprintf('%s %s', $userManager->getEntryValue($id, 'forename'),
									$userManager->getEntryValue($soli_order['UID'], 'name'));
			} catch (MySQLVoidDataException $e) {
				$username = $this->msg['ERR_SQL_NO_DATA'];
			}

			$mealname = $soli_order['mealname'];
			$date = formatDate($soli_order['date']);

			$soli_price = $soli_order['soliprice'];
			$bank_price = $soli_order['mealprice'] - $soli_price;

			try {//coupons
				$coupon = $soliCouponManager->searchEntry('UID = ' . $soli_order['UID']);
				$coupon_beg_date = $coupon['startdate'];
				$coupon_end_date = $coupon['enddate'];
			} catch (MySQLVoidDataException $e) {
				//no coupon for the user is existing, dont make the field spitting errors
				$coupon_beg_date = '---';
				$coupon_end_date = '---';
			}
			$orders[] = array('username' => $username, 'mealname' => $mealname, 'date' => $date,
					'coup_begin' => $coupon_beg_date, 'coup_end' => $coupon_end_date, 'soli_price' => $soli_price,
					'bank_price' => $bank_price);
		}
	}

	/**
	 * Shows the Orders which are ordered by User $uid in week $weeknum
	 * Enter description here ...
	 * @param $weeknum the number of the week in the year
	 * @param $uid the UserID of the soli-user who ordered meals
	 */
	function ShowSoliOrdersByDate($weeknum, $uid) {
		require_once PATH_ACCESS . '/UserManager.php';
		$userManager = new UserManager();
		if ($weeknum && $uid) {

			$orders = array();
			$monday = getFirstDayOfWeek(date('Y'), $weeknum);
			$sum_pricediff = 0.00;
			$secs_per_day = 86400;

			for ($i = 0; $i < 5; $i++) {
				$buffer = array();
				try {
					$buffer = $this->soliOrderManager->
						getOrdersByUserandMealdate($uid, date('Y-m-d',
							$monday + ($i * $secs_per_day)));
				} catch (MySQLVoidDataException $e) {
				//	$this->soliInterface->showMsg($this->msg['ERR_ORDERS_NOT_FOUND']);
				}
				foreach ($buffer as $order)
					$orders[] = $order;
			}
			$username = $userManager->getForename($uid) . ' ' . $userManager->getName($uid);
			if (!count($orders)) {
				//$this->soliInterface->dieError($this->msg['ERR_ORDERS_NOT_FOUND']);
				$this->soliInterface->ShowNoOrdersFound($weeknum, $username);
			} else {
			foreach ($orders as &$order) {
				$sum_pricediff += $order['mealprice'] - $order['soliprice'];
			}


			$this->soliInterface->ShowSpecOrders($orders, $weeknum, $username, $sum_pricediff);
			}
		} else {
			try {
				//Show Form to fill out Weeknumber and Soli
				$solis = $userManager->getAllSoli();
			} catch (Exception $e) {
				$this->soliInterface->dieError($this->msg['ERR_USER_NO_SOLI_FOUND']);
			}
			$this->soliInterface->AskShowSoliUser($solis);
		}

	}

	/**
	 * Change Settings for Solis
	 * Enter description here ...
	 */
	function ChangeSettings($soli_price) {

		require_once PATH_ACCESS . '/GlobalSettingsManager.php';

		$gbManager = new GlobalSettingsManager();

		if ($soli_price !== NULL) {
			try {
				try {//inputcheck
					inputcheck($_POST['soli_price'], 'credits');
				} catch (Exception $e) {
					die_error(SOLI_ERR_INP_PRICE);
					$this->soliInterface->dieError($this->msg['SOLI_ERR_INP_PRICE']);
				}
				$gbManager->changeSoliPrice($_POST['soli_price']);
			} catch (Exception $e) {
				die_error(SOLI_ERR_CHANGE_PRICE . ':' . $e->getMessage());
				$this->soliInterface->dieError($this->msg['SOLI_ERR_CHANGE_PRICE '] . ':' . $e->getMessage());
			}
			$this->soliInterface->dieMsg($this->msg['SOLI_FIN_CHANGE']);
		} else {
			try {
				$soli_price = $gbManager->getSoliPrice();
			} catch (Exception $e) {
				$this->soliInterface->dieError($this->msg['SOLI_ERR_PRICE']);
			}
			$this->soliInterface->ChangeSettings($soli_price);
		}
	}
	
	/**
	 * Creates an PDF with the Orders of all SoliUsers in a given week or month.
	 * @param $kw week/month number
	 * @param $weekMode true, if filtered by week
	 * 					false, if filtered by month
	 */
	function AllSoliOrdersToPDFByWeekOrMonth($dataContainer, $kw, $weekMode) {
		require_once PATH_ACCESS . '/UserManager.php';
		require_once PATH_INCLUDE . '/pdf/GeneralPdf.php';
		$userManager = new UserManager();
		
		$pdo = $dataContainer->getPdo();
		$smarty = $dataContainer->getSmarty();
		
		if($weekMode){
			$firstDay = getFirstDayOfWeek(date('Y'), $kw);
			$days = 5;
			$title = "SoliBestellungen Kalenderwoche " . $kw;
		}else{
			$firstDay = mktime(0,0,0,$kw,1,date('Y'));
			$days = cal_days_in_month(CAL_GREGORIAN, $kw, date('Y'));
			$month_ger = array('Januar','Februar','März', 'April','Mai','Juni', 'Juli','August','September', 'Oktober','November','Dezember');
			$title = "SoliBestellungen " . $month_ger[$kw-1];
			$kw = $month_ger[$kw-1] ;
		}
		
		$secs_per_day = 86400;
		$number = 0;
		$sum_pricediff = 0.0;

		$solis = $userManager->getAllSoli();
		
		foreach ($solis as $soli){
			$pricediff = 0.00;
			$SoliOrders[$number]['soli'] = $soli;
			$orders = array();
			for($i = 0; $i < $days; $i++) {
				$buffer = array();
				try {
					$buffer = $this->soliOrderManager->
						getOrdersByUserandMealdate($soli['ID'], date('Y-m-d',
							$firstDay + ($i * $secs_per_day)));
				} catch (MySQLVoidDataException $e) {
					//	$this->soliInterface->showMsg($this->msg['ERR_ORDERS_NOT_FOUND']);
				}
				foreach ($buffer as &$order)
					$orders[] = $order;
			}
			foreach ($orders as &$order) {
				$pricediff += $order['mealprice'] - $order['soliprice'];
			}
			$sum_pricediff += $pricediff;
			$SoliOrders[$number]['orders'] = $orders;
			$SoliOrders[$number]['pricediff'] = $pricediff;
			$number++;
		}
		$smarty->assign('SoliOrders', $SoliOrders);
		$smarty->assign('weekMode', $weekMode);
		$smarty->assign('ordering_date', $kw);
		$smarty->assign('sum', $sum_pricediff);
		$pdf_content = $smarty->fetch(PATH_SMARTY_TPL.'/pdf/orders_of_soliuser_per_week.pdf.tpl');
		$pdf = new GeneralPdf($pdo);
		$pdf->create($title, $pdf_content);
		$pdf->output();
	}

	/**
	 * Object of class AdminSoliInterface
	 * @var AdminSoliInterface
	 */
	protected $soliInterface;

	protected $soliCouponManager;

	protected $soliOrderManager;

	/**
	 * $msg contains messages shown to the User
	 * @var array($string)
	 */
	protected $msg = array();
}

?>