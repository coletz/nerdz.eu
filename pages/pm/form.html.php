<?php
ob_start('ob_gzhandler');
require_once $_SERVER['DOCUMENT_ROOT'].'/class/autoload.php';
ob_start(array('NERDZ\\Core\\Utils','minifyHTML'));

$user = new NERDZ\Core\User();
$vals = [];

$user->getTPL()->assign($vals);
$user->getTPL()->draw('pm/form');

