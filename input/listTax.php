<?php
session_start();
require("inc/gatekeeper.php");
require_once('inc/jsonRPCClient.php');  // for MDLD-JSON service

require __DIR__ . '/vendor/autoload.php';

use Jacq\Settings;
use Jacq\DbAccess;
use Jacq\Display;
use Jacq\Tools;
use Jaxon\Jaxon;

$jaxon = jaxon();
$jaxon->app()->setup(__DIR__ . '/inc/jacqJaxonConfig.php');

if (!isset($_SESSION['taxStatus']))     { $_SESSION['taxStatus'] = ""; }
if (!isset($_SESSION['taxRank']))       { $_SESSION['taxRank'] = ""; }
if (!isset($_SESSION['taxMDLD']))       { $_SESSION['taxMDLD'] = ""; } // BP
if (!isset($_SESSION['noLiterature']))  { $_SESSION['noLiterature'] = false; }
if (!isset($_SESSION['taxon_list']))    { $_SESSION['taxon_list'] = ''; }

$nrSel = (!empty($_GET['nr'])) ? intval($_GET['nr']) : 0;

$NOLITERATURE_SQL_STATEMENT="NOT EXISTS ( SELECT tax_syn_ID FROM herbarinput.tbl_tax_synonymy syncheck where syncheck.taxonID=ts.taxonID and (  ifnull( IF(syncheck.source='literature', syncheck.source_citationID, IF(syncheck.source='person', syncheck.source_person_ID, IF(syncheck.source='service', syncheck.source_serviceID, IF(syncheck.source='specimen', syncheck.source_specimenID,NULL)))),0)<>0))";


if (!empty($_GET['taxon_list'])) {
    $buffer = trim(preg_replace("/[^0-9,]/", "", $_GET['taxon_list']));   // remove all but numbers and comma
    if (str_contains($buffer, ',')) {   // only react if "taxon_list" contains at least two numbers
        $_POST['taxon'] = $buffer;
        $_POST['select'] = 'select';
    }
} elseif (!empty($_GET['genus'])) {
    $_POST['genus'] = $_GET['genus'];
    $_POST['mdld']  = '';
    $_POST['search'] = 'search';
} elseif (!empty($_GET['family'])) {
    $_POST['family'] = $_GET['family'];
    $_POST['mdld']  = '';
    $_POST['search'] = 'search';
}

