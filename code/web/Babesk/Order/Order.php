<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_WEB . '/Babesk/Babesk.php';

class Order extends Babesk {

	////////////////////////////////////////////////////////////////////////
	//Constructor
	////////////////////////////////////////////////////////////////////////

	/**
	 * Constructs the Object
	 */
	public function __construct($name, $display_name, $path) {

		parent::__construct($name, $display_name, $path);
		$this->modulePath = $path;
		$this->smartyPath = PATH_SMARTY_TPL . '/web' . $path;
		require_once PATH_CODE . '/web/WebInterface.php';
	}

	////////////////////////////////////////////////////////////////////////
	//Methods
	////////////////////////////////////////////////////////////////////////

	/**
	 * Executes the Object
	 */
	public function execute($dataContainer) {

		//No direct access
		defined('_WEXEC') or die("Access denied");

		$smarty = $dataContainer->getSmarty();
		$this->_interface = $dataContainer->getInterface();

		$this->entryPoint($dataContainer);

		if (isset($_GET['order'])) {
			$this->mealOrderEntry();
		}
		else {
			require_once 'MealsForOrderDisplayer.php';

			$displayer = new MealsForOrderDisplayer();
			try {
				$displayer->display($dataContainer, $this->smartyPath);

			} catch (Exception $e) {
				$this->_interface->dieError('Ein Fehler ist beim abrufen der Mahlzeiten aufgetreten! <br />' . $e->getMessage());
			}
		}
	}

	////////////////////////////////////////////////////////////////////////
	//Implements
	////////////////////////////////////////////////////////////////////////

	protected function entryPoint($dataContainer) {

		Module::entryPoint($dataContainer);
		parent::moduleTemplatePathSet();
	}

	/**
	 * Creates the Meallist containing Mealweeks containing meals
	 *
	 * @param  Array $meals The Meals fetched from the Database
	 * @return Array Multiple Mealweeks, each containing its Meals
	 */
	protected function meallistCreate($meals) {

		foreach ($meals as &$meal) {
			$meal_day = date('N', strtotime($meal['date']));
			$meal_weeknum = date('W', strtotime($meal['date']));
			if($this->userHasValidCoupon($_SESSION['uid'])) {
				$meal['price'] = $this->soliPriceGet();
			}
			$meallist[$meal_weeknum][$meal_day][] = $meal;
			//The date of the beginning of the week. +7 because of negative meal_day setting the date 1 week behind
			for($i = 1; $i <= 7; $i++) {
				$meallist[$meal_weeknum]['date'][$i] = date(
					'd.m.Y',
					strtotime(sprintf('+%s day', -$meal_day + $i),
					strtotime($meal['date']))
				);
			}
		}

		return $meallist;
	}

	/**
	 * The Entrypoint to ordering a meal, checks if user should confirm first
	 */
	protected function mealOrderEntry() {

		if ('POST' == $_SERVER['REQUEST_METHOD']) {
			if(isset($_GET['confirmed'])) {
				$this->mealOrder();
			}
			else {
				$this->mealOrderConfirm();
			}
		}
	}

	/**
	 * Displays a confirm-dialog for the User to confirm the Meal-Order
	 */
	protected function mealOrderConfirm() {

		TableMng::sqlEscape($_GET['order']);
		$mealId = $_GET['order'];
		$meal = $this->mealGet($mealId, $_SESSION['uid']);
		$date = formatDate($meal['date']);
		$this->_smarty->assign('date', $date);
		$this->_smarty->assign('meal', $meal['name']);
		$this->_smarty->assign('orderId', $_GET['order']);
		$this->displayTpl('confirm.tpl');
		//$this->_interface->dieMessage("Am $date das Menü $meal[name] bestellen?<br /><form method='POST' action='index.php?section=Babesk|Order&order={$_GET['order']}&confirmed' ><input type='submit' value='Bestellen' /></form>");
	}

	/**
	 * Handles the Ordering of the Meal
	 *
	 * Dies displaying an Error when something has gone wrong
	 */
	protected function mealOrder() {

		try {
			$this->_interface->setBacklink(
				'index.php?module=web|Babesk|Order'
			);
			$this->mealOrderValuesInit();

			if($this->mealorderAllowedCheck()) {
				TableMng::getDb()->autocommit(false);   // Deppenschutz
				$this->mealPay();
				$this->orderToDb($_SESSION['uid'], $_SERVER['REMOTE_ADDR']);
				TableMng::getDb()->autocommit(true);
			}
			else {
				$this->_interface->dieError('Es ist zu spät, diese Mahlzeit zu bestellen oder die maximale Anzahl an Bestellungen pro Tag wurde erreicht');
			}

		} catch (Exception $e) {
			$this->_interface->dieError(ERR_ORDER . ' ' . $e->getMessage());
			die();
		}

		$this->orderSuccess();
	}

