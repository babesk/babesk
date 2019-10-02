<?php

class AdminMealProcessing {
	public function __construct ($mealInterface, $dataContainer) {
		require_once PATH_ACCESS . '/MealManager.php';
		require_once PATH_ACCESS . '/OrderManager.php';

		$this->mealManager = new MealManager();
		$this->orderManager = new OrderManager();
		$this->mealInterface = $mealInterface;
		$this->_pdo = $dataContainer->getPdo();

		$this->msg = array(
			//create meal
			'err_inp_pc'			 => 'Die Preisklasse der Mahlzeit wurde nicht korrekt ausgefüllt',
			'err_inp'				 => 'Ein falscher Wert wurde im Feld "%s" eingegeben',
			'err_no_pc'				 => 'Es konnten keine Preisklassen gefunden werden',
			'fin_add_meal'			 => 'Mahlzeit wurde erfolgreich hinzugefügt',
			'field_pc'				 => 'Preisklassen',
			'field_name'			 => 'Name',
			'field_description'		 => 'Beschreibung',
			'field_max_orders'		 => 'Maximale Bestellungen',
			//show meals
			'err_no_meals'			 => 'Es konnten keine Mahlzeiten gefunden werden.',
			//edit infotexts
			'err_get_infotexts'		 => 'Ein Fehler ist beim Holen der Infotexte aufgetreten',
			'err_edit_infotexts'	 => 'Ein Fehler ist beim ändern der Infotexte aufgetreten',
			//ShowOrders
			'err_inp_date'			 => 'Es wurde ein unmögliches Datum eingegeben',
			'err_no_orders'			 => 'Es wurden keine Bestellungen für den angegebenen Zeitraum gefunden',
			'err_order_database'	 => 'Der Datenbankeintrag der Bestellung mit der ID "%s" enthält fehlerhafte Links!',
			'err_no_orders_at_date'	 => 'für den sind keine Bestellungen vorhanden',
			'order_not_fetched'		 => 'Bestellung <b>nicht</b> abgeholt',
			'order_fetched'			 => 'Bestellung abgeholt',
			//DeleteOldMealsAndOrders
			'err_conn_mysql'		 => 'Ein Problem ist beim verbinden mit dem MySQL-Server entstanden.',
			'fin_del_meals_orders'	 => 'Das löschen der alten Bestellungen und Mahlzeiten ist abgeschlossen',
			//DeleteMeal
			'err_del_order'			 =>
				'Ein Fehler ist beim löschen einer Bestellung aufgetreten. BestellungsID: "%s"<br>',
			'err_del_meal'			 => 'Ein Fehler ist beim löschen der Mahlzeit aufgetreten: ',
			'fin_del_meal'			 => 'Die Mahlzeit mit der ID "%s" wurde gelöscht.',
		);
	}

