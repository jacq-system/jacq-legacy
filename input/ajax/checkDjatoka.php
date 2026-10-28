<?php
session_start();
require("../inc/gatekeeper.php");
require __DIR__ . '/../vendor/autoload.php';

use Jacq\DjatokaActions;
use Jacq\JacqServletRPCClient;
use Jacq\PdoAccess;

//$_POST=$_GET;
ob_start();  // intercept all output

$checkDjatoka = new DjatokaActions();
$methodName = (isset($_POST['method'])) ? $_POST['method'] : "";

$ret = array();
if (method_exists($checkDjatoka, $methodName)) {
    try {
        $params = $_POST['params'];
        $ret = array('res' => $checkDjatoka->$methodName($params));
        if (($a = $checkDjatoka->getInfo())) {
            $ret['info'] = $a;
        }
    } catch (Exception $e) {
        $ret = array(
            'error' => $e->getMessage() // . '<br />' . var_export($e->getTrace(), true)
        );
    }
} else {
    $ret = array('error' => "Method: '{$methodName}' doesn't exist.");
}

$ob = ob_get_clean();
if (strlen($ob) > 0) {
    $ret['ob'] = $ob;
}
echo json_encode($ret);
