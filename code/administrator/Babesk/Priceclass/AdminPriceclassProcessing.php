<?php

class AdminPriceclassProcessing {
	public function __construct ($pcInterface) {

		require_once PATH_ACCESS . '/PriceClassManager.php';

		$this->pcManager = new PriceClassManager();
		$this->pcInterface = $pcInterface;

		$this->msg = array(
			//new Priceclass
			'err_inp_nprice'				 => 'Das Standardpreis-Feld wurde nicht korrekt ausgefüllt',
			'err_inp_price'					 => 'In dem Feld "%s" wurde ein falscher Preis eingegeben',
			'err_add_priceclass_for_group'	 =>
				'Ein Fehler ist beim hinzufügen der Preisklasse für Gruppe "%s" aufgetreten.',
			'fin_add_priceclass'			 => 'Die Preisklasse wurde erfolgreich hinzugefügt.',
			'err_fetch_groups'				 => 'Ein Fehler ist beim Abrufen der Gruppendaten aufgetreten.',
			//DeletePriceclass
			'err_del_priceclass'			 => 'Ein Fehler ist beim löschen der Preisklasse aufgetreten.',
			'fin_del_priceclass'			 => 'Die Preisklasse wurde erfolgreich gelöscht.',
			//ChangePriceclass
			'err_inp_id'					 => 'Die ID wurde nicht richtig eingegeben.',
			'err_get'						 => 'Ein Fehler ist beim auswerten von GET-Variablen aufgetreten!',
			'err_change_priceclass'			 => 'Ein Fehler ist beim Verändern der Preisklasse aufgetreten.',
			'fin_change_priceclass'			 => 'Die Preisklasse wurde erfolgreich verändert.',
			//ShowPriceclasses
			'err'							 => 'Fehler',
			'err_fetch_priceclass'			 => 'Ein Fehler ist beim Abrufen der Preisklassen aufgetreten.'
		);
	}

	/**
	 * Creates a new priceclassgroup
	 * This function creates a new priceclass-group by showing the user an interface to add
	 * priceclasses and evaluating it.
	 */
	function NewPriceclass () {

		require_once PATH_ACCESS . '/GroupManager.php';

		$groupManager = new GroupManager();

		try {
			$priceclasses = $this->pcManager->getTableData();
		} catch (MySQLVoidDataException $e) {
			$priceclasses = false;
		}
		catch (Exception $e) {
			$this->pcInterface->dieError('Error while getting PriceclassData:' . $e->getMessage());
		}

		$highest_pc_ID = $this->pcManager->getHighestPriceclassID();

		if (isset($_POST['name'], $_POST['n_price'], $_POST['n_orders'])) {
			try {
				$groups = $groupManager->getTableData();
			} catch (Exception $e) {
				$this->pcInterface->dieError($this->msg['err_fetch_groups'] . $e->getMessage());
			}
			$pc_name = $_POST['name'];
			$normal_price = $_POST['n_price'];
            $normal_orders = $_POST['n_orders'];
			if (!preg_match('/\A^[0-9]{1,2}((,|\.)[0-9]{2})?\z/', $normal_price)) {
				$this->pcInterface->dieError($this->msg['err_inp_nprice']);
			}
			foreach ($groups as $group) {
				$price = $_POST['group_price' . $group['ID']];
				if (!$price || trim($price) == '') {
					$price = $normal_price;
				}
				else if (!preg_match('/\A^[0-9]{0,2}((,|\.)[0-9]{2})?\z/', $price)) {
					$this->pcInterface->dieError($this->msg['err_input_price']);
				}
                $orders = $_POST['group_orders' . $group['ID']];
                if (!$orders || trim($orders) == '') {
                    $orders = $normal_orders;
                }
				$price = str_replace(',', '.', $price); //Comma bad for MySQL
				try { //add the group
					$this->pcManager->addPriceClass($pc_name, $group['ID'], $price, $highest_pc_ID + 1, $orders);
				} catch (Exception $e) {
					$this->pcInterface->dieError(sprintf($this->msg['err_add_priceclass_for_group'] . $e->getMessage()),
						$group['name']);
				}
			}
			$this->pcInterface->dieMsg($this->msg['fin_add_priceclass']);
		}
		else {

			try {
				$groups = $groupManager->getTableData();
			} catch (Exception $e) {
				$this->pcInterface->dieError($this->msg['err_fetch_groups'] . $e->getMessage());
			}
			$this->pcInterface->NewPriceclass($groups);
		}
	}

