<?php
/**
 * INIT Module
 *
 * This file prepares all the stuff for usage (like Database etc.)
 * @author Wolfgang Koller <wkoller@senegate.at>
 * @since 19.03.2008
 */

if( !isset( $bInitialized ) || !$bInitialized ) {

/**
 * Require all necessary definitions
 */
require_once("variables.php");
require_once("tools.php");

/**
 * Start the session
 */
// session_name("herbardb");  TODO: use a session name systemwide
session_start();

/**
 * check if the user is logged in and send him to the login if not
 */
if (empty($_SESSION['username']) || empty($_SESSION['uid'])) {
    header("Location: login.php");
    exit();
}


$bInitialized = true;
}
