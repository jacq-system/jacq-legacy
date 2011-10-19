<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/log_functions.php");
no_magic();

$config=array(
	'HERBARIMAGEURL'=>$_OPTIONS['HERBARIMAGEURL'] ,
);

echo 'var config='.json_encode($config).';';