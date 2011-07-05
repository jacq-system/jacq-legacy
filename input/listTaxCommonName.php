<?php
session_start();
require("inc/connect.php");
require("inc/herbardb_input_functions.php");

// BP: for MDLD-JSON service
require_once('inc/variables.php');
//require_once("inc/xajax/xajax_core/xajax.inc.php");
require_once('inc/jsonRPCClient.php');

no_magic();
$nrSel = (!empty($_GET['nr'])) ? intval($_GET['nr']) : 0;

_logger("---- listTax.php (nrSel = " . $nrSel . " ---");

if (!isset($_SESSION['taxStatus'])) $_SESSION['taxStatus'] = "";
if (!isset($_SESSION['taxRank']))   $_SESSION['taxRank'] = "";
if (!isset($_SESSION['taxMDLD']))   $_SESSION['taxMDLD'] = ""; // BP

if (isset($_POST['search'])) {
    $_SESSION['taxMDLD'] = $_POST['mdld'];  // BP
    if ($_SESSION['editFamily']) $_POST['family'] = $_SESSION['editFamily'];
    if ($_POST['collector'] || $_POST['number'] || $_POST['date']) {
        $_SESSION['taxType']       = 4; // list Species, other display
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
        $_SESSION['taxExternal']   = $_POST['external'];
        $_SESSION['taxOrder']      = "Sammler, Sammler_2, series, leg_nr, tt.date";
        $_SESSION['taxOrTyp']      = 41;
    } else if ($_POST['species'] || $_POST['status']){
        $_SESSION['taxType']       = 3; // list Species
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
        $_SESSION['taxExternal']   = $_POST['external'];
        $_SESSION['taxOrder']      = "genus, auth_g, family, epithet, author, epithet1, author1, "
                                   . "epithet2, author2, epithet3, author3, epithet4, author4, epithet5, author5";
        $_SESSION['taxOrTyp']      = 31;
    } else if ($_POST['genus']) {
        $_SESSION['taxType']       = 2; // list Genus
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
        $_SESSION['taxExternal']   = $_POST['external'];
        $_SESSION['taxOrder']      = "genus, auth_g, family";
        $_SESSION['taxOrTyp']      = 21;
    } else {
        $_SESSION['taxType']       = 1; // list Family
        $_SESSION['taxFamily']     = $_POST['family'];
        $_SESSION['taxGenus']      = "";
        $_SESSION['taxSpecies']    = "";
        $_SESSION['taxStatus']     = "";
        $_SESSION['taxCollector']  = "";
        $_SESSION['taxNumber']     = "";
        $_SESSION['taxDate']       = "";
        $_SESSION['taxAuthor']     = "";
        $_SESSION['taxAnnotation'] = "";
        $_SESSION['taxExternal']   = $_POST['external'];
        $_SESSION['taxOrder']      = "category, family";
        $_SESSION['taxOrTyp']      = 11;
    }
} else if (isset($_GET['lfamily'])) {
    $_SESSION['taxType']       = 1; // list Family
    $_SESSION['taxFamily']     = $_GET['lfamily'];
    $_SESSION['taxGenus']      = "";
    $_SESSION['taxSpecies']    = "";
    $_SESSION['taxStatus']     = "";
    $_SESSION['taxRank']       = "";
    $_SESSION['taxCollector']  = "";
    $_SESSION['taxNumber']     = "";
    $_SESSION['taxDate']       = "";
    $_SESSION['taxAuthor']     = "";
    $_SESSION['taxAnnotation'] = "";
    $_SESSION['taxExternal']   = "";
    $_SESSION['taxOrder']      = "category, family";
    $_SESSION['taxOrTyp']      = 11;
} else if (isset($_GET['lgenus'])) {
    $_SESSION['taxType']       = 2; // list Genus
    $_SESSION['taxFamily']     = ($_SESSION['editFamily']) ? $_SESSION['editFamily'] : "";
    $_SESSION['taxGenus']      = $_GET['lgenus'];
    $_SESSION['taxSpecies']    = "";
    $_SESSION['taxStatus']     = "";
    $_SESSION['taxRank']       = "";
    $_SESSION['taxCollector']  = "";
    $_SESSION['taxNumber']     = "";
    $_SESSION['taxDate']       = "";
    $_SESSION['taxAuthor']     = "";
    $_SESSION['taxAnnotation'] = "";
    $_SESSION['taxExternal']   = "";
    $_SESSION['taxOrder']      = "genus, auth_g, family";
    $_SESSION['taxOrTyp']      = 21;
} else if (isset($_GET['order'])) {
    if ($_SESSION['taxType'] == 4) { // list Species, other display
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
    } else if ($_SESSION['taxType'] == 3) { // list Species
        if ($_GET['order'] == "cs") {
            $_SESSION['taxOrder'] = "epithet, genus, auth_g, family, author, epithet1, author1, "
                                  . "epithet2, author2, epithet3, author3, epithet4, author4, epithet5, author5";
            if ($_SESSION['taxOrTyp'] == 33) {
                $_SESSION['taxOrTyp'] = -33;
            } else {
                $_SESSION['taxOrTyp'] = 33;
            }
        } else if ($_GET['order'] == "cf") {
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
    } else if ($_SESSION['taxType'] == 2) { // list Genus
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
    if ($_SESSION['taxOrTyp']<0) $_SESSION['taxOrder'] = implode(" DESC, ",explode(", ",$_SESSION['taxOrder']))." DESC";
}

if (!empty($_POST['select']) && !empty($_POST['taxon'])) {
    /*$location="Location: editSpecies.php?sel=<" . $_POST['taxon'] . ">";
    if (SID != "") $location .= "?" . SID;
    Header($location);*/
	echo <<<EOF
<scrip>
selectTaxon('{$_POST['taxon']}');
</script>
EOF;
}

// BP: logger: only log if set in variables.php
function _logger($message, $message_type = 0)
{
    global $_OPTIONS;

    if ($_OPTIONS['debug'])
        error_log($message, $message_type);
}

// BP: placed into a function because needed at various locations now
function prettyPrintSynonymLinks()
{
    $out = "<td style=\"width:20px\">&nbsp;</td>"
       . "<td><a href=\"javascript:listSynonyms(0,0)\">list synonyms (long)</a></td>"
       . "<td style=\"width:20px\">&nbsp;</td>"
       . "<td><a href=\"javascript:listSynonyms(1,0)\">list synonyms (short)</a></td>"
       . "<td style=\"width:20px\">&nbsp;</td>"
       . "<td><a href=\"javascript:listSynonyms(1,1)\">list names alphabetically</a></td>";
   return $out;
}

// BP: for testing only!
function dumpMatchJsonRPC($searchtext)
{
    global $_OPTIONS;

    $searchtext = ucfirst(trim($searchtext));
    if (substr($searchtext, 0, 3) == chr(0xef) . chr(0xbb) . chr(0xbf)) $searchtext = substr($searchtext, 3);

    $service = new jsonRPCClient($_OPTIONS['serviceTaxamatch']);
    _logger("URL = " . $_OPTIONS['serviceTaxamatch'],0);

    try {
        $matches = $service->getMatches($searchtext);
        if (!empty($formData['nearmatch'])) {
            $matchesNearMatch = $service->getMatches($searchtext, true);
        } else {
            $matchesNearMatch = array();
        }

        $out = "<big><b>Dump or Results for search for '" . nl2br($searchtext) . "':</b></big><br>\n"
             . "<pre>" . var_export($matches, true) . "\n" . var_export($matchesNearMatch, true) . "</pre>\n";
    }
    catch (Exception $e) {
        $out =  "Fehler " . nl2br($e);
    }

    return $out;
}

// BP: convert JSON-MDLD-result into a formatted HTML-string
// BP: for some reason, can't access global $nrSel in this function
// BP: ==> pass it as a parameter & find out why it didn't work!
// BP: returns formatted HTML-string containing MDLD-result and content for field "taxonID"
function showMatchJsonRPCClickable($searchtext, $selectedRow=1,$useNearMatch=false)
{
    _logger("listTax - showMatchJsonRPCClickable(selectedRow=" . $selectedRow . ")");

    global $_OPTIONS;

    $start = microtime(true);

    $searchtext = ucfirst(trim($searchtext));
    if (substr($searchtext, 0, 3) == chr(0xef) . chr(0xbb) . chr(0xbf)) $searchtext = substr($searchtext, 3);

    $service = new jsonRPCClient($_OPTIONS['serviceTaxamatch']);
    _logger("URL = " . $_OPTIONS['serviceTaxamatch'],0);
    try {
        $matches = $service->getMatches($searchtext);
        if ($useNearMatch) {
            $matchesNearMatch = $service->getMatches($searchtext, true);
        } else {
            $matchesNearMatch = array();
        }

        $stop = microtime(true);

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
            print_r($matches);
			
			// Get TaxonIds...
			$ids='';
			foreach($matches['result'] as $rows){
				foreach($rows['searchresult'] as $row){
					$ids.=",'{$row['ID']}'";
				}
			}
			$ids=substr($ids,1);
			$taxon_ids=array();
			
			// taken from editSpecies.php
			$sql = "
SELECT
 genID,taxonID
FROM
 tbl_tax_species sp
WHERE
     sp.speciesID IS NULL
 AND sp.subspeciesID IS NULL AND sp.subspecies_authorID IS NULL
 AND sp.varietyID IS NULL AND sp.variety_authorID IS NULL
 AND sp.subvarietyID IS NULL AND sp.subvariety_authorID IS NULL
 AND sp.formaID IS NULL AND sp.forma_authorID IS NULL
 AND sp.subformaID IS NULL AND sp.subforma_authorID IS NULL
 AND genID IN ({$ids})
";

			$result = db_query($sql);
			while ($row = mysql_fetch_array($result)) {
                $taxon_ids[$row['genID']]=$row['taxonID'];
			}
			
			print_r($taxon_ids);
			
			
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
                        if ($indexResult < $countResults) {
                            $row = $matches['result'][$indexMatch]['searchresult'][$indexResult];
                            $found++;
                            // uninomial --> link to "editGenera.php"
                            $newLink = "<a href=\"javascript:selectTaxon('" . $taxon_ids[$row['ID']] . "')\">";
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
                            if ($indexResult < $countResultsNearMatch) {
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
                                $nr++;
                                if (($matchingTaxonID == "") || ($nr == $selectedRow)) {
                                    $matchingTaxonID = $row2['taxonID'];
                                }
                                $found++;
                                // contains taxonID --> link to "editSpecis.php"
                                $newLink = "<a href=\"javascript:selectTaxon('{$row2['taxonID']}')\">";
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
                            }
                        }
                        if ($indexResult < $countResultsNearMatch) {
                            $row = $matchesNearMatch['result'][$indexMatch]['searchresult'][$indexResult];
                            foreach ($row['species'] as $key2 => $row2) {
                                $columnRight[] = '<td>&nbsp;&nbsp;<b>' . $row2['taxon'] . ' <' . $row2['taxonID'] . '></b>'
                                               . (($row2['syn']) ? "<br>&nbsp;&nbsp;&rarr;&nbsp;" . $row2['syn'] . " <" . $row2['synID'] . ">" : "")
                                               . '</td>'
                                               . '<td>&nbsp;' . $row2['distance'] . '&nbsp;</td>'
                                               . '<td align="right">&nbsp;' . number_format($row2['ratio'] * 100, 1) . "%</td>";
                                $foundNearMatch++;
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
                      . "&nbsp;&nbsp;<big><b>" . $matches['result'][$indexMatch]['searchtext'] . "</b></big>&nbsp;&nbsp;<br>\n"
                      . "&nbsp;&nbsp;$found match" . (($found > 1) ? 'es' : '') . " found&nbsp;&nbsp;<br>\n"
                      . "&nbsp;&nbsp;" . $matches['result'][$indexMatch]['rowsChecked'] . " rows checked&nbsp;&nbsp;"
                      . "</td>"
                      . $out2Firstline[0];
                if ($useNearMatch) {
                      $out .= "<td rowspan='$line'>"
                            . "&nbsp;&nbsp;<big><b>" . $matchesNearMatch['result'][$indexMatch]['searchtextNearmatch'] . "</b></big>&nbsp;&nbsp;<br>\n"
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

    // decide what to return for field "taxonID"
    if ($matchingTaxonID == "") {
        $matchingTaxonID = findFirstTaxonID($matches);
    }
    $outArray = array('html'    => $out,
                      'id'      => $matchingTaxonID);

    // for synonyms and editSpecies.php
    $linkList[0] = $numSyn - 1;
    $_SESSION['txLinkList'] = $linkList;

    return $outArray;
}


// BP: finds the ID to display in the field taxonID
// BP: if there is only a genus, take 'ID' of genus
// BP: if there are species: take the 'taxonID' of the first species
function findFirstTaxonID($matches) {
    $firstID = "0";
    if($matches['result'][0]['type'] == "multi") {
        // we have species ==> take 'taxonID' of first species
        // actually, if type==multi this function should not even be called...
        // but that's ok, it doesn't hurt to keep this branch!
        $firstID = $matches['result'][0]['searchresult'][0]['species'][0]['taxonID'];
    } else {
        // no species ==> take 'ID' of genus
        $firstID = $matches['result'][0]['searchresult'][0]['ID'];
    }
    return $firstID;
}
// BP: END


function typusItem($row)
{
    $text = $row['Sammler'];
    if ($row['Sammler_2']) {
        if (strstr($row['Sammler_2'],"&") === false) {
            $text .= " & " . $row['Sammler_2'];
        } else {
            $text .= " et al.";
        }
    }
    if ($row['series']) $text .= " " . $row['series'];
    if ($row['leg_nr']) $text .= " " . $row['leg_nr'];
    if ($row['alternate_number']) {
        $text .= " " . $row['alternate_number'];
        if (strstr($row['alternate_number'], "s.n.") !== false) {
            $text .= " [" . $row['date'] . "]";
        }
    }
    $text .= "; " . $row['duplicates'];

    return $text;
}


function makeTaxon($taxonID)
{
    $sql = "SELECT ts.taxonID, tg.genus,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
             ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
             te4.epithet epithet4, te5.epithet epithet5
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
             LEFT JOIN tbl_tax_status tst ON tst.statusID = ts.statusID
             LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
             LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID
            WHERE taxonID = '$taxonID'";
    $row = mysql_fetch_array(db_query($sql));
    $result = taxon($row);
    $result = preg_replace("/ [\s]+/", " ", $result);

    return $result;
}


function getHybrids($taxonID)
{
    $text = "";

    $sql = "SELECT parent_1_ID, parent_2_ID
            FROM tbl_tax_hybrids
            WHERE taxon_ID_fk = '$taxonID'";
    $result = db_query($sql);
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
        $text .= makeTaxon($row['parent_1_ID']) . " x " . makeTaxon($row['parent_2_ID']) . "<br>\n";
    }

    $sql = "SELECT parent_1_ID, parent_2_ID
            FROM tbl_tax_hybrids
            WHERE parent_1_ID = '$taxonID'";
    $result = db_query($sql);
    while ($row = mysql_fetch_array($result)) {
        $text .= makeTaxon($row['parent_1_ID']) . " x " . makeTaxon($row['parent_2_ID']) . "<br>\n";
    }

    $sql = "SELECT parent_1_ID, parent_2_ID
            FROM tbl_tax_hybrids
            WHERE parent_2_ID = '$taxonID'";
    $result = db_query($sql);
    while ($row = mysql_fetch_array($result)) {
        $text .= makeTaxon($row['parent_1_ID']) . " x " . makeTaxon($row['parent_2_ID']) . "<br>\n";
    }

    return $text;
}


function makeDropdown($name,$select,$value,$text)
{
    // BP: add tag "id" so I can access it with jQuery for en/disabling!
    echo "<select name=\"$name\" id=\"$name\">\n";
    for ($i = 0; $i < count($value); $i++) {
        echo "  <option";
        if ($value[$i] != $text[$i]) echo " value=\"" . $value[$i] . "\"";
        if ($select == $value[$i]) print " selected";
        echo ">" . htmlspecialchars($text[$i]) . "</option>\n";
    }
    echo "</select>\n";
}


function taxonWithFamily($row)
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


unset($status);
$status[] = "";
$status[] = "everything";
$sql = "SELECT status, statusID FROM tbl_tax_status ORDER BY status";
if ($result = db_query($sql)) {
    if (mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            $status[] = $row['status'] . " <" . $row['statusID'] . ">";
        }
    }
}

unset($rank);
$rank[] = "";
$sql = "SELECT rank, tax_rankID FROM tbl_tax_rank ORDER BY rank";
if ($result = db_query($sql)) {
    if (mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            $rank[] = $row['rank'] . " <" . $row['tax_rankID'] . ">";
        }
    }
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list Species</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
<script type='text/javascript' language='JavaScript'>
function selectTaxon(taxonID){
	window.opener.UpdateTaxon(taxonID);
	self.close();
}
</script>
  <!-- BP: use jQuery for disabling edit-fields if MDLD-search has been entered -->
  <script src="inc/jQuery/jquery.min.js" type="text/javascript"></script>
  <!-- BP END -->

  <script type="text/javascript" language="JavaScript">
      // BP: add. code for enabling/disabling search-fiels when MDLD-input field is empty (or not)
      var isMDLDSearch = false;         // BP: global variable indicating which fields are currently en/disabled

      var fieldsArr = ["family",        // BP: fields to be dis/enabled according to MDLD input-field
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

      var mdldField = "#mdld";          // BP: name of MDLD-input-field
      // BP: END

    function editFamily(sel) {
      target = "editFamily.php?sel=" + encodeURIComponent(sel);
      MeinFenster = window.open(target,"editFamily","width=300,height=150,top=60,left=60,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function editGenera(sel) {
      target = "editGenera.php?sel=" + encodeURIComponent(sel);
      MeinFenster = window.open(target,"editGenera","width=600,height=500,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function listSynonyms(sel,type) {
      target  = "listSynonyms.php?short=" + encodeURIComponent(sel) + "&listOnly=" + encodeURIComponent(type);
      options = "width=800,height=800,top=50,left=50,scrollbars=yes,resizable=yes";
      MeinFenster = window.open(target,"listSynonyms",options);
      MeinFenster.focus();
    }

    // BP: as soon as user enters something into MDLD-input-field:
    // BP: disable other input-fields
    // BP: when all information of MDLD-field is removed: enable them again
    $(document).ready(function() {
        checkEnableEdit = function() {
            $.each(fieldsArr, function(key,val) {
                var myLabel = "#" + val + "Label";
                var myField = "#" + val;
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

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f">
  <input type="submit" name="reload" value="" id="close">
  <input type="hidden" name="family" value="">
  <input type="hidden" name="gen" value="">
  <input class="button" type="button" value=" close window " onclick="self.close()" id="close">
</form>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST">

<table cellspacing="5" cellpadding="0">
    <!-- BP: additional field for MDLD-search -->
<tr>
    <td align="right" >&nbsp;<b>MDLD-Search:</b></td>
    <td colspan="6"><input type="text" name="mdld" id="mdld" size="89" value="<?php echoSpecial('taxMDLD', 'SESSION'); ?>"></td>
</tr>
<!-- BP: END -->

<!-- BP: added ids to all elements I need to manipulate with jQuery -->
<tr>
  <td align="right" id="familyLabel">&nbsp;<b>Family:</b></td>
    <td><input type="text" name="family" id="family" value="<?php echoSpecial('taxFamily', 'SESSION'); ?>"<?php if ($_SESSION['editFamily']) echo "disabled"; ?>></td>
  <td align="right" id="genusLabel">&nbsp;<b>Genus:</b></td>
    <td><input type="text" name="genus" id="genus" value="<?php echoSpecial('taxGenus', 'SESSION'); ?>"></td>
  <td align="right" id="speciesLabel">&nbsp;<b>Species:</b></td>
    <td><input type="text" name="species" id="species" value="<?php echoSpecial('taxSpecies', 'SESSION'); ?>"></td>
</tr><tr>
  <td align="right" id="statusLabel">&nbsp;<b>Status:</b></td>
    <td><?php makeDropdown("status",$_SESSION['taxStatus'],$status,$status); ?></td>
  <td align="right" id="rankLabel">&nbsp;<b>Rank:</b></td>
    <td><?php makeDropdown("rank",$_SESSION['taxRank'],$rank,$rank); ?></td>
  <td align="right" id="authorLabel">&nbsp;<b>Author:</b></td>
    <td><input type="text" name="author" id="author" value="<?php echoSpecial('taxAuthor', 'SESSION'); ?>"></td>
</tr><tr>
  <td align="right"id="collectorLabel">&nbsp;<b>Typecollection:</b></td>
    <td><input type="text" name="collector" id="collector" value="<?php echoSpecial('taxCollector', 'SESSION'); ?>"></td>
  <td align="right" id="numberLabel">&nbsp;<b>Number:</b></td>
    <td><input type="text" name="number" id="number" value="<?php echoSpecial('taxNumber', 'SESSION'); ?>"></td>
  <td align="right" id="dateLabel">&nbsp;<b>Date:</b></td>
    <td><input type="text" name="date" id="date" value="<?php echoSpecial('taxDate', 'SESSION'); ?>"></td>
</tr><tr>
  <td align="right" id="annotationLabel">&nbsp;<b>Annotation:</b></td>
    <td colspan="5"><input type="text" name="annotation" id="annotation" size="89" value="<?php echoSpecial('taxAnnotation', 'SESSION'); ?>"></td>
</tr><tr>
  <td align="left" colspan="3"><input class="button" type="submit" name="search" value=" search "></td>
  <td align="right" id="externalLabel" colspan="2">&nbsp;<b>external:</b></td>
  <td align="left"><input type="checkbox" name="external" id="external"<?php echo (!empty($_SESSION['taxExternal'])) ? ' checked' : ''; ?>></td>
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
      <!-- BP: added id for Javascript -->
    <b>taxonID:</b> <input type="text" name="taxon" id="taxon" value="<?php echoSpecial('taxon', 'POST'); ?>">
    <input class="button" type="submit" name="select" value=" Edit ">
  </form>
</td></tr></table>
<p>

<?php
/* Buchstabenleiste 'Family' abgeschaltet
if (!$_SESSION['editFamily']) {
  echo "<form Action=\"".$_SERVER['PHP_SELF']."\" Method=\"POST\">\n";
  echo "<b>Family:</b> ";
  for ($i=0,$a='A';$i<26;$i++,$a++)
    echo "<input class=\"button\" type=\"button\" value=\"$a\" style=\"width: 1.6em\" ".
         "onClick=\"self.location.href='".$_SERVER['PHP_SELF']."?lfamily=$a'\"\n>";
  echo "</form>\n";
}
*/
echo "<table cellpadding=\"0\" cellspacing=\"0\"><tr>";

/* Buchstabenleiste 'Genus' abgeschaltet
echo "<td><form Action=\"".$_SERVER['PHP_SELF']."\" Method=\"POST\">\n";
echo "<b>Genus:</b> ";
for ($i=0,$a='A';$i<26;$i++,$a++)
  echo "<input class=\"button\" type=\"button\" value=\"$a\" style=\"width: 1.6em\" ".
       "onClick=\"self.location.href='".$_SERVER['PHP_SELF']."?lgenus=$a'\"\n>";
echo "</form></td>\n";
*/

// BP: if MDLD-search-field contains at least one char,
// BP: ignore all other fields and make an MDLD-search
if ($_SESSION['taxMDLD'] != "") {
    $mdldResult = showMatchJsonRPCClickable($_SESSION['taxMDLD'],$nrSel);   // get MDLD-result
    $html_out = $mdldResult['html'];                        // formatted HTML-output
    $_SESSION['firstTaxonID'] = $mdldResult['id'];          // ID for field "taxonID"
    //$html_out .= dumpMatchJsonRPC($_SESSION['taxMDLD']);  // dump result (for testing only)
    echo $html_out;                                         // print the output
    ?>

    <!-- BP: MDLD-search finished ==> display first "meaningful" ID of search-result
         (genus or species) in field "taxonID" -->
    <script type="text/javascript" language="JavaScript">
        $("#taxon").val("<?php echo $_SESSION['firstTaxonID']; ?>");
    </script>

    <?php
} else {
    // BP: reset field "taxonID" to empty
    $_SESSION['firstTaxonID'] = "";     
    ?>
    
    <!-- BP: no MDLD-search ==> display empty string in field "taxonID" -->
    <script type="text/javascript" language="JavaScript">
        $("#taxon").val("");
    </script>

    <?php
    if ($_SESSION['taxType'] == 3) {  
        echo prettyPrintSynonymLinks();     // BP: moved to a function
    }
    echo "</tr></table><p>\n";

    if ($_SESSION['taxType'] == 1) {  // list Family
       /* $sql = "SELECT tf.familyID, tf.family, tsc.cat_description category
                FROM tbl_tax_families tf
                 LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID = tsc.categoryID "
             . (($_SESSION['taxExternal']) ? "WHERE tf.external > 0 " : "WHERE tf.external = 0 ");
		*/
		$sql = "
SELECT
 tf.familyID, tf.family, tsc.cat_description category,
 sp.taxonID

FROM
 tbl_tax_families tf
 LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID = tsc.categoryID
 
 LEFT JOIN tbl_tax_genera gen ON gen.genus=tf.family
 LEFT JOIN tbl_tax_species sp ON(
      sp.speciesID IS NULL
  AND sp.subspeciesID IS NULL AND sp.subspecies_authorID IS NULL
  AND sp.varietyID IS NULL AND sp.variety_authorID IS NULL
  AND sp.subvarietyID IS NULL AND sp.subvariety_authorID IS NULL
  AND sp.formaID IS NULL AND sp.forma_authorID IS NULL
  AND sp.subformaID IS NULL AND sp.subforma_authorID IS NULL
  AND sp.genID=gen.genID
 )
 "
             . (($_SESSION['taxExternal']) ? "WHERE tf.external > 0 " : "WHERE tf.external = 0 ");
        if (trim($_SESSION['taxFamily'])) {
            $sql .= "AND family LIKE '" . mysql_escape_string($_SESSION['taxFamily']) . "%' ";
        }
        $sql .= "ORDER BY " . $_SESSION['taxOrder'] . " LIMIT 1001";
		
			 
        $result = db_query($sql);
        if (mysql_num_rows($result) > 1000) {
            echo "<b>no more than 1000 results allowed</b>\n";
        } elseif (mysql_num_rows($result) > 0) {
            echo "<table class=\"out\" cellspacing=\"0\">\n";
            echo "<tr class=\"out\">";
            echo "<th class=\"out\">"
               . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=ac\">Category</a>" . sortItem($_SESSION['taxOrTyp'], 11) . "</th>";
            echo "<th class=\"out\">"
               . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=af\">Family</a>" . sortItem($_SESSION['taxOrTyp'], 12) . "</th>";
            echo "</tr>\n";
            while ($row = mysql_fetch_array($result)) {
                echo "<tr class=\"out\"><td class=\"out\">";
                echo "<a href=\"javascript:selectTaxon('" . $row['taxonID'] . "')\">";
                echo $row['category'];
                echo "</a></td><td class=\"out\">";
                echo "<a href=\"javascript:selectTaxon('" . $row['taxonID'] . "')\">";
                echo $row['family'];
                echo "</a></td></tr>\n";
            }
            echo "</table>\n";
        } else {
            echo "<b>nothing found!</b>\n";
        }
    } else if ($_SESSION['taxType'] == 2) {  // list Genus
        $sql = "SELECT tg.genID, tg.genus, tg.fk_taxonID, tag.author auth_g, tf.family, 
                 tsc.cat_description category, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs
                FROM tbl_tax_genera tg
                 LEFT JOIN tbl_tax_authors tag ON tag.authorID=tg.authorID
                 LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID
                 LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID=tsc.categoryID
                WHERE genus LIKE '".mysql_escape_string($_SESSION['taxGenus'])."%' "
             . (($_SESSION['taxExternal']) ? "AND tg.external > 0 " : "AND tg.external = 0 ");
        if ($_SESSION['taxFamily']) {
            $sql .= "AND family LIKE '".mysql_escape_string($_SESSION['taxFamily'])."%' ";
        }
        if ($_SESSION['taxAnnotation']) {
            $sql .= "AND tg.remarks LIKE '%".mysql_escape_string($_SESSION['taxAnnotation'])."%' ";
        }
        $sql .= "ORDER BY ".$_SESSION['taxOrder']." LIMIT 1001";
        $result = db_query($sql);
        if (mysql_num_rows($result)>1000) {
            echo "<b>no more than 1000 results allowed</b>\n";
        } elseif (mysql_num_rows($result)>0) {
            echo "<table class=\"out\" cellspacing=\"0\">\n";
            echo "<tr class=\"out\">";
            echo "<th class=\"out\">"
               . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=bg\">Genus</a>" . sortItem($_SESSION['taxOrTyp'], 21) . "</th>";
            echo "<th class=\"out\">Author</th>";
            echo "<th class=\"out\">RefNo</th>";
            echo "<th class=\"out\">"
               . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=bf\">Family</a>" . sortItem($_SESSION['taxOrTyp'], 22) . "</th>";
            echo "<th class=\"out\">Category</th></tr>\n";
            while ($row = mysql_fetch_array($result)) {
                echo "<tr class=\"out\"><td class=\"out\">";
                echo "<a href=\"javascript:selectTaxon('" . $row['fk_taxonID'] . "')\">";
                echo $row['genus'];
                echo "</a></td><td class=\"out\">";
                echo "<a href=\"javascript:selectTaxon('" . $row['fk_taxonID'] . "')\">";
                echo $row['auth_g'];
                echo "</a></td><td class=\"out\">";
                echo $row['DallaTorreIDs'] . $row['DallaTorreZusatzIDs'];
                echo "</td><td class=\"out\">";
                echo "<a href=\"javascript:selectTaxon('" . $row['fk_taxonID'] . "')\">";
                echo $row['family'];
                echo "</a></td><td class=\"out\">";
                echo "<a href=\"javascript:selectTaxon('" . $row['fk_taxonID'] . "')\">";
                echo $row['category'];
                echo "</a></td></tr>\n";
            }
            echo "</table>\n";
        } else {
            echo "<b>nothing found!</b>\n";
        }
    }
    else if ($_SESSION['taxType'] == 3) {  // list Species
        $sql = "SELECT ts.taxonID, ts.statusID, tg.genus, tag.author auth_g, tf.family,
                 ta.author author, ta1.author author1, ta2.author author2, ta3.author author3,
                 ta4.author author4, ta5.author author5,
                 te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                 te4.epithet epithet4, te5.epithet epithet5
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
                 LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID "
             . (($_SESSION['taxExternal']) ? "WHERE ts.external > 0 " : "WHERE ts.external = 0 ");
        if ($_SESSION['taxStatus'] != "everything") {
            if ($_SESSION['taxSpecies']) {
                $sql .= "AND (te.epithet LIKE '" . mysql_escape_string($_SESSION['taxSpecies']) . "%'
                          OR te1.epithet LIKE '" . mysql_escape_string($_SESSION['taxSpecies']) . "%'
                          OR te2.epithet LIKE '" . mysql_escape_string($_SESSION['taxSpecies']) . "%'
                          OR te3.epithet LIKE '" . mysql_escape_string($_SESSION['taxSpecies']) . "%'
                          OR te4.epithet LIKE '" . mysql_escape_string($_SESSION['taxSpecies']) . "%'
                          OR te5.epithet LIKE '" . mysql_escape_string($_SESSION['taxSpecies']) . "%') ";
            } else {
                $sql .= "AND te.epithet IS NULL ";
            }
            if ($_SESSION['taxStatus']) {
                $sql .= "AND ts.statusID=" . extractID($_SESSION['taxStatus']) . " ";
            }
        }
        if ($_SESSION['taxRank']) {
            $sql .= "AND ts.tax_rankID=" . extractID($_SESSION['taxRank']) . " ";
        }
        if ($_SESSION['taxFamily']) {
            $sql .= "AND family LIKE '" . mysql_escape_string($_SESSION['taxFamily']) . "%' ";
        }
        if ($_SESSION['taxGenus']) {
            $sql .= "AND genus LIKE '" . mysql_escape_string($_SESSION['taxGenus']) . "%' ";
        }
        if ($_SESSION['taxAuthor']) {
            $sql .= "AND (ta.author LIKE '%" . mysql_escape_string($_SESSION['taxAuthor']) . "%'
                      OR ta1.author LIKE '%" . mysql_escape_string($_SESSION['taxAuthor']) . "%'
                      OR ta2.author LIKE '%" . mysql_escape_string($_SESSION['taxAuthor']) . "%'
                      OR ta3.author LIKE '%" . mysql_escape_string($_SESSION['taxAuthor']) . "%'
                      OR ta4.author LIKE '%" . mysql_escape_string($_SESSION['taxAuthor']) . "%'
                      OR ta5.author LIKE '%" . mysql_escape_string($_SESSION['taxAuthor']) . "%') ";
        }
        if ($_SESSION['taxAnnotation']) {
            $sql .= "AND ts.annotation LIKE '%" . mysql_escape_string($_SESSION['taxAnnotation']) . "%' ";
        }
        $sql .= "ORDER BY " . $_SESSION['taxOrder'] . " LIMIT 1001";
        $result = db_query($sql);
        if (mysql_num_rows($result) > 1000) {
            echo "<b>no more than 1000 results allowed</b>\n";
        } elseif (mysql_num_rows($result) > 0) {
            echo "<table class=\"out\" cellspacing=\"0\">\n";
            echo "<tr class=\"out\">";
            echo "<th class=\"out\">ID</th>";
            echo "<th class=\"out\">".
                 "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=cf\">Family</a>" . sortItem($_SESSION['taxOrTyp'], 32) . "</th>";
            echo "<th class=\"out\">acc.</th>";
            echo "<th class=\"out\">".
                 "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=cg\">Genus</a>" . sortItem($_SESSION['taxOrTyp'], 31) . "</th>";
            echo "<th class=\"out\">Author</th>";
            echo "<th class=\"out\">".
                 "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=cs\">Species</a>" . sortItem($_SESSION['taxOrTyp'], 33) . "</th>";
            echo "<th class=\"out\">Author</th>";
            echo "<th class=\"out\">infraspecific Taxon</th>";
            echo "</tr>\n";
            $nr = 1;
            while ($row = mysql_fetch_array($result)) {
                $linkList[$nr] = $row['taxonID'];
                echo "<tr class=\"" . (($nrSel == $nr) ? "outMark" : "out") . "\"><td class=\"out\" style=\"text-align:right\">";
                echo "<a href=\"javascript:selectTaxon('{$row['taxonID']}')\">";
                echo $row['taxonID'];
                echo "</a></td><td class=\"out\">";
                echo "<a href=\"javascript:selectTaxon('{$row['taxonID']}')\">";
                echo $row['family'];
                echo "</a></td><td style=\"text-align: center;\" class=\"out\">";
                echo (($row['statusID'] == 96) ? "&bull;" : "");
                echo "</td><td class=\"out\">";
                echo "<a href=\"javascript:selectTaxon('{$row['taxonID']}')\">";
                echo $row['genus'];
                echo "</a></td><td class=\"out\">";
                echo "<a href=\"javascript:selectTaxon('{$row['taxonID']}')\">";
                echo $row['auth_g'];
                echo "</a></td><td class=\"out\">";
                echo "<a href=\"javascript:selectTaxon('{$row['taxonID']}')\">";
                echo $row['epithet'];
                echo "</a></td><td class=\"out\">";
                echo "<a href=\"javascript:selectTaxon('{$row['taxonID']}')\">";
                echo $row['author'];
                echo "</a></td><td class=\"out\">";
                echo "<a href=\"javascript:selectTaxon('{$row['taxonID']}')\">";
                echo subTaxonItem($row);
                echo "</a></td></tr>\n";
                $hybrids = getHybrids($row['taxonID']);
                if (strlen($hybrids) > 0) {
                    echo "<tr><td class=\"out\" colspan=\"7\">";
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
    } else if ($_SESSION['taxType'] == 4) {  // list Species, other display
        $sql = "SELECT ts.taxonID, tg.genus,
                 tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, tag.author author_g,
                 tf.family, tsc.category,
                 ta.author author, ta1.author author1, ta2.author author2, ta3.author author3,
                 ta4.author author4, ta5.author author5,
                 te.epithet epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                 te4.epithet epithet4, te5.epithet epithet5,
                 Sammler, Sammler_2, series, leg_nr, alternate_number, date, duplicates
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
            $sql .= "AND tt.date LIKE '" . mysql_escape_string($_SESSION['taxDate']) . "%' ";
        }
        if ($_SESSION['taxNumber']) {
            $sql .= "AND tt.leg_nr='" . mysql_escape_string($_SESSION['taxNumber']) . "' ";
        }
        if ($_SESSION['taxCollector'])
            $sql .= "AND (   tc.Sammler LIKE '" . mysql_escape_string($_SESSION['taxCollector']) . "%' "
                  . "     OR tc2.Sammler_2 LIKE '" . mysql_escape_string($_SESSION['taxCollector']) . "%') ";
        if ($_SESSION['taxStatus'] != "everything") {
            if ($_SESSION['taxSpecies']) {
                $sql .= "AND te.epithet LIKE '" . mysql_escape_string($_SESSION['taxSpecies']) . "%' ";
            } else {
                $sql .= "AND te.epithet IS NULL ";
            }
            if ($_SESSION['taxStatus']) {
                $sql .= "AND ts.statusID=" . extractID($_SESSION['taxStatus']) . " ";
            }
        }
        if ($_SESSION['taxRank']) {
            $sql .= "AND ts.tax_rankID=" . extractID($_SESSION['taxRank']) . " ";
        }
        if ($_SESSION['taxFamily']) {
            $sql .= "AND family LIKE '" . mysql_escape_string($_SESSION['taxFamily']) . "%' ";
        }
        if ($_SESSION['taxGenus']) {
            $sql .= "AND genus LIKE '" . mysql_escape_string($_SESSION['taxGenus']) . "%' ";
        }
        if ($_SESSION['taxAnnotation']) {
            $sql .= "AND ts.annotation LIKE '%" . mysql_escape_string($_SESSION['taxAnnotation']) . "%' ";
        }
        $sql .= "ORDER BY " . $_SESSION['taxOrder'] . " LIMIT 1001";
        $result = db_query($sql);
        if (mysql_num_rows($result) > 1000) {
            echo "<b>no more than 1000 results allowed</b>\n";
        } elseif (mysql_num_rows($result) > 0) {
            echo "<table class=\"out\" cellspacing=\"0\">\n";
            echo "<tr class=\"out\">";
            echo "<th class=\"out\">".
                 "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=da\">Type</a>" . sortItem($_SESSION['taxOrTyp'], 41) . "</th>";
            echo "<th class=\"out\">".
                 "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=db\">Taxon</a>" . sortItem($_SESSION['taxOrTyp'], 42) . "</th>";
            echo "</tr>\n";
            $nr = 1;
            while ($row = mysql_fetch_array($result)) {
                echo "<tr class=\"" . (($nrSel == $nr) ? "outMark" : "out") . "\"><td class=\"out\">";
                echo "<a href=\"javascript:selectTaxon('{$row['taxonID']}')\">";
                echo htmlspecialchars(typusItem($row));
                echo "</a></td><td class=\"out\">";
                echo "<a href=\"javascript:selectTaxon('{$row['taxonID']}')\">";
                echo htmlspecialchars(taxonWithFamily($row));
                echo "</a></td></tr>\n";
                $nr++;
            }
            echo "</table>\n";
        } else {
            echo "<b>nothing found!</b>\n";
        }
    }
} // no MDLD
?>

</body>
</html>