	/**
	 *Function to show the create-meal-form and, if it is filled out,
	 *it adds the meal in the MySQL-Server
	 */
	function CreateMeal () {
		//---INCLUDE---
		require_once PATH_ACCESS . '/PriceClassManager.php';

		//---INIT---
		$pcManager = new PriceClassManager();

		//---ROUTINES---
		//safety-checks
		if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['name'], $_POST['description'], $_POST['price_class'],
			$_POST['max_orders'])) {

			$name = $_POST['name'];
			$description = $_POST['description'];
			$price_class = $_POST['price_class'];
			$max_orders = $_POST['max_orders'];
			if(!empty($_POST['meal-date'])) {
				$date = new DateTime($_POST['meal-date']);
				$dateStr = $date->format('Y-m-d');
			}
			else {
				$this->mealInterface->dieError('Kein Datum angegeben.');
			}

			//$date_ar = array("day"	 => $_POST['Date_Day'], "month"	 => $_POST['Date_Month'], "year"	 => $_POST[
			//	'Date_Year']
			//);

			try {

				inputcheck($price_class, 'id', $this->msg['field_pc']);
				inputcheck($max_orders, 'id', $this->msg['field_max_orders']);
				inputcheck($name, '/\A^.{0,255}\z/', $this->msg['field_name']);
				if(strlen($description) > 1000) {
					throw new WrongInputException($description,
						$this->msg['field_description']);
				}
			} catch (WrongInputException $e) {
				$this->mealInterface->dieError(sprintf($this->msg['err_inp'], $e->getFieldName()));
			}

			//convert the date for MySQL-Server
			//$date_conv = $date_ar["year"] . "-" . $date_ar["month"] . "-" . $date_ar["day"];

			//check if weekday is saturday or sunday
			if ($date->format('N') > 5) {
				$this->mealInterface->dieError(
					gettext("Mealdate at weekend not valid!")
				);
			}

			//and add the meal
			try {

				$this->mealManager->addMeal(
					$name, $description, $dateStr, $price_class, $max_orders
				);
			} catch (Exception $e) {
				$this->mealInterface->dieError($this->msg['err_add_meal'] . $e->getMessage());
			}
			$this->mealInterface->backlink(
				'administrator|Babesk|Meals&action=1'
			);
			$this->mealInterface->dieMsg($this->msg['fin_add_meal']);
		}
		else {

			//if Formular isnt filled yet or the link was wrong
			try {
				$pc_arr = $pcManager->getAllPriceClassesPooled();
			} catch (Exception $e) {
				$this->mealInterface->dieError($this->msg['err_no_pc'] . $e->getMessage());
			}
			$pc_ids = array();
			$pc_names = array();
			foreach ($pc_arr as $pc) {
				$pc_ids[] = $pc['pc_ID'];
				$pc_names[] = $pc['name'];
			}
			$this->mealInterface->AddMeal($pc_ids, $pc_names);
		}
	}

	/**
	 *shows the meals
	 */
	function ShowMeals () {

		try {
			//$meals = $this->mealManager->getTableData();
			$meals = $this->_pdo->query(
				'SELECT m.*, pc.name AS priceClassName FROM BabeskMeals m
					INNER JOIN BabeskPriceClasses pc ON pc.pc_ID = m.price_class GROUP BY m.id
			')->fetchAll();
		} catch (MySQLVoidDataException $e) {
			$this->mealInterface->dieError($this->msg['err_no_meals']);
		}
		foreach ($meals as $key => $meal) {
			$meals[$key]['date'] = formatDate($meal['date']);
		}
		$this->mealInterface->ShowMeals($meals);
	}

	/**
	 * Edits the infotexts, which are placed below the Meallist
	 */
	function EditInfotext () {
		require_once PATH_ACCESS . '/GlobalSettingsManager.php';

		$gsManager = new GlobalSettingsManager();
		$infotexts = $gsManager->getInfoTexts();
		if (count($infotexts) != 2)
			$this->mealInterface->dieError($this->msg['err_get_infotexts']);

		if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['infotext1'], $_POST['infotext2'])) {

			$infotext1new = $_POST['infotext1'];
			$infotext2new = $_POST['infotext2'];
			if ($infotext1new == '')
				$infotext1new = '&nbsp;';
			if ($infotext2new == '')
				$infotext2new = '&nbsp;';
			try {
				$gsManager->setInfoTexts($infotext1new, $infotext2new);
			} catch (Exception $e) {
				$this->mealInterface->dieError($this->msg['err_edit_infotexts'] . $e->getMessage());
			}
			$this->mealInterface->FinEditInfotexts($infotext1new, $infotext2new);

		}
		else {
			$it1 = 'da';
			$it2 = 'bu';
			$it1 = $infotexts[0];
			$it2 = $infotexts[1];
			$this->mealInterface->EditInfotexts($it1, $it2);
		}
	}

	/**
	 * Allows the user to edit the Last Order time for meals saved in GlobalSettings-table
	 */
	function EditLastOrderTime () {

		require_once PATH_ACCESS . '/GlobalSettingsManager.php';

		$gsManager = new GlobalSettingsManager();
		$last_order_time = $gsManager->getLastOrderTime();

		if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['Time_Hour'], $_POST['Time_Minute'])) {

			$n_last_order_time = $_POST['Time_Hour'] . ':' . $_POST['Time_Minute'];

			try {
				$gsManager->setLastOrderTime($n_last_order_time);
			} catch (Exception $e) {
				$this->mealInterface->dieError($this->msg['err_edit_lot'] . $e->getMessage());
			}
			$this->mealInterface->dieMsg(sprintf($this->msg['fin_edit_lot'], $n_last_order_time));
		}
		else {

			$this->mealInterface->EditLastOrderTime($last_order_time);
		}
	}

	/**
	 *shows the orders in a table
	 */
	function ShowOrders () {
		require_once PATH_ACCESS . '/OrderManager.php';
		require_once PATH_ACCESS . '/MealManager.php';
		require_once PATH_ACCESS . '/UserManager.php';
		require_once PATH_ACCESS . '/GroupManager.php';
		require_once PATH_ACCESS . '/GlobalSettingsManager.php';

		//Show the Orders
		$user_manager = new UserManager();
		$groupManager = new GroupManager();

		$mysql_orders = array();
		$order = array();

		if(isset($_POST['date']) ) {


            $date = $_POST['date'];
            $date = strtotime($date);
            $date = date('Y-m-d', $date);
            try {
                $orders = $this->orderManager->getAllOrdersAt($date);

            } catch (MySQLVoidDataException $e) {
                $this->mealInterface->dieError($this->msg['err_no_orders']);
            } catch (MySQLConnectionException $e) {
                $this->mealInterface->dieError($e->getMessage());
            }

            if (!count($orders))
                $this->mealInterface->dieError($this->msg['err_no_orders']);

            foreach ($orders as & $order) {
                if (!count($meal_data = $this->mealManager->getEntryData($order['MID'], 'name')) or !count($user_data =
                        $user_manager->getEntryData($order['UID'], 'name', 'forename', 'GID'))) {
                    $this->mealInterface->dieError($this->msg['err_order_database']);
                } else {

                    $order['meal_name'] = $meal_data['name'];
                    $order['user_name'] = $user_data['forename'] . ' ' . $user_data['name'];

                    if (!$order['fetched'])
                        $order['is_fetched'] = $this->msg['order_not_fetched'];
                    else
                        $order['is_fetched'] = $this->msg['order_fetched'];
                }
            }

            //--------------------
            //Count all Orders
            $num_orders = array();
            $mealIdArray = $this->mealManager->GetMealIdsAtDate($date);
            $counter = 0;
            foreach ($mealIdArray as $mealIdEntry) {

                $groups = array(); //to show how many from different groups ordered something
                $sp_orders = $this->orderManager->getAllOrdersOfMealAtDate($mealIdEntry['MID'], $date);
                $num_orders[$counter]['MID'] = $mealIdEntry['MID'];
                $num_orders[$counter]['name'] = $this->mealManager->GetMealName(($mealIdEntry['MID']));
                $num_orders[$counter]['number'] = count($sp_orders);
                //--------------------
                //Get specific Usergroups for Interface
                foreach ($sp_orders as $sp_order) {

                    $user = $user_manager->getEntryData($sp_order['UID'], 'GID');
                    $sql_group = $groupManager->getEntryData($user['GID'], 'name');
                    $group_name = $sql_group['name'];
                    if (count($groups)) {
                        $is_new_group = true;
                        foreach ($groups as & $group) {
                            if (isset($group['name']) && $group['name'] == $group_name) {
                                $group['counter'] += 1;
                                $is_new_group = false;
                                continue;
                            }
                        }
                        if ($is_new_group) {
                            $group_arr = array('name' => $group_name, 'counter' => 1);
                            $groups[] = $group_arr;
                        }
                    } else {
                        //no group defined yet
                        $group_arr = array('name' => $group_name, 'counter' => 1);
                        $groups[] = $group_arr;
                    }
                }
                //--------------------

                $num_orders[$counter]['user_groups'] = $groups;
                $counter++;
            }


            /**
             * Sort the Orders
             */
            foreach ($orders as &$order) {
                $meals[$order['meal_name']][] = $order;
            }

            //sorting by usernames
            foreach ($meals as $meal) {
                foreach ($meal as $order) {
                    $temp[] = $order['user_name'];
                }
                sort($temp);

                foreach ($temp as $temp_name) {
                    foreach ($meal as & $order) {
                        if ($order['user_name'] == $temp_name) {
                            $sorted_orders[] = $order;
                            $order = NULL; //to avoid bugs with multiple orders from one user
                            break;
                        }
                    }
                }
            }

            /**
             * Show Orders
             */
            if (isset($num_orders[0]) && $counter) {
                if (isset($_POST['show']))
                    $this->mealInterface->ShowOrders($num_orders, $sorted_orders, formatDate($date));
				elseif (isset($_POST['pdf']))
                    $this->PrintOrders($num_orders, $sorted_orders, formatDate($date));
                else
                    $this->mealInterface->Menu();
            } else {
                $this->mealInterface->dieError(sprintf($this->msg['err_no_orders_at_date'], formatDateTime($date)));
            }
        }else{
            $today = array(
                'day'	 => date('d'),
                'month'	 => date('m'),
                'year'	 => date('Y'), );
			$this->mealInterface->ShowOrdersSelectDate($today);
		}
	}

	/**
	 * Deletes old Meals and Orders (Yesterday and before)
	 */
	public function DeleteOldMealsAndOrders () {

		if (isset($_POST['dialogConfirmed'])) {
			require_once PATH_ACCESS . '/MealManager.php';
			require_once PATH_ACCESS . '/OrderManager.php';

			$timestamp = time();
			$orderManager = new OrderManager();

			try {
				$this->mealManager->deleteMealsBeforeDate($timestamp);
				$orderManager->deleteOrdersBeforeDate($timestamp);
			} catch (MySQLConnectionException $e) {
				$this->mealInterface->dieError($this->msg['err_conn_mysql']);
			}
			catch (Exception $e) {
				$this->mealInterface->dieError($e->getMessage());
			}
			$this->mealInterface->dieMsg($this->msg['fin_del_meals_orders']);
		}
		else if (isset($_POST['dialogNotConfirmed'])) {
			$this->mealInterface->Menu();
		}
		else {
			$this->mealInterface->confirmationDialog(
				'Wollen sie die alten Mahlzeiten und Bestellungen wirklich löschen?', 'Babesk|Meals', '4',
				'Ja', 'Nein');
		}
	}

	/**
	 * deletes a meal
	 * This function deletes a meal. If linked_orders is set to true, it will also delete the
	 * orders linked to it.
	 * @param numeric_string $ID the ID of the meal to delete
	 * @param boolean $linked_orders If set to true, all orders linked to the meal to delete will also be deleted
	 */
	function DeleteMeal ($ID, $linked_orders) {

		try {
			$this->mealManager->delEntry($ID);
		} catch (Exception $e) {
			$this->mealInterface->dieError($this->msg['err_del_meal'] . $e->getMessage());
		}
		if ($linked_orders) {
			try {
				$orders = $this->orderManager->getAllOrdersOfMeal($ID);
			} catch (MySQLVoidDataException $e) {
				//no orders to delete, finished
				$this->mealInterface->dieMsg(sprintf($this->msg['fin_del_meal'], $ID));
			}
			foreach ($orders as $order) {
				try {
					$this->orderManager->delEntry($order['ID']);
				} catch (Exception $e) {
					$this->mealInterface->dieError(sprintf($this->msg['err_del_order'], $order['ID']) . $e->getMessage()
						);
				}
			}
		}
		$this->mealInterface->dieMsg(sprintf($this->msg['fin_del_meal'], $ID));
	}

	/**
	 * This function shows the createMeal-Form with the variables already filled out
	 */
	function DuplicateMeal ($name, $description, $pc_ID, $date, $max_orders) {

		require_once PATH_ACCESS . '/PriceClassManager.php';
		$pcManager = new PriceClassManager();
		try {
			$pc_arr = $pcManager->getAllPriceClassesPooled();
		} catch (Exception $e) {
			$this->mealInterface->dieError($this->msg['err_no_pc'] . $e->getMessage());
		}
		$pc_ids = array();
		$pc_names = array();
		foreach ($pc_arr as $pc) {
			$pc_ids[] = $pc['pc_ID'];
			$pc_names[] = $pc['name'];
		}
		$this->mealInterface->DuplicateMeal($pc_ids, $pc_names, $name, $description, $pc_ID, $max_orders, $date);
	}

    function PrintOrders($num_orders, $sorted_orders, $date) {
        require_once PATH_INCLUDE . '/pdf/GeneralPdf.php';
        $pdf_content = $this->mealInterface->ShowOrdersPDF($num_orders, $sorted_orders, $date);
        $title = "Bestellungen am ".$date;
        $pdf = new GeneralPdf($this->_pdo);
        $pdf->create($title, $pdf_content);
        $pdf->output();
    }

	/**
	 * Handles the MySQL-table meals
	 * @var MealManager
	 */
	protected $mealManager;

	/**
	 * Handles the MySQL-table orders
	 * @var OrderManager
	 */
	protected $orderManager;

	/**
	 * Messages shown to the user
	 * @var string[]
	 */
	protected $msg;

	/**
	 * Handles the Output shown to the User
	 * @var AdminMealInterface
	 */
	protected $mealInterface;

}
?>
