<?php
ini_set("soap.wsdl_cache_enabled", 0);  // turn caching off during development

$client = new SoapClient("http://herbarium.botanik.univie.ac.at/herbarium-wu/soap/herbar.wsdl");
echo "<pre>\n";

try {
    print($client->getTaxon("astragalus a"));
} catch (SoapFault $exception) {
    echo $exception;
}
echo "\n----\n";

try {
    print($client->getSynonyms(50481, 1));
} catch (SoapFault $exception) {
    echo $exception;
}
echo "\n----\n";

var_dump($client->__getFunctions());
echo "\n</pre>\n";
