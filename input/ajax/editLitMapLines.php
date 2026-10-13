<?php
session_start();
require_once('../inc/connect.php');
require __DIR__ . '/../vendor/autoload.php';

use Jacq\MapLines_editLit;

//debug
foreach($_GET as $k=>$v){
    $params[$k]=$v;
}

$mapLines = new MapLines_editLit();
$mapLines->execFunction($_POST['function'], $_POST);
