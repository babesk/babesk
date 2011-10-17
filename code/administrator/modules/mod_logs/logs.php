<?php

    //no direct access
    defined('_AEXEC') or die("Access denied");

	require_once PATH_INCLUDE."/logs.php";
	require_once 'logs_constants.php';
	
	global $smarty;
	global $logger;
	
	$logs = $logger->getLogData();
	if(!count($logs)) {
		die(NO_LOGS);
	}
	//the different actions the module can do
	$_showLogs = 'show';
	$_delLogs  = 'delete';
	
	
    if(isset($_GET['action'])) {
        if($_GET['action'] == $_showLogs) {
            if ('POST' == $_SERVER['REQUEST_METHOD']) {
        	    if (!isset($_POST['Category'], $_POST['Severity'])) {
        		   die(EMPTY_FORM);
        	    }
        	    $category = trim($_POST['Category']);
                $severity = trim($_POST['Severity']);

                $logger->printLogs($category, $severity);                      
        
            }
            else {
            	$logs = $logger->getLogData();
            	$severitys = array();
            	$categories = array();
            	$is_existing = false;
            	//add severitys and categories to display them with smarty
            	/*
            	 * This method has the advantage that unused severities or categories
            	 * cannot be choosen by the user
            	 */
            	foreach($logs as $log) {
            		foreach($severitys as $severity) {
            			if($severity == $log['severity'])
            				$is_existing = true;
            		}
            		if(!$is_existing)
            			$severitys[] = $log['severity'];
            		$is_existing = false;
            		foreach($categories as $category) {
            			if($category == $log['category'])
            				$is_existing = true;
            		}
            		if(!$is_existing)
            			$categories [] = $log['category'];
            	}
            	$smarty->assign('categories', $categories);
            	$smarty->assign('severity_levels', $severitys);
                $smarty->display('administrator/modules/mod_logs/showLogs.tpl');
            }
        }
        if($_GET['action'] == $_delLogs) {
            $logger->clearLogs();        
        }
    }
	else {
        $smarty->display('administrator/modules/mod_logs/logs.tpl');
    }

?>