// "taxon" contains numbers (one or more)
if (!empty($_POST['select']) && !empty($_POST['taxon'])) {
    $buffer = trim(preg_replace("/[^0-9,]/", "", $_POST['taxon']));   // remove all but numbers and comma
	if (str_contains($buffer, ',')) {
        $parts = explode(',', $buffer);
        $newparts = array();
        if ($parts) {
            foreach ($parts as $part) {
                if (intval($part) > 0) {
                    $newparts[] = intval($part);
                }
            }
        }
        if (!empty($newparts)) {
            $buffer = implode(',', $newparts);;
        }
    }
    if (str_contains($buffer, ',')){
        $_SESSION['taxon_list'] = $buffer;

        $_SESSION['taxType']       = 3; // list Species
        $_SESSION['taxMDLD']       = "";
        $_SESSION['noLiterature']  = isset($_POST['noLiterature']) && $_POST['noLiterature'] == '1';
        $_SESSION['taxCommonname'] = "";
        $_SESSION['taxFamily']     = ($_SESSION['editFamily']) ?: "";
        $_SESSION['taxGenus']      = "";
        $_SESSION['taxSpecies']    = "";
        $_SESSION['taxStatus']     = "";
        $_SESSION['taxRank']       = "";
        $_SESSION['taxCollector']  = "";
        $_SESSION['taxNumber']     = "";
        $_SESSION['taxDate']       = "";
        $_SESSION['taxAuthor']     = "";
        $_SESSION['taxAnnotation'] = "";
        $_SESSION['taxExternal']   = false;
        $_SESSION['taxOrder']      = "genus, auth_g, family, epithet, author, epithet1, author1, "
                                   . "epithet2, author2, epithet3, author3, epithet4, author4, epithet5, author5";
        $_SESSION['taxOrTyp']      = 31;
    } else {
        $_SESSION['taxon_list'] = intval($buffer);
		$location="Location: editSpecies.php?sel=<" . $_SESSION['taxon_list'] . ">";
		if (SID != "") $location .= "?" . SID;
		header($location);
	}
} elseif (isset($_POST['search'])) {
	if (empty($_POST['taxon'])) {
        $_SESSION['taxon_list'] = '';
    }
    $_SESSION['taxMDLD'] = $_POST['mdld'];  // BP
	if(!empty($_SESSION['taxMDLD'])){
		$_SESSION['noLiterature']  = isset($_POST['noLiterature'])&&$_POST['noLiterature']=='1';

	}
    if ($_SESSION['editFamily']) $_POST['family'] = $_SESSION['editFamily'];
    if (!empty($_POST['commonname'])) { // commonName
		$_SESSION['taxType']       = 5; // list common names with different interface
		$_SESSION['noLiterature']  = isset($_POST['noLiterature']) && $_POST['noLiterature'] == '1';
		$_SESSION['taxCommonname'] = $_POST['commonname'];
		$_SESSION['taxFamily']     = "";
        $_SESSION['taxGenus']      = "";
        $_SESSION['taxSpecies']    = "";
        $_SESSION['taxStatus']     = "";
        $_SESSION['taxCollector']  = "";
        $_SESSION['taxNumber']     = "";
        $_SESSION['taxDate']       = "";
        $_SESSION['taxAuthor']     = "";
        $_SESSION['taxAnnotation'] = "";
        $_SESSION['taxExternal']   = "";
        $_SESSION['taxOrder']      = "genus, auth_g, family, epithet,common_name, author";
        $_SESSION['taxOrTyp']      = 51;
	} elseif (!empty($_POST['collector']) || !empty($_POST['number']) || !empty($_POST['date'])) {
        $_SESSION['taxType']       = 4; // list Types
		$_SESSION['noLiterature']  = isset($_POST['noLiterature']) && $_POST['noLiterature'] == '1';
        $_SESSION['taxCommonname'] = "";
        $_SESSION['taxFamily']     = $_POST['family'];
        $_SESSION['taxGenus']      = $_POST['genus'];
        $_SESSION['taxSpecies']    = $_POST['species'];
        $_SESSION['taxStatus']     = $_POST['status'];
        $_SESSION['taxRank']       = $_POST['rank'];
        $_SESSION['taxCollector']  = $_POST['collector'];
        $_SESSION['taxNumber']     = $_POST['number'];
        $_SESSION['taxDate']       = $_POST['date'];
        $_SESSION['taxAuthor']     = $_POST['author'];
        $_SESSION['taxAnnotation'] = $_POST['annotation'];
        $_SESSION['taxExternal']   = isset($_POST['external']) && $_POST['external'] == '1';
        $_SESSION['taxOrder']      = "Sammler, Sammler_2, series, leg_nr, tt.date";
        $_SESSION['taxOrTyp']      = 41;
    } elseif (!empty($_POST['species']) || !empty($_POST['status'])){
        $_SESSION['taxType']       = 3; // list Species
		$_SESSION['noLiterature']  = isset($_POST['noLiterature']) && $_POST['noLiterature'] == '1';
        $_SESSION['taxCommonname'] = "";
        $_SESSION['taxFamily']     = $_POST['family'];
		$_SESSION['taxGenus']      = $_POST['genus'];
        $_SESSION['taxSpecies']    = $_POST['species'];
        $_SESSION['taxStatus']     = $_POST['status'];
        $_SESSION['taxRank']       = $_POST['rank'];
        $_SESSION['taxCollector']  = "";
        $_SESSION['taxNumber']     = "";
        $_SESSION['taxDate']       = "";
        $_SESSION['taxAuthor']     = $_POST['author'];
        $_SESSION['taxAnnotation'] = $_POST['annotation'];
        $_SESSION['taxExternal']   = isset($_POST['external']) && $_POST['external'] == '1';
        $_SESSION['taxOrder']      = "genus, auth_g, family, epithet, author, epithet1, author1, "
                                   . "epithet2, author2, epithet3, author3, epithet4, author4, epithet5, author5";
        $_SESSION['taxOrTyp']      = 31;
    } elseif (!empty($_POST['genus'])) {
        $_SESSION['taxType']       = 2; // list Genus
		$_SESSION['noLiterature']  = isset($_POST['noLiterature']) && $_POST['noLiterature'] == '1';
        $_SESSION['taxCommonname'] = "";
        $_SESSION['taxFamily']     = $_POST['family'];
        $_SESSION['taxGenus']      = $_POST['genus'];
        $_SESSION['taxSpecies']    = "";
        $_SESSION['taxStatus']     = $_POST['status'];
        $_SESSION['taxRank']       = "";
        $_SESSION['taxCollector']  = "";
        $_SESSION['taxNumber']     = "";
        $_SESSION['taxDate']       = "";
        $_SESSION['taxAuthor']     = "";
        $_SESSION['taxAnnotation'] = $_POST['annotation'];
        $_SESSION['taxExternal']   = isset($_POST['external']) && $_POST['external'] == '1';
        $_SESSION['taxOrder']      = "genus, auth_g, family";
        $_SESSION['taxOrTyp']      = 21;
    } else {
        $_SESSION['taxType']       = 1; // list Family
		$_SESSION['noLiterature']  = isset($_POST['noLiterature']) && $_POST['noLiterature'] == '1';
        $_SESSION['taxCommonname'] = "";
		$_SESSION['taxFamily']     = $_POST['family'] ?? "";
        $_SESSION['taxGenus']      = "";
        $_SESSION['taxSpecies']    = "";
        $_SESSION['taxStatus']     = "";
        $_SESSION['taxCollector']  = "";
        $_SESSION['taxNumber']     = "";
        $_SESSION['taxDate']       = "";
        $_SESSION['taxAuthor']     = "";
        $_SESSION['taxAnnotation'] = "";
        $_SESSION['taxExternal']   = isset($_POST['external']) && $_POST['external'] == '1';
        $_SESSION['taxOrder']      = "category, family";
        $_SESSION['taxOrTyp']      = 11;
    }
} elseif (isset($_GET['order'])) {
    if ($_SESSION['taxType'] == 5) { // list common names with different interface
		if ($_GET['order']=="db") {
			$_SESSION['taxOrder'] = "genus, auth_g, family, epithet,common_name, author";
			if ($_SESSION['taxOrTyp'] == 52) {
				$_SESSION['taxOrTyp'] = -52;
			} else {
				$_SESSION['taxOrTyp'] = 52;
			}
		} else {
			$_SESSION['taxOrder'] = "common_name,genus, auth_g, family, epithet, author";
			if ($_SESSION['taxOrTyp'] == 51) {
				$_SESSION['taxOrTyp'] = -51;
			} else {
				$_SESSION['taxOrTyp'] = 51;
			}
		}
	} elseif ($_SESSION['taxType'] == 4) { // list Types
        if ($_GET['order']=="db") {
            $_SESSION['taxOrder'] = "family, genus, epithet, author, epithet1, author1, "
                                  . "epithet2, author2, epithet3, author3, epithet4, author4, epithet5, author5";
            if ($_SESSION['taxOrTyp'] == 42) {
                $_SESSION['taxOrTyp'] = -42;
            } else {
                $_SESSION['taxOrTyp'] = 42;
            }
        } else {
            $_SESSION['taxOrder'] = "Sammler, Sammler_2, series, leg_nr, tt.date";
            if ($_SESSION['taxOrTyp'] == 41) {
                $_SESSION['taxOrTyp'] = -41;
            } else {
                $_SESSION['taxOrTyp'] = 41;
            }
        }
    } elseif ($_SESSION['taxType'] == 3) { // list Species
        if ($_GET['order'] == "cr") {
            $_SESSION['taxOrder'] = "tr.rank_hierarchy, genus, auth_g, family, epithet, author, epithet1, author1, "
                                  . "epithet2, author2, epithet3, author3, epithet4, author4, epithet5, author5";
            if ($_SESSION['taxOrTyp'] == 34) {
                $_SESSION['taxOrTyp'] = -34;
            } else {
                $_SESSION['taxOrTyp'] = 34;
            }
        } elseif ($_GET['order'] == "cs") {
            $_SESSION['taxOrder'] = "epithet, genus, auth_g, family, author, epithet1, author1, "
                                  . "epithet2, author2, epithet3, author3, epithet4, author4, epithet5, author5";
            if ($_SESSION['taxOrTyp'] == 33) {
                $_SESSION['taxOrTyp'] = -33;
            } else {
                $_SESSION['taxOrTyp'] = 33;
            }
        } elseif ($_GET['order'] == "cf") {
            $_SESSION['taxOrder'] = "family, genus, auth_g, epithet, author, epithet1, author1, "
                                  . "epithet2, author2, epithet3, author3, epithet4, author4, epithet5, author5";
            if ($_SESSION['taxOrTyp'] == 32) {
                $_SESSION['taxOrTyp'] = -32;
            } else {
                $_SESSION['taxOrTyp'] = 32;
            }
        } else {
            $_SESSION['taxOrder'] = "genus, auth_g, family, epithet, author, epithet1, author1, "
                                  . "epithet2, author2, epithet3, author3, epithet4, author4, epithet5, author5";
            if ($_SESSION['taxOrTyp'] == 31) {
                $_SESSION['taxOrTyp'] = -31;
            } else {
                $_SESSION['taxOrTyp'] = 31;
            }
        }
    } elseif ($_SESSION['taxType'] == 2) { // list Genus
        if ($_GET['order'] == "bf") {
            $_SESSION['taxOrder'] = "family, genus, auth_g";
            if ($_SESSION['taxOrTyp'] == 22) {
                $_SESSION['taxOrTyp'] = -22;
            } else {
                $_SESSION['taxOrTyp'] = 22;
            }
        }
        else {
            $_SESSION['taxOrder'] = "genus, auth_g, family";
            if ($_SESSION['taxOrTyp'] == 21) {
                $_SESSION['taxOrTyp'] = -21;
            } else {
                $_SESSION['taxOrTyp'] = 21;
            }
        }
    } else { // list Family (Type=1)
        if ($_GET['order'] == "af") {
            $_SESSION['taxOrder'] = "family, category";
            if ($_SESSION['taxOrTyp'] == 12) {
                $_SESSION['taxOrTyp'] = -12;
            } else {
                $_SESSION['taxOrTyp'] = 12;
            }
        } else {
            $_SESSION['taxOrder'] = "category, family";
            if ($_SESSION['taxOrTyp'] == 11) {
                $_SESSION['taxOrTyp'] = -11;
            } else {
                $_SESSION['taxOrTyp'] = 11;
            }
        }
    }
    if ($_SESSION['taxOrTyp'] < 0) {
        $_SESSION['taxOrder'] = implode(" DESC, ", explode(", ", $_SESSION['taxOrder'])) . " DESC";
    }
}

function prettyPrintSynonymLinks(): string
{
    return "<td style=\"width:20px\">&nbsp;</td>"
         . "<td><a href=\"javascript:listSynonyms(0,0)\">list synonyms (long)</a></td>"
         . "<td style=\"width:20px\">&nbsp;</td>"
         . "<td><a href=\"javascript:listSynonyms(1,0)\">list synonyms (short)</a></td>"
         . "<td style=\"width:20px\">&nbsp;</td>"
         . "<td><a href=\"javascript:listSynonyms(1,1)\">list names alphabetically</a></td"
	     . "<td style=\"width:20px\">&nbsp;&nbsp;&nbsp;&nbsp;</td>"
         . "<td><a href=\"javascript:listSynonyms(1,2)\">list CommonNames alphabetically</a></td>";
}

