<?php
require('inc/variables.php'); // require configuration
require('inc/RestClient.php');

$rest = new RestClient($_CONFIG['JACQ_SERVICES']);

echo $rest->get('classification/download', array(filter_input(INPUT_GET, 'referenceType', FILTER_SANITIZE_STRING),
                                                 intval(filter_input(INPUT_GET, 'referenceId', FILTER_SANITIZE_NUMBER_INT))),
                                           array('scientificNameId' => intval(filter_input(INPUT_GET, 'scientificNameId', FILTER_SANITIZE_NUMBER_INT)),
                                                 'hideScientificNameAuthors' => filter_input(INPUT_GET, 'hideScientificNameAuthors', FILTER_SANITIZE_STRING)));
die();
//http://localhost/develop.jacq/legacy/output.new/classificationBrowser_download.php?id=1&referenceType=citation&referenceId=31070&scientificNameId=233647&hideScientificNameAuthors=
