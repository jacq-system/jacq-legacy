<?php
session_start();
require("inc/gatekeeper.php");
require __DIR__ . '/vendor/autoload.php';

use Jacq\AutocompleteCommonName;

ob_start();  // intercept all output

if (!empty($_GET['field'])) {
    if (!empty($_GET['term'])) {
        $autocomplete = AutocompleteCommonName::Load();

        $method = $_GET['field'];
        if (method_exists($autocomplete, $method)) {
            $value = $autocomplete->AjaxParseValue($_GET['term']);

            /*if(isset($_GET['searchparams'])){
                $a=explode(';',$_GET['searchparams']);
                foreach($a as $b){
                    list($k,$v)=explode('=',$b);
                    $value['params'][$k]=$v;
                }
            }*/

            $data = $autocomplete->$method($value);
        } else {
            $data = [['id' => 0, 'label' => 'error: method not found', 'value' => 'error: method not found']];
        }
    } else {
        $data = [['id' => 0, 'label' => 'error: term is missing', 'value' => 'error: term is missing']];
    }
} else {
    $data = [['id' => 0, 'label' => 'error: field is missing', 'value' => 'error: field is missing']];
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
