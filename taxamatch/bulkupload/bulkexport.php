<?php
session_name('herbarium_wu_taxamatch');
session_start();

include('inc/variables.php');
include('inc/connect.php');

if (empty($_SESSION['uid'])) die();

$result = db_query("SELECT * FROM tbljobs WHERE jobID = '" . intval($_GET['id']) . "' AND uid = '" . $_SESSION['uid'] . "'");
if (mysql_num_rows($result) == 0) die();
$row = mysql_fetch_array($result);
$jobID = $row['jobID'];

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


$header = "\"search for\"\t\"result\"\t\"Dist.\"\t\"Ratio\"";

$result = db_query("SELECT * FROM tblqueries WHERE jobID = '$jobID' ORDER BY lineNr");
$displayOnlyParts = (!empty($_GET['short']) || mysql_num_rows($result) > 50) ? 1 : 0;

$out = "";
$correct = 0;
while ($row = mysql_fetch_array($result)) {
    $matches = unserialize($row['result']);
    if ($matches) {
        foreach ($matches['result'] as $match) {
            $out2 = '';
            $found = 0;
            $blocked = 0;
            foreach ($match['searchresult'] as $key => $row) {
                if (isset($match['type']) && $match['type'] == 'uni') {
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
                        $showSynonyms = (!empty($row2['synonyms'])) ? true : false;          // BP, 07.2010: synonyms
                        if ($displayOnlyParts && $row2['distance'] == 0) $blocked++;
                        if ($found > 0) {
                            $out2 .= formatCell('');
                        }
                        $out2 .= formatCell($row2['taxon'] . ' <' . $row2['taxonID'] . '>')
                               . (($showSynonyms) ? (formatCell(prettyPrintSynonyms($row2['synonyms'])) . chr(10)) : formatCell(""))     // BP 07.2010: synonyms
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
            if (!$found || $found != $blocked) {
                $out .= formatCell($match['searchtext'])
                      . $out2;
            } else {
                $correct++;
            }
        }
    }
}
if ($correct > 0)  {
    $out .= formatCell("$correct queries had a full hit") . formatCell('') . formatCell('') . formatCell('') . "\n";
}

$out = str_replace("\r", "", $out); // embedded returns have "\r"

header("Content-type: application/octet-stream charset=utf-8");
header("Content-Disposition: attachment; filename=bulkexport.csv");
header("Pragma: no-cache");
header("Expires: 0");
echo chr(0xef) . chr(0xbb) . chr(0xbf) . $header . "\n" . $out;