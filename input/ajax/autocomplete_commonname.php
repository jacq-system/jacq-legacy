<?php
/**
 * Include necessary files
 */
require_once("inc/init.php");

ob_start();  // intercept all output

$autocomplete = clsAutocompleteCommonName::Load();

$methodName = (isset($_GET['field'])) ? $_GET['field'] : "";

if (method_exists($autocomplete, $methodName) && isset($_GET['term'])) {

	if($id=extractID2($_GET['term'])){
		
		$data = $autocomplete->$methodName($_GET['term'],$id);
	}else{
		 $data = $autocomplete->$methodName(removeID($_GET['term']),0);	
	}
	
} else {
    $data  = '';
}

$errors = ob_get_clean();

if ($errors) {
    $data = array(array('id'    => 0,
                        'label' => $errors,
                        'value' => $errors));
}

print json_encode($data);
