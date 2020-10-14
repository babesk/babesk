<?php

require_once PATH_INCLUDE . '/Module.php';
require_once PATH_ADMIN . '/Schbas/Schbas.php';
require_once PATH_INCLUDE . '/Schbas/Barcode.php';

class BookInfo extends Schbas {

	/////////////////////////////////////////////////////////////////////
	//Constructor
	/////////////////////////////////////////////////////////////////////

	public function __construct($name, $display_name, $path) {
		parent::__construct($name, $display_name, $path);
	}

	/////////////////////////////////////////////////////////////////////
	//Methods
	/////////////////////////////////////////////////////////////////////

	public function execute($dataContainer) {

		$this->entryPoint($dataContainer);

		if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['barcode'])) {
			$this->bookinfoShow($_POST['barcode']);
		}
		else{
			$this->displayTpl('form.tpl');
		}
	}

	/////////////////////////////////////////////////////////////////////
	//Implements
	/////////////////////////////////////////////////////////////////////

	protected function entryPoint($dataContainer) {

		parent::entryPoint($dataContainer);
		$this->initSmartyVariables();
	}

	private function bookinfoShow($barcodeStr) {

		$invData = $this->invDataFetch($barcodeStr);
		if(!empty($invData)) {
			$this->bookinfoTemplateGenerate($invData);
		}
		else {
			$this->_interface->dieError('Dieses Buch ist nicht im System.');
		}
	}

	private function invDataFetch($barcodeStr) {

		try {
            $barcode = \Babesk\Schbas\Barcode::createByBarcodeString($barcodeStr);

            $inventory = $barcode->getMatchingBookExemplar($this->_pdo);

			return $inventory;

		} catch (Exception $e) {
			$this->_logger->log('Error fetching the bookinfo', 'Notice',
				Null, json_encode(array('barcode' => $barcode,
					'msg' => $e->getMessage(), 'type' => get_class($e))));
			$this->_interface->dieError('Fehler beim Abrufen des Buches.');
		}
	}

	private function bookinfoTemplateGenerate($exemplar) {

		$stmt = $this->_pdo->prepare("SELECT u.* FROM UserActiveClass u JOIN SchbasLending l ON (u.ID = l.user_id) WHERE inventory_id = ?");
		$stmt->execute(array($exemplar['id']));
		$user = $stmt->fetch();

		if(!user)
			$activeGrade = "---";
		else
			$activeGrade = $user['gradelevel'].$user['label'];
		$this->_smarty->assign('activeGrade', $activeGrade);
		$this->_smarty->assign('user', $user);
		$this->_smarty->assign('book', $exemplar);
		$this->displayTpl('result.tpl');
	}
}

?>
