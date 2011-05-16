<?php
require_once('inc/jsonRPCClient.php');
require_once('inc/variables.php');   // BP, 07.2010

$searchtext = $_GET['search'];
$database   = $_GET['db'];
$showSyns   = $_GET['showSyn'];      // BP, 07.2010


function formatCell($value) {

  if(!isset($value) || $value === '')
    $value = "\t";
  else {
    $value = str_replace('"', '""', $value); // escape quotes
    $value = '"'.$value.'"'."\t";
  }
  return $value;
}

// BP, 07.2010: return a pretty print of the synonyms belonging to a species
function prettyPrintSynonyms($synonyms20) {
    $synString = "";
    for ($counter20 = 0; $counter20 < count($synonyms20); $counter20++) {
        $synString .= prettyPrintSynonymEntry($synonyms20[$counter20]);
        $synonyms40 = $synonyms20[$counter20]['synonyms'];
        for ($counter40 = 0; $counter40 < count($synonyms40); $counter40++) {
            $synString .= prettyPrintSynonymEntry($synonyms40[$counter40],2);
        }
    }
    return $synString;
}

// BP, 07.2010: return a pretty print of one synonym
function prettyPrintSynonymEntry($synonym,$indent=1) {
    $startOfLine = chr(10);
    $sign = "&equiv;";
    if ($synonym['equalsSign'] == "=") {
        $sign = "&equ;";
    }
    for ($i=1; $i < $indent; $i++)
        $startOfLine .= "  ";
    $synString = $startOfLine ;
    //$synString .= $synonym['equalsSign'];
    $synString .= $sign;
    $synString .= "  ";
    $synString .= $synonym['status'];
    //$synString .= $startOfLine . "     ";
    $synString .= $synonym['name'];    
    return $synString;
}


// BP, 07.2010: get IP-address of JSON-service from 'variables.php'
$url = $options['hostAddr'] . "json_rpc_taxamatchMdld.php";
$service = new jsonRPCClient($url);
//$service = new jsonRPCClient('http://131.130.131.9/taxamatch/json_rpc_taxamatchMdld.php');
try {
    if ($database == 'col') {
        $matches = $service->getMatchesCol($searchtext);
    } elseif ($database == 'fe') {
        $matches = $service->getMatchesFaunaeuropea($searchtext);
    } else {
        if ($showSyns) {
            $matches = $service->getMatchesWithSynonyms($searchtext);
        } else {
            $matches = $service->getMatches($searchtext);
        }
    }

    $header = "\"search for\"\t\"result\"\t\"Dist.\"\t\"Ratio\"";

    $out = "";
    foreach ($matches['result'] as $match) {
        $out2 = '';
        $found = 0;
        foreach ($match['searchresult'] as $key => $row) {
            if ($match['type'] == 'uni') {
                if ($found > 0) {
                    $out2 .= formatCell('');
                }
                $out2 .= formatCell($row['taxon'] . ' <' . $row['ID'] . '>')
                       . formatCell($row['distance'])
                       . formatCell(number_format($row['ratio'] * 100, 1) . "%")
                       . "\n";
                $found++;
            } else {
                foreach ($row['species'] as $key2 => $row2) {
                    if ($found > 0) {
                        $out2 .= formatCell('');
                    }
                    $out2 .= formatCell($row2['taxon'] . ' <' . $row2['taxonID'] . '>')
                           . (($showSyns) ? (formatCell(prettyPrintSynonyms($row2['synonyms'])) . chr(10)) : formatCell(""))     // BP 07.2010: synonyms
                           . formatCell($row2['distance'])
                           . formatCell(number_format($row2['ratio'] * 100, 1) . "%")
                           . "\n";
                    if ($row2['syn']) {
                        $out2 .= formatCell('')
                               . formatCell("  -> " . $row2['syn'] . " <" . $row2['synID'] . ">")
                               . "\n";
                    }
                    $found++;
                }
            }
        }
        if (!$found) {
            $out2 = formatCell("nothing found") . formatCell('') . formatCell('') . "\n";
        }
        $out .= formatCell($match['searchtext'])
             . $out2;
    }

    $out = str_replace("\r", "", $out); // embedded returns have "\r"

    header("Content-type: application/octet-stream charset=utf-8");
    header("Content-Disposition: attachment; filename=taxamatchMdld.csv");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo chr(0xef) . chr(0xbb) . chr(0xbf) . $header . "\n" . $out;
}
catch (Exception $e) {
    ;
}
