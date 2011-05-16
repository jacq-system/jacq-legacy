<?php
/**
 * xajax INIT Module
 *
 * This file prepares all the stuff for usage of xajax
 * @author Johannes Schachner <j.schachner@ddcs.at>
 * @since 14.07.2010
 */

if( !isset( $bxInitialized ) || !$bxInitialized ) {

/**
 * Require all necessary definitions
 */
require_once("xajax/xajax_core/xajax.inc.php");

/**
 * create the xajax object
 * @global xajax $xajaxObject
 */
$xajaxObject = new xajax();

// and register all functions
$xajaxObject->registerFunction("dispatch");
$xajaxObject->registerFunction("back");

$xajaxObject->setRequestURI("index_xajax.php");


$bxInitialized = true;
}