// BP: for testing only!
function dumpMatchJsonRPC($searchtext): string
{
    $searchtext = ucfirst(trim($searchtext));
    if (substr($searchtext, 0, 3) == chr(0xef) . chr(0xbb) . chr(0xbf)) $searchtext = substr($searchtext, 3);

    $settings = Settings::Load();
    $service = new jsonRPCClient($settings->get('serviceTaxamatch'));

    try {
        $matches = $service->getMatchesService('vienna',$searchtext,array('showSyn'=>false,'NearMatch'=>false));
        if (!empty($formData['nearmatch'])) {
            $matchesNearMatch = $service->getMatchesService('vienna',$searchtext,array('showSyn'=>false,'NearMatch'=>true));
        } else {
            $matchesNearMatch = array();
        }

        $out = "<span style='font-size: larger'><b>Dump or Results for search for '" . nl2br($searchtext) . "':</b></span><br>\n"
             . "<pre>" . var_export($matches, true) . "\n" . var_export($matchesNearMatch, true) . "</pre>\n";
    }
    catch (Exception $e) {
        $out =  "Fehler " . nl2br($e);
    }

    return $out;
}

function LiteratureExists($taxonID): bool
{
    try {
        $db = DbAccess::ConnectTo('INPUT');
    } catch (Exception) {
        return false;
    }

	$result = $db->queryCatch("SELECT tax_syn_ID 
                               FROM herbarinput.tbl_tax_synonymy syncheck 
                               WHERE syncheck.taxonID = '" . $db->real_escape_string($taxonID) . "' 
                                AND (  ifnull( IF(syncheck.source='literature', syncheck.source_citationID, IF(syncheck.source='person', syncheck.source_person_ID, IF(syncheck.source='service', syncheck.source_serviceID, IF(syncheck.source='specimen', syncheck.source_specimenID,NULL)))),0)<>0)
                               LIMIT 1");
	if ($result->num_rows > 0){
		return true;
	}
	return false;
}

// BP: convert JSON-MDLD-result into a formatted HTML-string
// BP: for some reason, can't access global $nrSel in this function
// BP: ==> pass it as a parameter & find out why it didn't work!
// BP: returns formatted HTML-string containing MDLD-result and content for field "taxonID"
function showMatchJsonRPCClickable($searchtext, $selectedRow=1,$useNearMatch=false): array
{
    $searchtext = ucfirst(trim($searchtext));
    if (substr($searchtext, 0, 3) == chr(0xef) . chr(0xbb) . chr(0xbf)) $searchtext = substr($searchtext, 3);

    $settings = Settings::Load();
    $service = new jsonRPCClient($settings->get('serviceTaxamatch'));
    try {
        $matches = $service->getMatchesService('vienna',$searchtext,array('showSyn'=>false,'NearMatch'=>false));
        if ($useNearMatch) {
            $matchesNearMatch = $service->getMatchesService('vienna',$searchtext,array('showSyn'=>false,'NearMatch'=>true));
        } else {
            $matchesNearMatch = array();
        }

        if (!empty($matches['error'])) {
            $out = $matches['error'];
        } elseif (!empty($matchesNearMatch['error'])) {
            $out = $matchesNearMatch['error'];
        } else {
            $out = "";
            $indexMatch = 0;
            $showSynonyms = false;  // only show synonyms if result contains at least one taxonID
            $nr=0;                  // current line-no of clickable taxonIDs in result
            $isHighlighted = false; // only highlight if indicated by $selectedRow
            $matchingTaxonID = "";  // taxonID to show in field "taxonID"

            while ($indexMatch < count($matches['result'])) {
                $countResults = count($matches['result'][$indexMatch]['searchresult']);
                $countResultsNearMatch = ($useNearMatch) ? count($matchesNearMatch['result'][$indexMatch]['searchresult']) : 0;
                $out2 = '';
                $found = 0;
                $numSyn = 1;    //
                $foundNearMatch = 0;
                $line = 0;
                $indexResult = 0;
                $columnLeft = $columnRight = array();
                while ($indexResult < $countResults || $indexResult < $countResultsNearMatch) {
                    if ($matches['result'][$indexMatch]['type'] == 'uni') {
                        if (($indexResult < $countResults) &&
                                (!$_SESSION['noLiterature'] || !LiteratureExists($matches['result'][$indexMatch]['searchresult'][$indexResult]['ID'])) ) {

                            $row = $matches['result'][$indexMatch]['searchresult'][$indexResult];
                            $found++;
                            // uninomial --> link to "editGenera.php"
                            $newLink = "<a href=\"javascript:editGenera(" . $row['ID'] . ")\">";
                            $out2Left = '<td>&nbsp;&nbsp;<b>'
                                      . $newLink
                                      . $row['taxon']
                                      //. '</a>'
                                      . ' <' . $row['ID'] . '></a></b></td>'
                                      . '<td>&nbsp;' . $row['distance'] . '&nbsp;</td>'
                                      . '<td align="right">&nbsp;' . number_format($row['ratio'] * 100, 1) . "%</td>";
                        } else {
                            $out2Left = "<td></td><td></td><td></td>";
                        }
                        if ($useNearMatch) {
                            if (($indexResult < $countResultsNearMatch) &&
                                    (!$_SESSION['noLiterature'] || !LiteratureExists($matches['result'][$indexMatch]['searchresult'][$indexResult]['ID'])) ) {
                                $row = $matchesNearMatch['result'][$indexMatch]['searchresult'][$indexResult];

                                $out2Right = '<td>&nbsp;&nbsp;<b>' . $row['taxon'] . ' <' . $row['ID'] . '></b></td>'
                                           . '<td>&nbsp;' . $row['distance'] . '&nbsp;</td>'
                                           . '<td align="right">&nbsp;' . number_format($row['ratio'] * 100, 1) . "%</td>";
                                $foundNearMatch++;
                            } else {
                                $out2Right = "<td></td><td></td><td></td>";
                            }
                        } else {
                            $out2Right = '';
                        }
                        if ($line == 0) {
                            $out2Firstline = array($out2Left, $out2Right);
                        } else {
                            $out2 .= "<tr valign='baseline'>" . $out2Left . $out2Right . "</tr>\n";
                        }
                        $line++;
                    } else {    // multi
                        $showSynonyms = true;   // BP: result contains at least one taxonID --> ok to show link to synonyms
                        if ($indexResult < $countResults) {
                            $row = $matches['result'][$indexMatch]['searchresult'][$indexResult];
                            foreach ($row['species'] as $key2 => $row2) {
                                if(	(!$_SESSION['noLiterature'] || !LiteratureExists($row2['taxonID'])) ) {


                                    $nr++;
                                    if (($matchingTaxonID == "") || ($nr == $selectedRow)) {
                                        $matchingTaxonID = $row2['taxonID'];
                                    }
                                    $found++;
                                    // contains taxonID --> link to "editSpecis.php"
                                    $newLink = "<a href=\"editSpecies.php?sel="
                                             . htmlspecialchars("<" . $row2['taxonID'] . ">")
                                             . "&nr=" . ($nr) . "\">";
                                    $columnLeft[] = '<td class="' . (($nr == $selectedRow) ? 'outMark' : 'out' ) . '">&nbsp;&nbsp;<b>'
                                                  . $newLink
                                                  . $row2['taxon']
                                                  //. ' </a>'
                                                  .'<' . $row2['taxonID'] . '>'
                                                  . '</a>'
                                                  . '</b>'
                                                  . (($row2['syn']) ? "<br>&nbsp;&nbsp;&rarr;&nbsp;" . $row2['syn'] . " <" . $row2['synID'] . ">" : "")
                                                  . '</td>'
                                                  . '<td class="' . (($nr == $selectedRow) ? 'outMark' : 'out' ) . '">&nbsp;' . $row2['distance'] . '&nbsp;</td>'
                                                  . '<td align="right" class="' . (($nr == $selectedRow) ? 'outMark' : 'out' ) . '">&nbsp;' . number_format($row2['ratio'] * 100, 1) . "%</td>";

                                    // save current taxonID for "editSpecies.php" and "listSynonyms.php"
                                    $linkList[$numSyn++] = $row2['taxonID'];
//                                } else {
//                                    $columnLeft[] = '<td class="' . (($nr == $selectedRow) ? 'outMark' : 'out' ) . '">&nbsp;</td>'
//                                                  . '<td class="' . (($nr == $selectedRow) ? 'outMark' : 'out' ) . '">&nbsp;</td>'
//                                                  . '<td align="right" class="' . (($nr == $selectedRow) ? 'outMark' : 'out' ) . ">&nbsp;</td>";
                                }
                            }
                        }
                        if ($indexResult < $countResultsNearMatch) {
                            $row = $matchesNearMatch['result'][$indexMatch]['searchresult'][$indexResult];
                            foreach ($row['species'] as $key2 => $row2) {
                                if(	(!$_SESSION['noLiterature'] || !LiteratureExists($row2['taxonID'])) ) {

                                    $columnRight[] = '<td>&nbsp;&nbsp;<b>' . $row2['taxon'] . ' <' . $row2['taxonID'] . '></b>'
                                                   . (($row2['syn']) ? "<br>&nbsp;&nbsp;&rarr;&nbsp;" . $row2['syn'] . " <" . $row2['synID'] . ">" : "")
                                                   . '</td>'
                                                   . '<td>&nbsp;' . $row2['distance'] . '&nbsp;</td>'
                                                   . '<td align="right">&nbsp;' . number_format($row2['ratio'] * 100, 1) . "%</td>";
                                    $foundNearMatch++;
                                }
                            }
                        }
                    }
                    $indexResult++;
                }
                if ($columnLeft || $columnRight) {
                    $out2Firstline = array((!empty($columnLeft[0]))  ? $columnLeft[0]  : "<td></td><td></td><td></td>",
                                           (!empty($columnRight[0])) ? $columnRight[0] : "<td></td><td></td><td></td>");
                    $line = 1;
                    while ($line < count($columnLeft) || $line < count($columnRight)) {
                        if ($line > 0) {
                            $out2 .= "<tr valign='baseline'>";
                        }
                        if ($line < count($columnLeft)) {
                            $out2 .= $columnLeft[$line];
                        } else {
                            $out2 .= "<td></td><td></td><td></td>";
                        }
                        if ($useNearMatch) {
                            if ($line < count($columnRight)) {
                                $out2 .= $columnRight[$line];
                            } else {
                                $out2 .= "<td></td><td></td><td></td>";
                            }
                        }
                        $out2 .= "</tr>\n";
                        $line++;
                    }
                }
                if (!$found && !$foundNearMatch) {
                    $out2Firstline = array("<td colspan='3'>nothing found</td>", "<td colspan='3'>nothing found</td>");
                    $line++;
                }
                $out  .=  "<tr valign='baseline'>"
                      . "<td rowspan='$line'>"
                      . "&nbsp;&nbsp;<span style='font-size: larger'><b>" . $matches['result'][$indexMatch]['searchtext'] . "</b></span>&nbsp;&nbsp;<br>\n"
                      . "&nbsp;&nbsp;$found match" . (($found > 1) ? 'es' : '') . " found&nbsp;&nbsp;<br>\n"
                      . "&nbsp;&nbsp;" . $matches['result'][$indexMatch]['rowsChecked'] . " rows checked&nbsp;&nbsp;"
                      . "</td>"
                      . $out2Firstline[0];
                if ($useNearMatch) {
                      $out .= "<td rowspan='$line'>"
                            . "&nbsp;&nbsp;<span style='font-size: larger'><b>" . $matchesNearMatch['result'][$indexMatch]['searchtextNearmatch'] . "</b></span>&nbsp;&nbsp;<br>\n"
                            . "&nbsp;&nbsp;$foundNearMatch match" . (($foundNearMatch > 1) ? 'es' : '') . " found&nbsp;&nbsp;<br>\n"
                            . "&nbsp;&nbsp;" . $matchesNearMatch['result'][$indexMatch]['rowsChecked'] . " rows checked&nbsp;&nbsp;"
                            . "</td>"
                            . $out2Firstline[1];
                }
                $out .= "</tr>\n"
                      . $out2;
                $indexMatch++;
            }

            // decide whether to show link to "listSynonyms.php"
            if ($showSynonyms) {
                $synonymyHeader = "<table><tr>" . prettyPrintSynonymLinks() . "</tr></table><br>";
            } else {
                $synonymyHeader="";
            }
            $out = $synonymyHeader
                 //. "<a href='taxamatchExport.php?search=" . urlencode($searchtext) . "&db=" . $formData['database'] . "' target='_blank'>export csv</a><br>\n"
                 //. "<big>" . number_format(($stop - $start), 2) . " seconds needed</big><br>\n"
                 . "<table rules='all' border='1'>\n"
                 . "<tr><th>&nbsp;search for&nbsp;</th><th>result</th><th>Dist.</th><th>Ratio</th>"
                 . (($useNearMatch) ? "<th>&nbsp;search for&nbsp;</th><th>result near match</th><th>Dist.n.m.</th><th>Ratio n.m.</th>" : "")
                 . "</tr>\n"
                 . $out
                 . "</table>\n";
        }
    }
    catch (Exception $e) {
        $out =  "Fehler " . nl2br($e);
    }

    $outArray = array('html'    => $out);

    // for synonyms and editSpecies.php
    $linkList[0] = $numSyn - 1;
    $_SESSION['txLinkList'] = $linkList;

    return $outArray;
}


function typusItem($row): string
{
    $text = $row['Sammler'];
    if ($row['Sammler_2']) {
        if (!str_contains($row['Sammler_2'], "&")) {
            $text .= " & " . $row['Sammler_2'];
        } else {
            $text .= " et al.";
        }
    }
    if ($row['series']) $text .= " " . $row['series'];
    if ($row['leg_nr']) $text .= " " . $row['leg_nr'];
    if ($row['alternate_number']) {
        $text .= " " . $row['alternate_number'];
        if (str_contains($row['alternate_number'], "s.n.")) {
            $text .= " [" . $row['date'] . "]";
        }
    }
    $text .= "; " . $row['duplicates'];

    return $text;
}


function getSubTaxonItem($row): string
{
    $text = "";
    if ($row['epithet1']) { $text .= " subsp. "   . $row['epithet1'] . " " . $row['author1']; }
    if ($row['epithet2']) { $text .= " var. "     . $row['epithet2'] . " " . $row['author2']; }
    if ($row['epithet3']) { $text .= " subvar. "  . $row['epithet3'] . " " . $row['author3']; }
    if ($row['epithet4']) { $text .= " forma "    . $row['epithet4'] . " " . $row['author4']; }
    if ($row['epithet5']) { $text .= " subforma " . $row['epithet5'] . " " . $row['author5']; }

    return $text;
}


function getHybrids($taxonID): string
{
    try {
        $db = DbAccess::ConnectTo('INPUT');
    } catch (Exception) {
        return '';
    }

    $text = "";
    $result = $db->queryCatch("SELECT taxon_ID_fk
                               FROM tbl_tax_hybrids
                               WHERE taxon_ID_fk = '$taxonID' 
                                OR parent_1_ID = '$taxonID' 
                                OR parent_2_ID = '$taxonID'");
    while ($row = $result->fetch_array()) {
        $text .= Tools::getScientificName($row['taxon_ID_fk']) . "<br />\n";
    }

    return $text;
}


function makeDropdown($name, $select, $value, $text): void
{
    echo "<select name='$name' id='$name'>\n";
    for ($i = 0; $i < count($value); $i++) {
        echo "  <option";
        if ($value[$i] != $text[$i]) echo " value=\"" . $value[$i] . "\"";
        if ($select == $value[$i]) print " selected";
        echo ">" . htmlspecialchars($text[$i]) . "</option>\n";
    }
    echo "</select>\n";
}


function taxonWithFamily($row): string
{
    $text = strtoupper($row['family'] . " " . $row['category']) . " "
          . $row['DallaTorreIDs'] . $row['DallaTorreZusatzIDs'] . " "
          . $row['genus'] . " " . $row['author_g'];
    if ($row['epithet']) {
        $text .= " " . $row['epithet'] . chr(194) . chr(183) . " " . $row['author'];
    } else {
        $text .= chr(194) . chr(183);
    }
    if ($row['epithet1']) $text .= " subsp. " . $row['epithet1'] . " " . $row['author1'];
    if ($row['epithet2']) $text .= " var. " . $row['epithet2'] . " " . $row['author2'];
    if ($row['epithet3']) $text .= " subvar. " . $row['epithet3'] . " " . $row['author3'];
    if ($row['epithet4']) $text .= " forma " . $row['epithet4'] . " " . $row['author4'];
    if ($row['epithet5']) $text .= " subforma " . $row['epithet5'] . " " . $row['author5'];

    $text .= " <" . $row['taxonID'] . ">";

    return $text;
} // end taxonWithFamily

$db = DbAccess::ConnectTo('INPUT');
$display = Display::Load();

unset($status);
$status[] = "";
$status[] = "everything";
if ($result = $db->queryCatch("SELECT status, statusID FROM tbl_tax_status ORDER BY status")) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array()) {
            $status[] = $row['status'] . " <" . $row['statusID'] . ">";
        }
    }
}