	/**
	 * Deletes a Priceclass
	 * @param string $priceclass_id The ID of the Priceclass to delete
	 */
	function DeletePriceclass ($priceclass_id) {

		try {
			$this->pcManager->delEntry($priceclass_id);
		} catch (Exception $e) {
			$this->pcInterface->dieError($this->msg['err_del_priceclass']);
		}
		$this->pcInterface->dieMsg($this->msg['fin_del_priceclass']);
	}

	/**
	 * Changes a Priceclass
	 * @param string $priceclass_id The ID of the Priceclass to change
	 */
	function ChangePriceclass ($priceclass_id) {

		if (isset($_GET['where'], $_POST['ID'], $_POST['price'], $_POST['orders'])) {

			$pc_old_ID = $_GET['where'];
			$pc_price = $_POST['price'];
			$max_orders = $_POST['orders'];

			$pc_price = str_replace(',', '.', $pc_price);

			if (!preg_match('/\A^[0-9]{0,2}((,|\.)[0-9]{2})?\z/', $pc_price))
				$this->pcInterface->dieError($this->msg['err_inp_price']);
			else if (!preg_match('/\A^[0-9]{1,5}\z/', $pc_old_ID))
				$this->pcInterface->dieError($this->msg['err_get']);

			try {
				$this->pcManager->changePriceClass($pc_old_ID, $pc_price, $max_orders);
			} catch (Exception $e) {
				$this->pcInterface->dieError($this->msg['err_change_priceclass'] . $e->getMessage());
			}
			$this->pcInterface->dieMsg($this->msg['fin_change_priceclass']);
		}
		else {

			require_once PATH_ACCESS . '/GroupManager.php';

			$groupManager = new GroupManager('groups');

			try {
				$priceclass = $this->pcManager->getEntryData($priceclass_id, '*');
				$current_group_name = $groupManager->getEntryData($priceclass['GID'], 'name');
				$groups = $groupManager->getTableData();
			} catch (Exception $e) {

			}

			foreach ($groups as & $group) {
				if ($group['ID'] == $priceclass['GID']) {
					$group['default'] = 'selected';
				}
				else {
					$group['default'] = '';
				}
			}
			$this->pcInterface->ChangePriceclass($priceclass, $groups, $current_group_name['name']);
		}
	}

	/**
	 * Shows the Priceclasses
	 */
	function ShowPriceclasses () {
		require_once PATH_ACCESS . '/GroupManager.php';

		try {
			$groupManager = new GroupManager('groups');
		} catch (Exception $e) {
			$this->pcInterface->dieError($this->msg['err_fetch_groups'] . $e->getMessage());
		}

		try {
			$priceclasses = $this->pcManager->getTableData();
		} catch (Exception $e) {
			$this->pcInterface->dieError($this->msg['err_fetch_priceclass'] . $e->getMessage());
		}

		foreach ($priceclasses as & $priceclass) {
			try {
				$group = $groupManager->getEntryData($priceclass['GID'], 'name');
			} catch (MySQLVoidDataException $e) {
				$priceclass['group_name'] = $this->msg['err'];
			}
			catch (Exception $e) {
				$this->pcInterface->dieError($this->msg['err_fetch_groups'] . $e->getMessage());
			}
			if (!$group) {
				$priceclass['group_name'] = $this->msg['err'];
			}
			else {
				$priceclass['group_name'] = $group['name'];
			}
		}
		$this->pcInterface->ShowPriceclasses($priceclasses);
	}

	protected $pcManager;

	protected $pcInterface;

	protected $msg;
}

?>
