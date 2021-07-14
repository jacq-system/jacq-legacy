<?php
session_start();
require( '../inc/connect.php' );

$config = array(
	'HERBARIMAGEURL' => $_OPTIONS['HERBARIMAGEURL'],
);

echo 'var config = '.json_encode($config).';';
