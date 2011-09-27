<?php
/**
 * Include necessary files
 */
require_once("inc/init.php");

ob_start();  // intercept all output

$autocomplete = clsAutocompleteCommonName::Load();

$methodName = (isset($_GET['field'])) ? $_GET['field'] : "";


if(method_exists($autocomplete, $methodName) && isset($_GET['term'])) {
	$value=AjaxParseValue($_GET['term']);
	
	/*if(isset($_GET['searchparams'])){
		$a=explode(';',$_GET['searchparams']);
		foreach($a as $b){
			list($k,$v)=explode('=',$b);
			$value['params'][$k]=$v;
		}
	}*/
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