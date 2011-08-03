<?php
/**
 * Include necessary files
 */
require("inc/init.php");

ob_start();  // intercept all output

$autocomplete = clsAutocompleteCommonName::Load();

$methodName = (isset($_GET['field'])) ? $_GET['field'] : "";

if (method_exists($autocomplete, $methodName) &&  isset($_GET['q'])  ) {
    $data = $autocomplete->$methodName($_GET['q']);
} else {
    $data  = '';
}

$errors = ob_get_clean();

if ($errors) {
    $data = array(array('id'    => 0,
                        'label' => $errors,
                        'value' => $errors));
}
if(is_array($data) && count($data)>0){
	foreach($data as $r){
		$r['value']=str_replace('|','-',$r['value']);
		echo "{$r['label']}|{$r['value']}|{$r['id']}\n";
	}
}
