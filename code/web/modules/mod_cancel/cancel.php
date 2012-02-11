<?php
    error_reporting(E_ALL);
    global $smarty;
    global $logger;
    
    $orderManager = new OrderManager('orders');
    $priceClassManager = new PriceClassManager();
    $userManager = new UserManager();
    
	try {
		$orderData = $orderManager->getEntryData($_GET['id'], 'MID');
		$mid = $orderData['MID'];
		$price = $priceClassManager->getPrice($_SESSION['uid'], $mid);
	} catch (Exception $e) {
		$logger->log('WEB|mod_cancel', 'MODERATE', sprintf('Error at ID %s, canceling MID %s: %s',
							$_GET['id'], $orderData['MID'], $e->getMessage()));
		die('<p class="error">Ein Fehler ist aufgetreten!</p>');
	}
	
	//"repay", add the price for the menu to the users account
	//double try-catch-block, works like if-else; delete Entry only if changeBalance did work
	try {
		if(!$userManager->changeBalance($_SESSION['uid'], $priceClassManager->getPrice($_SESSION['uid'], $mid))) {
			$smarty->display("web/modules/mod_cancel/failed.tpl");
			die();
		}
		try {
			$orderManager->delEntry($_GET['id']);
		} catch (Exception $e) {
			throw $e;
		}
	} catch (Exception $e) {
		$logger->log('WEB|mod_cancel', 'MODERATE', sprintf('Error at ID %s, canceling MID %s: %s',
							$_GET['id'], $orderData['MID'], $e->getMessage()));
		die('<p class="error">Ein Fehler ist aufgetreten!</p>');
	}
    
	$smarty->display("web/modules/mod_cancel/cancel.tpl");
?>