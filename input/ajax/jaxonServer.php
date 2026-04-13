<?php
session_start();
require(__DIR__ . '/../inc/connect.php');
require __DIR__ . '/../vendor/autoload.php';

use Jaxon\Jaxon;

$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_DIR, __DIR__ . '/../classes/Jaxon', ['namespace' => 'Jacq\Jaxon', 'autoload' => true]);
if ($jaxon->canProcessRequest()) {
    $jaxon->processRequest();
}