unset($rank);
$rank[] = "";
if ($result = $db->queryCatch("SELECT rank, tax_rankID FROM tbl_tax_rank ORDER BY rank")) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array()) {
            $rank[] = $row['rank'] . " <" . $row['tax_rankID'] . ">";
        }
    }
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html lang="en">
<head>
  <title>herbardb - list Species</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <link rel="stylesheet" href="js/lib/jQuery/css/blue/style_nhm.css" type="text/css" />
  <?php echo $jaxon->getScript(true, true); ?>
  <!-- BP: use jQuery for disabling edit-fields if MDLD-search has been entered -->
  <script src="js/lib/jQuery/jquery.min.js" type="text/javascript"></script>
  <!-- BP END -->
  <script type="text/javascript" language="JavaScript">
      // BP: add. code for enabling/disabling search-fiels when MDLD-input field is empty (or not)
      let isMDLDSearch = false;         // BP: global variable indicating which fields are currently en/disabled

      let fieldsArr = ["family",        // BP: fields to be dis/enabled according to MDLD input-field
                       "genus",
                       "species",
                       "status",
                       "rank",
                       "author",
                       "collector",
                       "number",
                       "date",
                       "annotation",
                       "external"];

      let mdldField = "#mdld";          // BP: name of MDLD-input-field
      // BP: END

    function editFamily(sel) {
      let target = "editFamily.php?sel=" + encodeURIComponent(sel);
      let MeinFenster = window.open(target,"editFamily","width=300,height=150,top=60,left=60,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function editGenera(sel) {
      let target = "editGenera.php?sel=" + encodeURIComponent(sel);
      let MeinFenster = window.open(target,"editGenera","width=600,height=500,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function listSynonyms(sel,type) {
      let target  = "listSynonyms.php?short=" + encodeURIComponent(sel) + "&listOnly=" + encodeURIComponent(type);
      let options = "width=800,height=800,top=50,left=50,scrollbars=yes,resizable=yes";
      let MeinFenster = window.open(target,"listSynonyms",options);
      MeinFenster.focus();
    }
    function showPDF(sel) {
      let target, label;
      switch (sel) {
        case 'scientificName':  target = "pdfLabelScientificName.php";  label = "labelScientificName"; break;
      }
      let MeinFenster = window.open(target, label);
      MeinFenster.focus();
    }

    // BP: as soon as user enters something into MDLD-input-field:
    // BP: disable other input-fields
    // BP: when all information of MDLD-field is removed: enable them again
    $(document).ready(function() {
        let checkEnableEdit = function() {
            $.each(fieldsArr, function(key,val) {
                let myLabel = "#" + val + "Label";
                let myField = "#" + val;
                if (($(mdldField).val() == "") && (isMDLDSearch)) {
                    $(myLabel).fadeTo('fast',1);
                    $(myField).removeAttr("disabled");
                } else if (($(mdldField).val() != "") && (! isMDLDSearch)) {
                    $(myLabel).fadeTo('fast',0.5);
                    $(myField).attr("disabled",true);
                }
            });
            isMDLDSearch = $(mdldField).val() != "";
        };

        // always check when window is opened
        checkEnableEdit();

        // every time something is changed in MDLD-input field, check if we need to en/disable other fields
        // TODO: does not react in all cases, e.g. after pressing Ctrl+V, the fields are not disabled
        // TODO: it is necessary to press another button.
        $("#mdld").keyup(function() {
            checkEnableEdit();
        });
        $("#mdld").mouseup(function() {
            checkEnableEdit();
        });
    });
  </script>
</head>

<body>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST">

<table cellspacing="5" cellpadding="0">
<tr>
  <td align="right" >&nbsp;<b><label for="mdld">MDLD-Search:</label></b></td>
    <td colspan="5"><input type="text" name="mdld" id="mdld" size="89" value="<?php echo htmlspecialchars($_SESSION['taxMDLD'] ?? ''); ?>"></td>
</tr><tr>
  <td align="right" id="familyLabel">&nbsp;<b><label for="family">Family:</label></b></td>
    <td><input type="text" name="family" id="family" value="<?php echo htmlspecialchars($_SESSION['taxFamily'] ?? ''); ?>"<?php if ($_SESSION['editFamily']) echo "disabled"; ?>></td>
  <td align="right" id="genusLabel">&nbsp;<b><label for="genus">Genus:</label></b></td>
    <td><input type="text" name="genus" id="genus" value="<?php echo htmlspecialchars($_SESSION['taxGenus'] ?? ''); ?>"></td>
  <td align="right" id="speciesLabel">&nbsp;<b><label for="species">Species:</label></b></td>
    <td><input type="text" name="species" id="species" value="<?php echo htmlspecialchars($_SESSION['taxSpecies'] ?? ''); ?>"></td>
</tr><tr>
  <td align="right" id="statusLabel">&nbsp;<b>Status:</b></td>
    <td><?php makeDropdown("status",$_SESSION['taxStatus'],$status,$status); ?></td>
  <td align="right" id="rankLabel">&nbsp;<b>Rank:</b></td>
    <td><?php makeDropdown("rank",$_SESSION['taxRank'],$rank,$rank); ?></td>
  <td align="right" id="authorLabel">&nbsp;<b><label for="author">Author:</label></b></td>
    <td><input type="text" name="author" id="author" value="<?php echo htmlspecialchars($_SESSION['taxAuthor'] ?? ''); ?>"></td>
</tr><tr>
  <td align="right" id="collectorLabel">&nbsp;<b><label for="collector">Typecollection:</label></b></td>
    <td><input type="text" name="collector" id="collector" value="<?php echo htmlspecialchars($_SESSION['taxCollector'] ?? ''); ?>"></td>
  <td align="right" id="numberLabel">&nbsp;<b><label for="number">Number:</label></b></td>
    <td><input type="text" name="number" id="number" value="<?php echo htmlspecialchars($_SESSION['taxNumber'] ?? ''); ?>"></td>
  <td align="right" id="dateLabel">&nbsp;<b><label for="date">Date:</label></b></td>
    <td><input type="text" name="date" id="date" value="<?php echo htmlspecialchars($_SESSION['taxDate'] ?? ''); ?>"></td>
</tr><tr>
  <td align="right" id="annotationLabel">&nbsp;<b><label for="annotation">Annotation:</label></b></td>
    <td colspan="5"><input type="text" name="annotation" id="annotation" size="89" value="<?php echo htmlspecialchars($_SESSION['taxAnnotation'] ?? ''); ?>"></td>
</tr><tr>
  <td align="right" id="annotationLabel">&nbsp;<b><label for="commonname">Common Name:</label></b></td>
    <td colspan="5"><input type="text" name="commonname" id="commonname" size="89" value="<?php echo htmlspecialchars($_SESSION['taxCommonname'] ?? ''); ?>"></td>
</tr><tr>
  <td align="left" colspan="3"><input class="button" type="submit" name="search" value=" search "></td>
  <td align="right" id="externalLabel" colspan="2">&nbsp;<b><label for="external">external:</label></b></td>
  <td align="left" valign="top">
    <input style="float:left" type="checkbox" name="external" id="external"<?php echo (!empty($_SESSION['taxExternal'])) ? ' checked' : ''; ?>>
    <span style="float:right">
      <label><b>no Literature:</b> <input type="checkbox" name="noLiterature" value="1" id="noLiterature"<?php echo $_SESSION['noLiterature']?' checked':''; ?>></label>
    </span>
  </td>
</tr>

</table>

</form>

<table><tr>
<?php if (($_SESSION['editControl'] & 0x1)!=0): ?>
<td>
  <input class="button" type="button" value="new entry" onClick="self.location.href='editSpecies.php'">
</td><td style="width: 3em">&nbsp;</td>
<?php endif; ?>
<td>
  <form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST">
    <label>
      <b>taxonID:</b> <input type="text" name="taxon" id="taxon" value="<?php echo htmlspecialchars(($_SESSION['taxon_list'])); ?>">
    </label>
    <input class="button" type="submit" name="select" value=" Edit ">
  </form>
</td></tr></table>
<p>

<?php

// BP: if MDLD-search-field contains at least one char,
// BP: ignore all other fields and make an MDLD-search
if ($_SESSION['taxMDLD'] != "") {   // list MDLD search results
    $mdldResult = showMatchJsonRPCClickable($_SESSION['taxMDLD'],$nrSel);   // get MDLD-result
    $html_out = $mdldResult['html'];                        // formatted HTML-output
    //$html_out .= dumpMatchJsonRPC($_SESSION['taxMDLD']);  // dump result (for testing only)
    echo $html_out;                                         // print the output
} elseif ($_SESSION['taxType'] == 1) {  // list Family
    $sql = "SELECT tf.familyID, tf.family, tsc.cat_description
            FROM tbl_tax_families tf
             LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID = tsc.categoryID "
         . (($_SESSION['taxExternal']) ? "WHERE tf.external > 0 " : "WHERE tf.external = 0 ");
    if (trim($_SESSION['taxFamily'])) {
        $sql .= "AND family LIKE '" . $db->real_escape_string($_SESSION['taxFamily']) . "%' ";
    }
    $sql .= "ORDER BY " . $_SESSION['taxOrder'] . " LIMIT 1001";
    $result = $db->queryCatch($sql);
    if ($result->num_rows > 1000) {
        echo "<b>no more than 1000 results allowed</b>\n";
    } elseif ($result->num_rows > 0) {
        echo "<table class=\"out\" cellspacing=\"0\">\n";
        echo "<tr class=\"out\">";
        echo "<th class=\"out\">"
           . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=ac\">Category</a>" . $display->sortItem($_SESSION['taxOrTyp'], 11) . "</th>";
        echo "<th class=\"out\">"
           . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=af\">Family</a>" . $display->sortItem($_SESSION['taxOrTyp'], 12) . "</th>";
        echo "</tr>\n";
        while ($row = $result->fetch_array()) {
            echo "<tr class=\"out\"><td class=\"out\">";
            echo "<a href=\"javascript:editFamily(" . $row['familyID'] . ")\">";
            echo $row['cat_description'];
            echo "</a></td><td class=\"out\">";
            echo "<a href=\"javascript:editFamily(" . $row['familyID'] . ")\">";
            echo $row['family'];
            echo "</a></td></tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<b>nothing found!</b>\n";
    }
} elseif ($_SESSION['taxType'] == 2) {  // list Genus
    $sql = "SELECT tg.genID, tg.genus, tag.author auth_g, tf.family,
             tsc.cat_description, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs
            FROM tbl_tax_genera tg
             LEFT JOIN tbl_tax_authors tag ON tag.authorID=tg.authorID
             LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID
             LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID=tsc.categoryID
             ";
    // taxon for this genus....
    if ($_SESSION['noLiterature']) {
            $sql .= "LEFT JOIN herbarinput.tbl_tax_species ts ON (
                        ts.genID = tg.genID AND ts.speciesID is NULL
                        AND ts.subspeciesID IS NULL AND ts.varietyID IS NULL
                        AND ts.subvarietyID IS NULL AND ts.formaID IS NULL AND ts.subformaID IS NULL
                     )";
    }
    $sql .= "WHERE genus LIKE '" . $db->real_escape_string($_SESSION['taxGenus']) . "%' "
         . (($_SESSION['taxExternal']) ? "AND tg.external > 0 " : "AND tg.external = 0 ");
    if ($_SESSION['taxFamily']) {
        $sql .= "AND family LIKE '" . $db->real_escape_string($_SESSION['taxFamily']) . "%' ";
    }

    if ($_SESSION['noLiterature']) {
            $sql .= " AND ( $NOLITERATURE_SQL_STATEMENT  ) ";
    }

    if ($_SESSION['taxAnnotation']) {
        $sql .= "AND tg.remarks LIKE '%" . $db->real_escape_string($_SESSION['taxAnnotation']) . "%' ";
    }

    $sql .= "ORDER BY ".$_SESSION['taxOrder']." LIMIT 1001";


    $result = $db->queryCatch($sql);
    if ($result->num_rows > 1000) {
        echo "<b>no more than 1000 results allowed</b>\n";
    } elseif ($result->num_rows > 0) {
        echo "<table class=\"out\" cellspacing=\"0\">\n";
        echo "<tr class=\"out\">";
        echo "<th class=\"out\">"
           . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=bg\">Genus</a>" . $display->sortItem($_SESSION['taxOrTyp'], 21) . "</th>";
        echo "<th class=\"out\">Author</th>";
        echo "<th class=\"out\">RefNo</th>";
        echo "<th class=\"out\">"
           . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=bf\">Family</a>" . $display->sortItem($_SESSION['taxOrTyp'], 22) . "</th>";
        echo "<th class=\"out\">Category</th></tr>\n";
        while ($row = $result->fetch_array()) {
            echo "<tr class=\"out\"><td class=\"out\">";
            echo "<a href=\"javascript:editGenera(" . $row['genID'] . ")\">";
            echo $row['genus'];
            echo "</a></td><td class=\"out\">";
            echo "<a href=\"javascript:editGenera(" . $row['genID'] . ")\">";
            echo $row['auth_g'];
            echo "</a></td><td class=\"out\">";
            echo $row['DallaTorreIDs'] . $row['DallaTorreZusatzIDs'];
            echo "</td><td class=\"out\">";
            echo "<a href=\"javascript:editGenera(" . $row['genID'] . ")\">";
            echo $row['family'];
            echo "</a></td><td class=\"out\">";
            echo "<a href=\"javascript:editGenera(" . $row['genID'] . ")\">";
            echo $row['cat_description'];
            echo "</a></td></tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<b>nothing found!</b>\n";
    }
} elseif ($_SESSION['taxType'] == 3) {  // list Species
    echo prettyPrintSynonymLinks();
?>
      <input type="button" class="button" value="make PDF (Scientific Names)" id="btMakeScientificNameLabelPdf" onClick="showPDF('scientificName')">
      <input type="button" class="button" value="clear all Labels" id="btClearScientificNameLabels" onClick="Jacq.Jaxon.ListTaxServer.clearScientificNameLabels(); return false;">
      <input type="button" class="button" value="set all" id="btSetAllLabels" onClick="Jacq.Jaxon.ListTaxServer.setAll(); return false;">
      <input type="button" class="button" value="clear all" id="btClearAllLabels" onClick="Jacq.Jaxon.ListTaxServer.clearAll(); return false;">
      <p>
<?php
    $sql = "SELECT ts.taxonID, ts.statusID, tg.genus, tag.author auth_g, tf.family, tr.rank_abbr, l.nr,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3, ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5
            FROM tbl_tax_species ts
             LEFT JOIN tbl_labels_scientificName l ON (ts.taxonID = l.taxonID AND l.userID = '" . intval($_SESSION['uid']) . "')
             LEFT JOIN tbl_tax_authors ta ON ta.authorID = ts.authorID
             LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
             LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
             LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
             LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
             LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
             LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
             LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
             LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
             LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
             LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
             LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
             LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
             LEFT JOIN tbl_tax_authors tag ON tag.authorID = tg.authorID
             LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID
             LEFT JOIN tbl_tax_rank tr ON tr.tax_rankID = ts.tax_rankID ";

    if (!empty($_SESSION['taxon_list'])) {
        $sql .= " where ts.taxonID in (" . $db->real_escape_string($_SESSION['taxon_list']) . ")";
    } else {
        $sql .= (($_SESSION['taxExternal']) ? "WHERE ts.external > 0 " : "WHERE ts.external = 0 ");
        if ($_SESSION['taxStatus'] != "everything") {
            if ($_SESSION['taxSpecies'] && $_SESSION['taxSpecies'] != '-') {
                $sql .= "AND (te.epithet LIKE '" . $db->real_escape_string($_SESSION['taxSpecies']) . "%'
                          OR te1.epithet LIKE '" . $db->real_escape_string($_SESSION['taxSpecies']) . "%'
                          OR te2.epithet LIKE '" . $db->real_escape_string($_SESSION['taxSpecies']) . "%'
                          OR te3.epithet LIKE '" . $db->real_escape_string($_SESSION['taxSpecies']) . "%'
                          OR te4.epithet LIKE '" . $db->real_escape_string($_SESSION['taxSpecies']) . "%'
                          OR te5.epithet LIKE '" . $db->real_escape_string($_SESSION['taxSpecies']) . "%') ";
            } else {
                $sql .= "AND te.epithet IS NULL ";
            }
            if ($_SESSION['taxStatus']) {
                $sql .= "AND ts.statusID=" . Tools::extractID($_SESSION['taxStatus']) . " ";
            }
        }
        if ($_SESSION['taxRank']) {
            $sql .= "AND ts.tax_rankID=" . Tools::extractID($_SESSION['taxRank']) . " ";
        }
        if ($_SESSION['noLiterature']) {
            $sql .= " AND ( $NOLITERATURE_SQL_STATEMENT  ) ";
        }

        if ($_SESSION['taxFamily']) {
            $sql .= "AND family LIKE '" . $db->real_escape_string($_SESSION['taxFamily']) . "%' ";
        }
        if ($_SESSION['taxGenus']) {
            $sql .= "AND genus LIKE '" . $db->real_escape_string($_SESSION['taxGenus']) . "%' ";
        }
        if ($_SESSION['taxAuthor']) {
            $sql .= "AND (ta.author LIKE '%" . $db->real_escape_string($_SESSION['taxAuthor']) . "%'
                      OR ta1.author LIKE '%" . $db->real_escape_string($_SESSION['taxAuthor']) . "%'
                      OR ta2.author LIKE '%" . $db->real_escape_string($_SESSION['taxAuthor']) . "%'
                      OR ta3.author LIKE '%" . $db->real_escape_string($_SESSION['taxAuthor']) . "%'
                      OR ta4.author LIKE '%" . $db->real_escape_string($_SESSION['taxAuthor']) . "%'
                      OR ta5.author LIKE '%" . $db->real_escape_string($_SESSION['taxAuthor']) . "%') ";
        }
        if ($_SESSION['taxAnnotation']) {
            $sql .= "AND ts.annotation LIKE '%" . $db->real_escape_string($_SESSION['taxAnnotation']) . "%' ";
        }
    }
    $sql .= " ORDER BY " . $_SESSION['taxOrder'] . " LIMIT 1001";
    error_log($sql);
    $_SESSION['labelTaxSQL'] = $sql;
    $result = $db->queryCatch($sql);
    if ($result->num_rows > 1000) {
        echo "<b>no more than 1000 results allowed</b>\n";
    } elseif ($result->num_rows > 0) {
        echo "<table class='out' cellspacing='0'>\n"
           . "<tr class='out'>"
           . "<th class='out'>ID</th>"
           . "<th class='out'><a href='" . $_SERVER['PHP_SELF'] . "?order=cr'>Tax.Rank</a>" . $display->sortItem($_SESSION['taxOrTyp'], 34) . "</th>"
           . "<th class='out'><a href='" . $_SERVER['PHP_SELF'] . "?order=cf'>Family</a>" . $display->sortItem($_SESSION['taxOrTyp'], 32) . "</th>"
           . "<th class='out'>acc.</th>"
           . "<th class='out'><a href='" . $_SERVER['PHP_SELF'] . "?order=cg'>Genus</a>" . $display->sortItem($_SESSION['taxOrTyp'], 31) . "</th>"
           . "<th class='out'>Author</th>"
           . "<th class='out'><a href='" . $_SERVER['PHP_SELF'] . "?order=cs'>Species</a>" . $display->sortItem($_SESSION['taxOrTyp'], 33) . "</th>"
           . "<th class='out'>Author</th>"
           . "<th class='out'>infraspecific Taxon</th>"
           . "<th class='out'>Label</th>"
           . "</tr>\n";
        $nr = 1;
        while ($row = $result->fetch_array()) {
            $linkList[$nr] = $id = $row['taxonID'];
            echo "<tr class='" . (($nrSel == $nr) ? "outMark" : "out") . "'>"
               . "<td class='out' style='text-align:right'>"
               .   "<a href='editSpecies.php?sel=" . htmlspecialchars("<" . $row['taxonID'] . ">") . "&nr=$nr'>" . $row['taxonID'] . "</a></td>"
               . "<td style='text-align: center;' class='out'>{$row['rank_abbr']}</td>"
               . "<td class='out'>"
               .   "<a href='editSpecies.php?sel=" . htmlspecialchars("<" . $row['taxonID'] . ">") . "&nr=$nr'>" . $row['family'] . "</a></td>"
               . "<td style='text-align: center;' class='out'>" . (($row['statusID'] == 96) ? "&bull;" : "") . "</td>"
               . "<td class='out'>"
               .   "<a href='editSpecies.php?sel=" . htmlspecialchars("<" . $row['taxonID'] . ">") . "&nr=$nr'>" . $row['genus'] . "</a></td>"
               . "<td class='out'>"
               .   "<a href='editSpecies.php?sel=" . htmlspecialchars("<" . $row['taxonID'] . ">") . "&nr=$nr'>" . $row['auth_g'] . "</a></td>"
               . "<td class='out'>"
               .   "<a href='editSpecies.php?sel=" . htmlspecialchars("<" . $row['taxonID'] . ">") . "&nr=$nr'>" . $row['epithet'] . "</a></td>"
               . "<td class='out'>"
               .   "<a href='editSpecies.php?sel=" . htmlspecialchars("<" . $row['taxonID'] . ">") . "&nr=$nr'>" . $row['author'] . "</a></td>"
               . "<td class='out'>"
               .   "<a href='editSpecies.php?sel=" . htmlspecialchars("<" . $row['taxonID'] . ">") . "&nr=$nr'>" . getSubTaxonItem($row) . "</a></td>";
            echo "<td class='outCenter'>"
               .   "<input style='width: 1em;' type='text' id='inpScientificNameLabel_$id' maxlength='2' "
               .     "value='" . intval($row['nr']) . "' onChange=\"Jacq.Jaxon.ListTaxServer.updateScientificNameLabel('$id', $('#inpScientificNameLabel_$id').val());\">"
               . "</td></tr>\n";
            $hybrids = getHybrids($row['taxonID']);
            if (strlen($hybrids) > 0) {
                echo "<tr><td class=\"out\" colspan='10'>";
                echo "$hybrids\n";
                echo "</td></tr>\n";
            }
            $nr++;
        }
        $linkList[0] = $nr - 1;
        $_SESSION['txLinkList'] = $linkList;
        echo "</table>\n";
    } else {
        echo "<b>nothing found!</b>\n";
    }
} else if ($_SESSION['taxType'] == 4) {  // list Types
    $sql = "SELECT ts.taxonID, tg.genus,
             tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, tag.author author_g,
             tf.family, tsc.category,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3, ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5,
             tc.Sammler, tc2.Sammler_2, tt.series, tt.leg_nr, tt.alternate_number, tt.date, tt.duplicates
            FROM tbl_tax_species ts
             LEFT JOIN tbl_tax_authors ta ON ta.authorID = ts.authorID
             LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
             LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
             LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
             LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
             LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
             LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
             LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
             LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
             LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
             LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
             LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
             LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
             LEFT JOIN tbl_tax_authors tag ON tag.authorID = tg.authorID
             LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID
             LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID = tsc.categoryID
             LEFT JOIN tbl_tax_typecollections tt ON tt.taxonID = ts.taxonID
             LEFT JOIN tbl_collector tc ON tc.SammlerID = tt.SammlerID
             LEFT JOIN tbl_collector_2 tc2 ON tc2.Sammler_2ID = tt.Sammler_2ID "
         . (($_SESSION['taxExternal']) ? "WHERE ts.external > 0 " : "WHERE ts.external = 0 ");
    if ($_SESSION['taxDate']) {
        $sql .= "AND tt.date LIKE '" . $db->real_escape_string($_SESSION['taxDate']) . "%' ";
    }
    if ($_SESSION['taxNumber']) {
        $sql .= "AND tt.leg_nr='" . $db->real_escape_string($_SESSION['taxNumber']) . "' ";
    }
    if ($_SESSION['taxCollector'])
        $sql .= "AND (   tc.Sammler LIKE '" . $db->real_escape_string($_SESSION['taxCollector']) . "%' "
              . "     OR tc2.Sammler_2 LIKE '" . $db->real_escape_string($_SESSION['taxCollector']) . "%') ";
    if ($_SESSION['taxStatus'] != "everything") {
        if ($_SESSION['taxSpecies']) {
            $sql .= "AND te.epithet LIKE '" . $db->real_escape_string($_SESSION['taxSpecies']) . "%' ";
        } else {
            $sql .= "AND te.epithet IS NULL ";
        }
        if ($_SESSION['taxStatus']) {
            $sql .= "AND ts.statusID=" . Tools::extractID($_SESSION['taxStatus']) . " ";
        }
    }
    if ($_SESSION['taxRank']) {
        $sql .= "AND ts.tax_rankID=" . Tools::extractID($_SESSION['taxRank']) . " ";
    }
    if ($_SESSION['taxFamily']) {
        $sql .= "AND family LIKE '" . $db->real_escape_string($_SESSION['taxFamily']) . "%' ";
    }
    if ($_SESSION['taxGenus']) {
        $sql .= "AND genus LIKE '" . $db->real_escape_string($_SESSION['taxGenus']) . "%' ";
    }
    if ($_SESSION['taxAnnotation']) {
        $sql .= "AND ts.annotation LIKE '%" . $db->real_escape_string($_SESSION['taxAnnotation']) . "%' ";
    }
    if ($_SESSION['noLiterature']) {
        $sql .= " AND ( $NOLITERATURE_SQL_STATEMENT ) ";
    }
    $sql .= "ORDER BY " . $_SESSION['taxOrder'] . " LIMIT 1001";
    $result = $db->queryCatch($sql);
    if ($result->num_rows > 1000) {
        echo "<b>no more than 1000 results allowed</b>\n";
    } elseif ($result->num_rows > 0) {
        echo "<table class=\"out\" cellspacing=\"0\">\n";
        echo "<tr class=\"out\">";
        echo "<th class=\"out\">".
             "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=da\">Type</a>" . $display->sortItem($_SESSION['taxOrTyp'], 41) . "</th>";
        echo "<th class=\"out\">".
             "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=db\">Taxon</a>" . $display->sortItem($_SESSION['taxOrTyp'], 42) . "</th>";
        echo "</tr>\n";
        $nr = 1;
        while ($row = $result->fetch_array()) {
            echo "<tr class=\"" . (($nrSel == $nr) ? "outMark" : "out") . "\"><td class=\"out\">";
            echo "<a href=\"editSpecies.php?sel=" . htmlspecialchars("<" . $row['taxonID'] . ">") . "&nr=$nr\">";
            echo htmlspecialchars(typusItem($row));
            echo "</a></td><td class=\"out\">";
            echo "<a href=\"editSpecies.php?sel=" . htmlspecialchars("<" . $row['taxonID'] . ">") . "&nr=$nr\">";
            echo htmlspecialchars(taxonWithFamily($row));
            echo "</a></td></tr>\n";
            $nr++;
        }
        echo "</table>\n";
    } else {
        echo "<b>nothing found!</b>\n";
    }
} else if ($_SESSION['taxType'] == 5) {  // list common names with different interface
    echo prettyPrintSynonymLinks();

    $start = microtime(true);

    $searchtext = strtolower(trim($_SESSION['taxCommonname']));

    $settings = Settings::Load();
    $service = new jsonRPCClient($settings->get('serviceTaxamatch'));
    try {

        $matches = $service->getMatchesService('vienna_common',$searchtext,array('showSyn'=>false,'NearMatch'=>false));
        $stop = microtime(true);
        /*if (!empty($matches['error'])) {
            $out = $matches['error'];
        } else */{

            $s[0] = $display->sortItem($_SESSION['taxOrTyp'], 51);
            $s[1] = $display->sortItem($_SESSION['taxOrTyp'], 52);
            $s[2] = $display->sortItem($_SESSION['taxOrTyp'], 53);
            $s[3] = $display->sortItem($_SESSION['taxOrTyp'], 54);

            /*echo<<<EOF
<table class="out" cellspacing="0">
<tr class="out">
<th class="out"><a href="{$_SERVER['PHP_SELF']}?order=da">Taxon</a>{$s[0]}</th>
<th class="out"><a href="{$_SERVER['PHP_SELF']}?order=db">CommonName</a>{$s[1]}</th>
<th class="out"><a href="{$_SERVER['PHP_SELF']}?order=dc">Distance</a>{$s[2]}</th>
<th class="out"><a href="{$_SERVER['PHP_SELF']}?order=dd">Ratio</a>{$s[3]}</th>
</tr>
EOF;*/
				echo<<<EOF
<script type="text/javascript" src="js/lib/jQuery/jquery.tablesorter_nhm.js"></script>

<table id="sorttable" cellspacing="0" cellpadding="0" class="tablesorter" border="1" style="border: 1px solid #000;border-collapse:collapse" width="700">
<colgroup><col width="40%"><col width="40%"><col width="10%"><col width="10%"></colgroup>
<thead>
<tr>
 <th><span>Taxon</span></th><th><span>CommonName</span></th><th><span>Distance</span></th><th><span>Ratio</span></th>
</tr>
</thead>
<tbody>

EOF;
            $x=0;
            $used=array();
            $linkList=array(0);
            $indexMatch=0;
            while ($indexMatch < count($matches['result'])) {
                $countResults = count($matches['result'][$indexMatch]['searchresult']);
                $indexResult=0;
                $nr1 = 1;
                while ($indexResult < $countResults) {
                    $row = $matches['result'][$indexMatch]['searchresult'][$indexResult]['species'][0];

                    if(empty($row['taxonID']) ||(	$_SESSION['noLiterature'] && LiteratureExists($row['taxonID'])) ) {
                            $indexResult++;
                            continue;
                    }

                    if(!isset($used[$row['taxonID']])){
                        $nr=$nr1;
                        $nr1++;
                    }else{
                        $nr=$used[$row['taxonID']];
                    }

                    //$link=  "editSpecies.php?sel=".htmlspecialchars("<" . $row['taxonID'] . ">")."&nr={$nr}";

                    $ratio=number_format($row['ratio'] * 100, 1).'%';
                    $c=($x++%2 || $nrSel == $nr)?'o':'e';
                    echo<<<EOF
<tr onclick="selectID('{$row['taxonID']}','$nr')" >
<td class="$c">{$row['taxon']} {$row['taxonID']}</td><td class="$c"">{$row['commonName']}</td><td class="$c">{$row['distance']}</td><td class="$c">$ratio</td>
</tr>
EOF;
                    if(!isset($used[$row['taxonID']])){
                        $used[$row['taxonID']]=$nr;
                        // save current taxonID for "editSpecies.php" and "listSynonyms.php"
                        $linkList[$nr] = $row['taxonID'];
                        $nr++;
                    }
                    $indexResult++;
                }
                $indexMatch++;
            }
            echo<<<EOF
</tbody>
</table>
<script>
$(function(){
	$("#sorttable tbody tr").hover(
		function(){ $(this).find('td').css('background-color','#ffff99');},
		function(){ $(this).find('td').css('background-color','');}
	);
	$("#sorttable").tablesorter();
});

function selectID(taxonID,nr){
	document.location.href="editSpecies.php?sel=<"+taxonID+">&nr="+nr;
}

</script>

EOF;
            $linkList[0] = $nr1 - 1;
            $_SESSION['txLinkList'] = $linkList;
        }
    }catch (Exception $e) {
        $out =  "Fehler " . nl2br($e);
    }
}
?>

</body>
</html>