	/**
	 * Escapes and transfers the Request-Data
	 */
	protected function mealOrderValuesInit() {

		TableMng::sqlEscape($_GET['order']);
		$this->_meal = $this->mealGet(
			$_GET['order'],
			$_SESSION['uid']);
		$this->_hasValidCoupon = $this->userHasValidCoupon(
			$this->_meal['ID']);
	}

	/**
	 * Checks if ordering of the Meal is allowed
	 *
	 * @return boolean True if it is allowed, false if it is not
	 */
	protected function mealorderAllowedCheck() {

		$enddate = $this->orderEnddateGet();
		$orderEnd = strtotime($enddate, strtotime($this->_meal['date']));

		if($this->userAlreadyHasOrderedWithPriceclassAtDateCheck()) {
			$this->_interface->dieMessage(
				'Sie haben bereits die maximale Anzahl an Bestellungen für dieses Gericht erreicht.'
			);
		}

		if($this->maxOrdersOfMealCheck()){
            $this->_interface->dieMessage(
                'Es sind keine weiteren Bestellungen für dieses Gericht möglich.'
            );
		}

		return (time() <= $orderEnd);
	}

	/**
	 * Fetches the orderEnddate from the Database
	 *
	 * @return String The date-modifier usable in strtotime
	 */
	protected function orderEnddateGet() {

		try {
			$data = TableMng::query('SELECT * FROM SystemGlobalSettings
				WHERE name = "orderEnddate"');
			if(!isset($data[0]['value'])) {
				throw new Exception('Could not fetch OrderEnddate');
			}
		} catch (Exception $e) {
			throw new Exception('Could not fetch OrderEnddate');
		}

		return $data[0]['value'];
	}

	/**
	 * Changes the Balance of the User to Pay for the Meal
	 *
	 * Dies displaying an Error when user has not enough money
	 */
	protected function mealPay() {

		$payment = $this->paymentGet();
		if($this->userBalanceChange($_SESSION['uid'], -$payment)) {
			return true;
		}
		else {
			$this->_interface->showMessage("Du hast zu wenig Geld! Verlangt werden {$payment}€");
			$this->_interface->dieContent($this->smartyPath . 'failed.tpl');
			die();
		}
	}

	/**
	 * Uploads the Meal-Order to the Database
	 *
	 * @param  int $userId The Id of the User that ordered the Meal
	 * @param  string $ip The IP of the User
	 */
	protected function orderToDb($userId, $ip) {

		$meal = $this->_meal;
		$ordertime = date("Y-m-d h:i:s");
		$soliPrice = $this->soliPriceGet();

		TableMng::query("INSERT INTO BabeskOrders
			(MID, UID, date, IP, ordertime, fetched) VALUES
			('$meal[ID]', '$userId', '$meal[date]', '$ip', '$ordertime', 0)");

		$lastInsertId = TableMng::getDb()->insert_id;

		if($this->_hasValidCoupon) {
			TableMng::query("INSERT INTO BabeskSoliOrders (ID, UID, date, IP,
				ordertime, fetched, mealname, mealprice, mealdate, soliprice)
				VALUES ('$lastInsertId', '$userId', '$meal[date]', '$ip',
					'$ordertime', '0', '$meal[name]', '$meal[price]',
					'$meal[date]', '$soliPrice')");
		}
	}

	/**
	 * Fetches the meal from the Database
	 *
	 * @param  int $mealId The ID of the Meal
	 * @param  int $userId The ID of the User
	 * @return Array The meal-data on success, else false
	 */
	protected function mealGet($mealId, $userId) {

		try {
			$meal = TableMng::query("SELECT pc.price AS price, pc.soliPrice AS soliPrice, u.GID AS groupID, m.*
				FROM BabeskMeals m
				JOIN SystemUsers u ON u.ID = $userId
				JOIN BabeskPriceClasses pc
					ON pc.GID = u.GID AND pc.pc_ID = m.price_class
				WHERE m.ID = '$mealId';");

		} catch (Exception $e) {
			$this->_interface->dieError(
				'Konnte die Mahlzeit nicht abrufen');
		}

		if(count($meal)) {
			return $meal[0];
		}
		else {
			return false;
		}
	}

	/**
	 * Returns the Amount of money the User needs to Pay for the Meal
	 *
	 * @return int The Amount of money
	 */
	protected function paymentGet() {

		$solipriceEnabled = $this->isSolipriceEnabledGet();
		if(!$this->_hasValidCoupon || !$solipriceEnabled) {
			return $this->_meal['price'];
		}else {
			return $this->soliPriceGet();
		}
	}

	/**
	 * Fetches if the soliprice is enabled
	 *
	 * Dies displaying a Message on Error
	 */
	protected function isSolipriceEnabledGet() {

		try {
			$stmt = $this->_pdo->query("SELECT `value` FROM `SystemGlobalSettings`
				WHERE `name` = 'solipriceEnabled'");

			return $stmt->fetchColumn() == '1';

		} catch (PDOException $e) {
			$this->_interface->dieError(
				_g('Could not check if the Soliprice is enabled!'));
		}
	}

	/**
	 * Checks if the User has a valid Solicoupon for the given Meal
	 *
	 * @param  int $mealId The ID of the Meal
	 * @return boolean True if the User has a valid Coupon, else false
	 */
	protected function userHasValidCoupon($mealId) {

		$hasCoupon = TableMng::query("SELECT COUNT(*) AS count
			FROM BabeskSoliCoupons sc
			JOIN BabeskMeals m ON m.ID = $mealId
			JOIN SystemUsers u ON u.ID = sc.UID
			WHERE m.date BETWEEN sc.startdate AND sc.enddate AND
				sc.UID = $_SESSION[uid] AND u.soli = 1");

		return $hasCoupon[0]['count'] != '0';
	}

	/**
	 * Fetches the Mealprice for Soli-Users and returns it
	 *
	 * @throws Exception If Soliprice could not be fetched
	 * @return string The Price of the Meal for Soli-Users
	 */
	protected function soliPriceGet() {

		$seperatePricesEnabled = TableMng::query('SELECT * FROM SystemGlobalSettings
			WHERE name = "seperateSoliPrices"');
		if($seperatePricesEnabled[0]['value'] == "1"){
			$pc_ID = $this->_meal['price_class'];
			$soliPrice = TableMng::query("SELECT soliPrice FROM BabeskPriceClasses WHERE pc_ID = $pc_ID GROUP BY pc_ID");
			if(count($soliPrice)) {
				return $soliPrice[0]['soliPrice'];
			}
			else {
				throw new Exception('Soli-Price not set, but Coupon used!');
			}
		}else{
			$soliPrice = TableMng::query('SELECT * FROM SystemGlobalSettings
				WHERE name = "soli_price"');
			if(count($soliPrice)) {
				return $soliPrice[0]['value'];
			}
			else {
				throw new Exception('Soli-Price not set, but Coupon used!');
			}
		}
	}

	/**
	 * Changes the Users Credits
	 *
	 * @param  int $userId The ID of the User
	 * @param  float $amount The Amount of the Credits to change
	 * (negative means that credits will be subtracted from the Users Konto)
	 * @return boolean True if success, when user has not enough money, false
	 */
	protected function userBalanceChange($userId, $amount) {

		if(($newBalance = $this->userBalanceChangeCheck($userId, $amount)) !==
				false) {
			$balanceStr = str_replace(',', '.', (string) $newBalance);
			TableMng::query("UPDATE SystemUsers SET credit = '$balanceStr'
				WHERE ID = $userId");
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Checks wether the User has enough money th pay for the Order
	 *
	 * @param  int $userId The ID of the User
	 * @param  float $amount The Amount (for paying it should be negative)
	 * @return int The new Amount of Credits the user has, or false if User has not enough Money
	 */
	protected function userBalanceChangeCheck($userId, $amount) {

		$data = TableMng::query("SELECT * FROM SystemUsers u
			WHERE u.ID = $userId");

		if(!empty($data[0]['credit'])) {
			$res = $data[0]['credit'] + $amount;
			if($res >= 0) {
				return $res;
			}
			else {
				return false;
			}
		}
		else {
			throw new Exception('Could net check the new Balance of User!');
		}
	}

	/**
	 * Displays a success-Information to the User
	 */
	protected function orderSuccess() {

		$date = formatDate($this->_meal['date']);
		$meal = $this->_meal;
		$this->_interface->dieMessage("Das Menü $meal[name] für den " .
			"$date erfolgreich bestellt. <a href='index.php'>Weiter</a>");
	}

	/**
	 * Fetches all Meals that are between the two dates
	 *
	 * @param  string $startdate The startdate to search for teh meals
	 * @param  string $enddate   The enddate to search for the meals
	 * @return Array The fetched Meals
	 */
	protected function mealsAllGetBetween($startdate, $enddate) {

		try {
			$meals = TableMng::query("SELECT m.*, pc.price AS price
				FROM BabeskMeals m
				JOIN SystemUsers u ON u.ID = $_SESSION[uid]
				JOIN BabeskPriceClasses pc
					ON m.price_class = pc.pc_ID AND pc.GID = u.GID
				WHERE date BETWEEN '$startdate' AND '$enddate'
					ORDER BY date, price_class");

		} catch (Exception $e) {
			throw new Exception('Konnte die Mahlzeiten nicht abrufen!', 0, $e);
		}

		return $meals;
	}

	/**
	 * Fetches and returns the Allowed maximum of Orders per day and User
	 *
	 * @return string The Maximum of Orders or false if setting not found
	 */
	protected function maxCountOfOrdersPerDayPerUserGet() {

		$data = TableMng::querySingleEntry(
			'SELECT * FROM SystemGlobalSettings
				WHERE name = "maxCountOfOrdersPerDayPerUser"');

		if(!count($data)) {
			return false;
		}
		else {
			return $data['value'];
		}
	}

	/**
	 * Returns the Count of Meals the User has ordered at that date
	 *
	 * @param  int    $userId The ID of the User
	 * @param  string $date   The Date of the User, format DD-MM-YYYY
	 * @return string         The Count of Orders
	 */
	protected function orderCountOfDayByUserGet($userId, $date) {

		$row = TableMng::querySingleEntry(
			"SELECT COUNT(*) AS count FROM BabeskOrders o
			JOIN BabeskMeals m ON m.ID = o.MID
			WHERE o.UID = '$userId' AND m.date = '$date'");

		return $row['count'];
	}

	/**
	 * Check if the user already ordered a meal with same priceclass and date
	 * @return true if he did, false if not
	 */
	protected function userAlreadyHasOrderedWithPriceclassAtDateCheck() {

		$stmt = $this->_pdo->prepare(
			'SELECT COUNT(*) FROM BabeskOrders o
				INNER JOIN BabeskMeals m ON m.ID = o.MID
				INNER JOIN SystemUsers u ON u.ID = :userId
				INNER JOIN BabeskPriceClasses pc
					ON m.price_class = pc.pc_ID AND pc.GID = u.GID
				WHERE m.date = :mealDate
					AND m.price_class = :priceClass
					AND o.UID = u.ID
		');
		$stmt->execute(array(
			'userId' => $_SESSION['uid'],
			'mealDate' => $this->_meal['date'],
			'priceClass' => $this->_meal['price_class']
		));
		$count = $stmt->fetch(PDO::FETCH_COLUMN);

        $stmt_orders = $this->_pdo->prepare(
            'SELECT orders_per_user FROM BabeskPriceClasses pc
				WHERE pc_ID = :priceClass
					AND GID = :groupID
		');
        $stmt_orders->execute(array(
            'priceClass' => $this->_meal['price_class'],
			'groupID' => $this->_meal['groupID']
        ));
        $max_orders = $stmt_orders->fetch(PDO::FETCH_COLUMN);
		return $count >= $max_orders;
	}

	protected function maxOrdersOfMealCheck(){
        $stmt = $this->_pdo->prepare(
            'SELECT COUNT(*) FROM BabeskOrders o
				INNER JOIN BabeskMeals m ON m.ID = o.MID
				WHERE m.ID = :mealID
		');
        $stmt->execute(array(
            'mealID' => $this->_meal['ID']
        ));
        $count = $stmt->fetch(PDO::FETCH_COLUMN);
        $stmt_orders = $this->_pdo->prepare(
            'SELECT max_orders FROM BabeskMeals m
				WHERE ID = :mealID
		');
        $stmt_orders->execute(array(
            'mealID' => $this->_meal['ID']
        ));
        $max_orders = $stmt_orders->fetch(PDO::FETCH_COLUMN);
        return $count >= $max_orders;
	}

	////////////////////////////////////////////////////////////////////////
	//Attributes
	////////////////////////////////////////////////////////////////////////

	private $smartyPath;
	private $modulePath;
	protected $_interface;

	private $_hasValidCoupon;
	private $_meal;
}
?>
