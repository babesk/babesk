<?php
require_once 'Smarty/Autoloader.php';

Smarty_Autoloader::register();
$smarty = new Smarty();

$smarty->setTemplateDir(dirname(__FILE__) . '../../../smarty_templates');
$smarty->setCompileDir(dirname(__FILE__) . '/templates_c');
$smarty->setCacheDir(dirname(__FILE__) . '/cache');
$smarty->setConfigDir(dirname(__FILE__) . '/config');

$smarty->error_reporting = E_ALL & ~E_NOTICE;

?>