<?php
session_start();
require("inc/gatekeeper.php");
require_once("inc/variables.php");
require_once("inc/tools.php");

ob_start();  // intercept all output

$autocomplete = clsAutocomplete::Load();

$methodName = (isset($_GET['field'])) ? $_GET['field'] : "";



if(method_exists($autocomplete, $methodName) && isset($_GET['term'])) {

	$value=AjaxParseValue($_GET['term']);
	$data =$autocomplete->$methodName($value);

}else{
	$data  = '';
}

$errors = ob_get_clean();

if($errors){
	$data = array(
		array(
			'id' => 0,
			'label' => $errors,
			'value' => $errors
		)
	);
}

print json_encode($data);
