<?php

/**
 * This file is outdated, but still needed by parts of the program
 * They use "global $db" to get access to the database
 * @todo: Remove this file and replace the occurences with DBConnect.php and the Class DBConnect
 */

require_once dirname(__FILE__) . '/DBConnect.php';

// $dbObject = new DBConnect($host, $username, $password, $database);
$dbObject = new DBConnect();
$dbObject->initDatabaseFromXML();
$db = $dbObject->getDatabase();

?>
