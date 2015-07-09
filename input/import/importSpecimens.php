<?php

const INCERTAE_SEDIS_IMPORT = 3449

;session_start();
require("../inc/connect.php");
require("../inc/log_functions.php");
require_once("../inc/herbardb_input_functions.php");
require_once('../inc/jsonRPCClient.php');
require_once('../inc/clsTaxonTokenizer.php');
no_magic();


/**
 * parses a line of a textfile and returns an array or false
 *
 * @param resource $handle
 * @param int[optional] $minNumOfParts minimum number of required columns (default: 2)
 * @param string[optional] $delimiter sets the field delimiter (default: ;)
 * @param string[optional] $enclosure sets the field enclosure character (default: ")
 * @return array|bool array of elements or "false" if too short
 */
function parseLine($handle, $minNumOfParts=2, $delimiter=';', $enclosure='"')
{
    $parts = fgetcsv($handle, 4096, $delimiter, $enclosure);
    if (count($parts) >= $minNumOfParts) {
        return $parts;
    } else {
        return false;
    }
}

/**
 * queries the database for a taxon with given ID
 *
 * @param int $id taxon-ID
 * @param bool[optional] $withAuthors check authors also
 * @return string taxon-text
 */
function getTaxon($id, $withAuthors = true) {
  $sql = "SELECT tg.genus,
           ta0.author  author0,  ta1.author  author1,  ta2.author  author2,  ta3.author  author3,  ta4.author  author4,  ta5.author  author5,
           te0.epithet epithet0, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5
          FROM tbl_tax_species ts
           LEFT JOIN tbl_tax_authors ta0 ON ta0.authorID = ts.authorID
           LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
           LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
           LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
           LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
           LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
           LEFT JOIN tbl_tax_epithets te0 ON te0.epithetID = ts.speciesID
           LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
           LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
           LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
           LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
           LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
           LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
          WHERE ts.taxonID = '" . intval($id) . "'";
  $row = mysql_fetch_array(db_query($sql));

  $text = $row['genus'];
  if ($row['epithet0']) $text .= " "         . $row['epithet0'] . (($withAuthors) ? " " . $row['author0'] : '');
  if ($row['epithet1']) $text .= " subsp. "  . $row['epithet1'] . (($withAuthors) ? " " . $row['author1'] : '');
  if ($row['epithet2']) $text .= " var. "    . $row['epithet2'] . (($withAuthors) ? " " . $row['author2'] : '');
  if ($row['epithet3']) $text .= " subvar. " . $row['epithet3'] . (($withAuthors) ? " " . $row['author3'] : '');
  if ($row['epithet4']) $text .= " forma "   . $row['epithet4'] . (($withAuthors) ? " " . $row['author4'] : '');
  if ($row['epithet5']) $text .= " subf. "   . $row['epithet5'] . (($withAuthors) ? " " . $row['author5'] : '');

  return $text;
}

/**
 * split the received taxonomic string into its subparts
 * first part is supposed to be the genus
 * if there is a second part is's supposed to be the species
 * if there's more, the parser searches for a rank keyword and the next part has to be the infraspecies
 * if the parser cannot find any rank keyword (subsp, var, subvar, forma or subf) the rest is ignored
 *
 * @param string $text the taxonomic string to parse
 * @return array parts of the parsed string -> genus, species, infra, subspecies, searchtext
 */
function splitTaxon($text)
{
    $pieces = explode(' ', $text, 3);
    $taxon['genus'] = trim($pieces[0]);
    if (count($pieces) > 1) {
        $taxon['species'] = trim($pieces[1]);
        if (count($pieces) > 2) {
            if (strpos($pieces[2], 'subsp') !== false) {
                $pos = strpos($pieces[2], 'subsp');
                $taxon['infra'] = " subsp. ";
            } elseif (strpos($pieces[2], 'var') !== false) {
                $pos = strpos($pieces[2], 'var');
                $taxon['infra'] = " var. ";
            } elseif (strpos($pieces[2], 'subvar') !== false) {
                $pos = strpos($pieces[2], 'subvar');
                $taxon['infra'] = " subvar. ";
            } elseif (strpos($pieces[2], 'forma') !== false) {
                $pos = strpos($pieces[2], 'forma');
                $taxon['infra'] = " forma ";
            } elseif (strpos($pieces[2], 'subf') !== false) {
                $pos = strpos($pieces[2], 'subf');
                $taxon['infra'] = " subforma ";
            } else {
                $pos = false;
                $taxon['infra'] = $taxon['subspecies'] = '';
            }
            if ($pos !== false) {
                $pieces2 = explode(' ', substr($pieces[2], $pos));
                $taxon['subspecies'] = (count($pieces2) > 1) ? $pieces2[1] : '';
            }
        } else {
            $taxon['infra'] = $taxon['subspecies'] = '';
        }
    } else {
        $taxon['species'] = $taxon['infra'] = $taxon['subspecies'] = '';
    }
    $taxon['searchtext'] = trim($taxon['genus'] . ' ' . $taxon['species'] . $taxon['infra'] . $taxon['subspecies']);

    return $taxon;
}

/**
 * inserts a new taxon into tbl_tax_species if the genus already exists
 * inserts all necessary epithets and authors into the appropriate tables
 *
 * @param string $taxon taxon to insert
 * @param integer $externalID external-ID, must be >0
 * @param integer $contentID primary ID of tbl_external_import_content
 * @param boolean $insert_new_genera New taxa with a genus part which is not
 *      yet in te database can be inserted if this option is turned ons. A
 *      new genus entry flagged as external will be created. It will be associated
 *      with a 'incertae sedis' family
 * @return array inserted "taxonID" or happened "error"
 */
function insertTaxon($taxon, $externalID, $contentID, $insert_new_genera = FALSE)
{
    $externalID = intval($externalID);
    $contentID  = intval($contentID);

    $ret = array('error' => '');

    if ($externalID == 0) {  // external-ID = 0 is not allowed
        $ret['error'] = 'Import of this new taxon has been skipped, you need to choose an externalID in the "Import Specimens" dialog first';
        return $ret;
    }

    $parser = clsTaxonTokenizer::Load();
    $taxonParts = $parser->tokenize($taxon);

    $result = db_query("SELECT genID FROM tbl_tax_genera WHERE genus = " . quoteString($taxonParts['genus']));
    if (mysql_num_rows($result) == 0) {
        if($insert_new_genera){
            // add nevertheles
            $genID = insertGenus($taxonParts['genus'], NULL, NULL, NULL, FALSE, TRUE, INCERTAE_SEDIS_IMPORT, NULL, NULL);
        } else {
            // genus not found -> abort
            $ret['error'] = 'genus not found';
            return $ret;
        }
    } else {
        $row = mysql_fetch_array($result);
        $genID = $row['genID'];
    }

  // find or insert the epithet if present
    if ($taxonParts['epithet']) {
        $result = db_query("SELECT epithetID FROM tbl_tax_epithets WHERE epithet = " . quoteString($taxonParts['epithet']));
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);
            $epithetID = $row['epithetID'];
        } else {
            db_query("INSERT INTO tbl_tax_epithets SET
                       epithet = " . quoteString($taxonParts['epithet']) . ",
                       locked = 0,
                       external = 1,
                       externalID = " . quoteString($externalID));
            $epithetID = mysql_insert_id();
        }
    } else {
        $epithetID = null;
    }

    // find or insert the author if present
    if ($taxonParts['author']) {
        $result = db_query("SELECT authorID FROM tbl_tax_authors WHERE author = " . quoteString($taxonParts['author']));
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);
            $authorID = $row['authorID'];
        } else {
            db_query("INSERT INTO tbl_tax_authors SET
                       author = " . quoteString($taxonParts['author']) . ",
                       locked = 0,
                       external = 1,
                       externalID = " . quoteString($externalID));
            $authorID = mysql_insert_id();
            logAuthors($authorID, 0);
        }
    } else {
        $authorID = null;
    }

    // find or insert the subepithet if present
    if ($taxonParts['subepithet']) {
        $result = db_query("SELECT epithetID FROM tbl_tax_epithets WHERE epithet = " . quoteString($taxonParts['subepithet']));
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);
            $subepithetID = $row['epithetID'];
        } else {
            db_query("INSERT INTO tbl_tax_epithets SET
                       epithet = " . quoteString($taxonParts['subepithet']) . ",
                       locked = 0,
                       external = 1,
                       externalID = " . quoteString($externalID));
            $subepithetID = mysql_insert_id();
        }
    } else {
        $subepithetID = null;
    }

    // find or insert the subauthor if present
    if ($taxonParts['subauthor']) {
        $result = db_query("SELECT authorID FROM tbl_tax_authors WHERE author = " . quoteString($taxonParts['subauthor']));
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);
            $subauthorID = $row['authorID'];
        } else {
            db_query("INSERT INTO tbl_tax_authors SET
                       author = " . quoteString($taxonParts['subauthor']) . ",
                       locked = 0,
                       external = 1,
                       externalID = " . quoteString($externalID));
            $subauthorID = mysql_insert_id();
            logAuthors($subauthorID, 0);
        }
    } else {
        $subauthorID = null;
    }

    $sql = "INSERT INTO tbl_tax_species SET
             genID = $genID,
             speciesID = " . (($epithetID) ? $epithetID : "NULL") . ",
             authorID = " . (($authorID) ? $authorID : "NULL") . ",
             tax_rankID = " . makeInt($taxonParts['rank'] + 1) . ",
             locked = 0,
             external = 1,
             externalID = " . makeInt($externalID);
    switch ($taxonParts['rank']) {
        case 1:
            if ($subepithetID) $sql .= ", subspeciesID = $subepithetID";
            if ($subauthorID)  $sql .= ", subspecies_authorID = $subauthorID";
            break;
        case 2:
            if ($subepithetID) $sql .= ", varietyID = $subepithetID";
            if ($subauthorID)  $sql .= ", variety_authorID = $subauthorID";
            break;
        case 3:
            if ($subepithetID) $sql .= ", subvarietyID = $subepithetID";
            if ($subauthorID)  $sql .= ", subvariety_authorID = $subauthorID";
            break;
        case 4:
            if ($subepithetID) $sql .= ", formaID = $subepithetID";
            if ($subauthorID)  $sql .= ", forma_authorID = $subauthorID";
            break;
        case 5:
            if ($subepithetID) $sql .= ", subformaID = $subepithetID";
            if ($subauthorID)  $sql .= ", subforma_authorID = $subauthorID";
            break;
    }
    db_query($sql);
    $ret['taxonID'] = mysql_insert_id();
    logSpecies($ret['taxonID'], 0);

    db_query("UPDATE tbl_external_import_content SET
               externalID = $externalID,
               taxonID    = " . $ret['taxonID'] . "
              WHERE contentID = $contentID");

    return $ret;
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - import Specimens</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="../css/screen.css">
  <script src="../inc/jQuery/jquery.min.js" type="text/javascript"></script>
  <script type="text/javascript" language="JavaScript">
      $(document).ready(function() {
          $( 'input[name^="similarTaxa_"]' ).change( function() {
              if( !$(this).attr( 'checked' ) ) return;
              
              // Find compare string
              var origString = $( 'input[name="' + $(this).attr( 'name' ) + '_orig"]' ).val();
              var selectVal = $(this).val();
              
              // Now find all other entries with the same value
              $( 'input[value="' + origString + '"]' ).each( function() {
                  var currName = $(this).attr( 'name' );
                  currName = currName.replace( '_orig', '' );
                  
                  $( 'input[name="' + currName + '"][value="' + selectVal + '"]' ).attr( 'checked', true );
              } );
          } );
      });
  </script>
</head>

<body>
<?php
$blocked = false;  // for now, anybody may insert anything   TODO: check the access rights!

if (isset($_FILES['userfile']) && is_uploaded_file($_FILES['userfile']['tmp_name'])) {
    /**
     * a file was uploaded and is ready to be parsed
     */
    $type = 1; // file uploaded
    $import = array();
    $handle = @fopen($_FILES['userfile']['tmp_name'], "r");
    if ($handle) {
        $linenumber = 1;
        while (!feof($handle)) {
            $parts = parseLine($handle, 6);
            if ($parts !== false) {
                $parts['linenumber'] = $linenumber++;
                array_push($import, $parts);
            }
        }
        fclose($handle);

        foreach ($import as $key => $row) {
            $first[$key]  = $row[0];
            $second[$key] = $row[3];
            $third[$key]  = $row[4];
        }
        array_multisort($first, SORT_ASC, SORT_STRING, $second, SORT_ASC, SORT_STRING, $third, SORT_ASC, SORT_STRING, $import);
    }

    db_query("DELETE FROM tbl_external_import_content
               WHERE specimen_ID IS NULL
                AND externalID IS NULL
                AND taxonID IS NULL");

    $ranks = array('', " subsp. ", " var. ", " subvar. ", " forma ", " subforma ");
    $status = array();
    $exists = array();
    $importable = 0;
    $importableTaxaPresent = false;
    $data = array();
    for ($i = 0; $i < count($import); $i++) {
        $OK = true;
        $status[$i] = "";

        /**
         * check if collection-ID exists
         */
        $result = db_query("SELECT collection FROM tbl_management_collections WHERE collectionID = '" . intval($import[$i][1]) . "'");
        if (mysql_num_rows($result) == 0) {
            $OK = false;
            $status[$i] .= "no_collection ";
            $data[$i]['collectionID'] = 0;
        } else {
            $data[$i]['collectionID'] = intval($import[$i][1]);
        }

        /**
         * get HerbNummer and check if this number already exists for the same institution (source_id), if there is a HerbNummer
         */
        $pieces = explode('_', $import[$i][0]);
        if (count($pieces) > 1) {
            $data[$i]['HerbNummer'] = trim($pieces[1]);
        } else {
            $data[$i]['HerbNummer'] = $import[$i][0];
        }
        if ($data[$i]['HerbNummer']) {
            $sql = "SELECT source_id
                    FROM tbl_management_collections
                    WHERE collectionID = '" . $data[$i]['collectionID'] . "'";
            $row = mysql_fetch_array(db_query($sql));
            $sql = "SELECT specimen_ID
                    FROM tbl_specimens, tbl_management_collections
                    WHERE tbl_specimens.collectionID = tbl_management_collections.collectionID
                     AND source_id = '" . $row['source_id'] . "'
                     AND HerbNummer = " . quoteString($data[$i]['HerbNummer']);
            $result = db_query($sql);
            if (mysql_num_rows($result) > 0) {
                $row = mysql_fetch_array($result);
                $OK = false;
                $status[$i] .= "exists ";
                $exists[$i] = $row['specimen_ID'];
            }
        }

        /**
         * check if identstatus exists
         */
        if (trim($import[$i][2])) {
            $result = db_query("SELECT identstatusID FROM tbl_specimens_identstatus WHERE identification_status = " . quoteString(trim($import[$i][2])));
            if (mysql_num_rows($result) > 0) {
                $row = mysql_fetch_array($result);
                $data[$i]['identstatusID'] = $row['identstatusID'];
            } else {
                $OK = false;
                $status[$i] .= "no_identstatus ";
            }
        } else {
            $data[$i]['identstatusID'] = "";
        }

        /**
         * check if taxon exists
         */
        $taxonOK = false;
        $genusOK = false;
        $pieces = explode(' ', $import[$i][3], 3);
        $result = db_query("SELECT genID FROM tbl_tax_genera WHERE genus = " . quoteString($pieces[0]));
        if (mysql_num_rows($result) > 0) {
            $genusOK = true;
            $row = mysql_fetch_array($result);
            $genID = $row['genID'];
            if (count($pieces) > 1) {
                $result = db_query("SELECT epithetID FROM tbl_tax_epithets WHERE epithet = " . quoteString($pieces[1]));
                if (mysql_num_rows($result) > 0) {
                    $row = mysql_fetch_array($result);
                    $epithetID = $row['epithetID'];
                    $result = db_query("SELECT taxonID FROM tbl_tax_species WHERE genID = '$genID' AND speciesID = '$epithetID'");
                    if (mysql_num_rows($result) > 0) {
                        while ($row = mysql_fetch_array($result)) {
                          if (strcmp(trim(getTaxon($row['taxonID'])), trim($import[$i][3])) == 0) {
                              $taxonOK = true;
                              $data[$i]['taxonID'] = $row['taxonID'];
                              break;
                          } else if (strcmp(trim(getTaxon($row['taxonID'], false)), trim($import[$i][3])) == 0) {
                              $taxonOK = true;
                              $data[$i]['taxonID'] = $row['taxonID'];
                              break;
                          }
                        }
                    }
                }
            } else {
                $sql = "SELECT taxonID
                        FROM tbl_tax_species
                        WHERE genID = '$genID'
                         AND speciesID IS NULL
                         AND subspeciesID IS NULL
                         AND varietyID IS NULL
                         AND subvarietyID IS NULL
                         AND formaID IS NULL
                         AND subformaID IS NULL";
                $result = db_query($sql);
                if (mysql_num_rows($result) > 0) {
                    $row = mysql_fetch_array($result);
                    $taxonOK = true;
                    $data[$i]['taxonID'] = $row['taxonID'];
                    //break;
                }
            }
        }

       /**
        * obtain suggestions for a taxon name by TaxaMatch
        */
        if (!$taxonOK) {
//            if(!$_OPTIONS['staging_area']){
//              // when using the staging area it is ok if the taxon names are not matching
//            }
            $OK = false;
            $status[$i] .= "no_taxa ";
            $data[$i]['taxonID'] = 0;

            $parser = clsTaxonTokenizer::Load();
            $taxonParts = $parser->tokenize($import[$i][3]);

            $taxamatch[$i] = array();
            $service = new jsonRPCClient($_OPTIONS['serviceTaxamatch']);
            try {
                //getMatchesService('vienna',$searchtext,array('showSyn'=>$showSynonyms,'NearMatch'=>false))
                $matches = $service->getMatchesService('vienna',$taxonParts['genus'] . ' ' . $taxonParts['epithet'] . $ranks[$taxonParts['rank']] . $taxonParts['subepithet'],array('showSyn'=>false,'NearMatch'=>false));
                if (isset($matches['result'][0]['searchresult'])) {
                    foreach ($matches['result'][0]['searchresult'] as $key => $val) {
                        for ($j = 0; $j < count($val['species']); $j++) {
                            $taxamatch[$i][] = $val['species'][$j];
                        }
                    }
                }
            }
            catch (Exception $e) {
                echo "JSON-Fehler " . nl2br($e);
            }
            if (!empty($taxamatch[$i])) {
                $status[$i] .= "similar_taxa ";
            } elseif (!$genusOK) {
                $status[$i] .= "no_genus ";
            }
        }

        /**
         * check the collectors (first and additional)
         */
        $collectorsOK = false;
        if (substr(trim($import[$i][4]), -6) == 'et al.') {
            $collector = substr(trim($import[$i][4]), 0, -7);
            $collector2 = "et al.";
        } else {
            $collectors = trim(strtr($import[$i][4], '&', ','));
            $parts = explode(', ', $collectors);
            $collector = trim($parts[0]);
            $collector2 = trim(substr(trim($import[$i][4]), strlen($collector) + 2));
        }
        $result = db_query("SELECT SammlerID FROM tbl_collector WHERE Sammler = " . quoteString($collector));
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);
            $collectorID = $row['SammlerID'];
            if (strlen($collector2) > 0) {
                $result = db_query("SELECT Sammler_2ID FROM tbl_collector_2 WHERE Sammler_2 = " . quoteString($collector2));
                if (mysql_num_rows($result) > 0) {
                    $row = mysql_fetch_array($result);
                    $collectorsOK = true;
                    $data[$i]['SammlerID'] = $collectorID;
                    $data[$i]['Sammler_2ID'] = $row['Sammler_2ID'];
                }
            } else {
                $collectorsOK = true;
                $data[$i]['SammlerID'] = $collectorID;
                $data[$i]['Sammler_2ID'] = 0;
            }
        }
        if (!$collectorsOK) {
            $OK = false;
            $status[$i] .= "no_collector ";
        }

        /**
         * check if series exists
         */
        if (trim($import[$i][5])) {
          $result = db_query("SELECT seriesID FROM tbl_specimens_series WHERE series = " . quoteString(trim($import[$i][5])));
          if (mysql_num_rows($result) > 0) {
              $row = mysql_fetch_array($result);
              $data[$i]['seriesID'] = $row['seriesID'];
          } else {
              $OK = false;
              $status[$i] .= "no_series ";
          }
        } else {
            $data[$i]['seriesID'] = "";
        }

        /**
         * fill series_number
         */
        $data[$i]['series_number'] = (!empty($import[$i][6])) ? $import[$i][6] : '';

        /**
         * fill Nummer
         */
        $data[$i]['Nummer'] = (!empty($import[$i][7])) ? $import[$i][7] : '';

        /**
         * fill alt_number
         */
        $data[$i]['alt_number'] = (!empty($import[$i][8])) ? $import[$i][8] : '';

        /**
         * fill Datum
         */
        $data[$i]['Datum'] = (!empty($import[$i][9])) ? $import[$i][9] : '';

        /**
         * fill Datum2
         */
        $data[$i]['Datum2'] = (!empty($import[$i][10])) ? $import[$i][10] : '';

        /**
         * fill det
         */
        $data[$i]['det'] = (!empty($import[$i][11])) ? $import[$i][11] : '';

        /**
         * fill typified
         */
        $data[$i]['typified'] = (!empty($import[$i][12])) ? $import[$i][12] : '';

        /**
         * check if type exists
         */
        if (isset($import[$i][13]) && trim($import[$i][13])) {
            $result = db_query("SELECT typusID FROM tbl_typi WHERE typus_lat = " . quoteString(trim($import[$i][13])));
            if (mysql_num_rows($result) > 0) {
                $row = mysql_fetch_array($result);
                $data[$i]['typusID'] = $row['typusID'];
            } else {
                $OK = false;
                $status[$i] .= "no_type ";
            }
        } else {
            $data[$i]['typusID'] = "";
        }

        /**
         * fill taxon_alt
         */
        $data[$i]['taxon_alt'] = (!empty($import[$i][14])) ? $import[$i][14] : '';

        /**
         * check if nation exists
         */
        if (isset($import[$i][15]) && trim($import[$i][15])) {
            $result = db_query("SELECT nationID FROM tbl_geo_nation WHERE nation_engl = " . quoteString(trim($import[$i][15])));
            if (mysql_num_rows($result) > 0) {
                $row = mysql_fetch_array($result);
                $data[$i]['NationID'] = $row['nationID'];
            } else {
                $OK = false;
                $status[$i] .= "no_nation ";
                $data[$i]['NationID'] = "";
            }
        } else {
            $data[$i]['NationID'] = "";
        }

        /**
         * check if province exists
         */
        if (isset($import[$i][16]) && trim($import[$i][16])) {
            $sql = "SELECT provinceID
                    FROM tbl_geo_province
                    WHERE provinz = " . quoteString(trim($import[$i][16])) . "
                     AND nationID = '" . intval($data[$i]['NationID']) . "'";
            $result = db_query($sql);
            if (mysql_num_rows($result) > 0) {
                $row = mysql_fetch_array($result);
                $data[$i]['provinceID'] = $row['provinceID'];
            } else {
                $OK = false;
                $status[$i] .= "no_province ";
            }
        } else {
            $data[$i]['provinceID'] = "";
        }

        /**
         * fill Fundort
         */
        $data[$i]['Fundort'] = (!empty($import[$i][17])) ? $import[$i][17] : '';
        $data[$i]['Fundort_engl'] = (!empty($import[$i][18])) ? $import[$i][18] : '';
        
        /**
         * fill habitat & habitus
         */
        $data[$i]['Habitat'] = (!empty($import[$i][19])) ? $import[$i][19] : '';
        $data[$i]['Habitus'] = (!empty($import[$i][20])) ? $import[$i][20] : '';

        /**
         * fill Bemerkungen
         */
        $data[$i]['Bemerkungen'] = (!empty($import[$i][21])) ? $import[$i][21] : '';

        /**
         * fill geografical coordinates
         */
        if (isset($import[$i][22]) && isset($import[$i][23]) && isset($import[$i][24]) && isset($import[$i][25])) {
            $data[$i]['Coord_N'] = ($import[$i][22]=='N') ? $import[$i][23] : "";
            $data[$i]['N_Min']   = ($import[$i][22]=='N') ? $import[$i][24] : "";
            $data[$i]['N_Sec']   = ($import[$i][22]=='N') ? strtr($import[$i][25], ",", ".") : "";
            $data[$i]['Coord_S'] = ($import[$i][22]=='S') ? $import[$i][23] : "";
            $data[$i]['S_Min']   = ($import[$i][22]=='S') ? $import[$i][24] : "";
            $data[$i]['S_Sec']   = ($import[$i][22]=='S') ? strtr($import[$i][25], ",", ".") : "";
        } else {
            $data[$i]['Coord_N'] = $data[$i]['N_Min'] = $data[$i]['N_Sec'] = $data[$i]['Coord_S'] = $data[$i]['S_Min'] = $data[$i]['S_Sec'] = '';
        }
        if (isset($import[$i][26]) && isset($import[$i][27]) && isset($import[$i][28]) && isset($import[$i][29])) {
            $data[$i]['Coord_W'] = ($import[$i][26]=='W') ? $import[$i][27] : "";
            $data[$i]['W_Min']   = ($import[$i][26]=='W') ? $import[$i][28] : "";
            $data[$i]['W_Sec']   = ($import[$i][26]=='W') ? strtr($import[$i][29], ",", ".") : "";
            $data[$i]['Coord_E'] = ($import[$i][26]=='E') ? $import[$i][27] : "";
            $data[$i]['E_Min']   = ($import[$i][26]=='E') ? $import[$i][28] : "";
            $data[$i]['E_Sec']   = ($import[$i][26]=='E') ? strtr($import[$i][29], ",", ".") : "";
        } else {
            $data[$i]['Coord_W'] = $data[$i]['W_Min'] = $data[$i]['W_Sec'] = $data[$i]['Coord_E'] = $data[$i]['E_Min'] = $data[$i]['E_Sec'] = '';
        }
        $data[$i]['exactness'] = (isset($import[$i][30])) ? strtr($import[$i][30], ",", ".") : '';
        
        /**
         * Fill in quadrant info
         */
        $data[$i]['quadrant'] = (isset($import[$i][31])) ? $import[$i][31] : '';
        $data[$i]['quadrant_sub'] = (isset($import[$i][32])) ? $import[$i][32] : '';

        /**
         * fill altitude
         */
        $data[$i]['altitude_min'] = (!empty($import[$i][33])) ? $import[$i][33] : '';
        $data[$i]['altitude_max'] = (!empty($import[$i][34])) ? $import[$i][34] : '';

        /**
         * fill switch: digital image
         */
        $data[$i]['digital_image'] = (!empty($import[$i][35])) ? 1 : 0;

        /**
         * fill switch: digital image obs
         */
        $data[$i]['digital_image_obs'] = (!empty($import[$i][36])) ? 1 : 0;

        /**
         * fill switch: observation
         */
        $data[$i]['observation'] = (!empty($import[$i][37])) ? 1 : 0;

        /**
         * finished -> log the file contents and processing errors (if any)
         */
        db_query("INSERT INTO tbl_external_import_content SET
                   filename = " . quoteString($_FILES['userfile']['name']) . ",
                   linenumber = " . makeInt($import[$i]['linenumber']) . ",
                   line = " . quoteString(var_export($import[$i], true)) . ",
                   processingError = " . quoteString($status[$i]) . ",
                   userID = " . quoteString($_SESSION['uid']));
        $data[$i]['contentID'] = mysql_insert_id();

        if ($OK) {
            $status[$i] = "OK";
            $importable++;
        }

        if ($status[$i] == "OK") {                              // everything is OK -> data can be imported
            $position[$i] = 0;
        } elseif ($status[$i] == "no_taxa similar_taxa ") {     // taxon not found but similar taxon found
            $position[$i] = 1;
            $importableTaxaPresent = true;
        } elseif ($status[$i] == "no_taxa ") {                  // taxon not found at all, but genus is in database
            $position[$i] = 2;
            $importableTaxaPresent = TRUE;
        } elseif ($_OPTIONS['staging_area']['ignore_no_genus']  // taxon not found and genus unknown, but to be imported
            && $status[$i] == "no_taxa no_genus ") {             // due to staging area option
            $position[$i] = 2;
            $importableTaxaPresent = TRUE;
        } elseif (strpos($status[$i], "exists") !== false) {    // HerbNummer exists already in database
            $position[$i] = 4;
        } else {                                                // nothing of the above, something else went wrong
            $position[$i] = 3;
        }
    }
} elseif (!empty($_POST['import_data'])) {
    /**
     * data is ready to be inserted into the database
     */
    $type = 2;  // insert data
    $data = array();
    foreach ($_POST as $k => $v) {
        $pieces = explode('_', $k);
        if ($pieces[0] == 'position') {
            $data[intval($pieces[1])]['position'] = $v;
        } elseif ($pieces[0] == 'similarTaxa') {
            if (substr($v, 0, 2) == 'ID') {
                $data[intval($pieces[1])]['importTaxa'] = substr($v, 2);
            } else {
                $data[intval($pieces[1])]['similarID'] = $v;
            }
        } elseif ($pieces[0] == 'Sammler2ID') {
            $data[intval($pieces[1])]['Sammler_2ID'] = $v;
        } elseif ($pieces[0] == 'seriesnumber') {
            $data[intval($pieces[1])]['series_number'] = $v;
        } elseif ($pieces[0] == 'altnumber') {
            $data[intval($pieces[1])]['alt_number'] = $v;
        } elseif ($pieces[0] == 'taxonalt') {
            $data[intval($pieces[1])]['taxon_alt'] = $v;
        } elseif ($pieces[0] == 'CoordN') {
            $data[intval($pieces[1])]['Coord_N'] = $v;
        } elseif ($pieces[0] == 'NMin') {
            $data[intval($pieces[1])]['N_Min'] = $v;
        } elseif ($pieces[0] == 'NSec') {
            $data[intval($pieces[1])]['N_Sec'] = $v;
        } elseif ($pieces[0] == 'CoordS') {
            $data[intval($pieces[1])]['Coord_S'] = $v;
        } elseif ($pieces[0] == 'SMin') {
            $data[intval($pieces[1])]['S_Min'] = $v;
        } elseif ($pieces[0] == 'SSec') {
            $data[intval($pieces[1])]['S_Sec'] = $v;
        } elseif ($pieces[0] == 'CoordW') {
            $data[intval($pieces[1])]['Coord_W'] = $v;
        } elseif ($pieces[0] == 'WMin') {
            $data[intval($pieces[1])]['W_Min'] = $v;
        } elseif ($pieces[0] == 'WSec') {
            $data[intval($pieces[1])]['W_Sec'] = $v;
        } elseif ($pieces[0] == 'CoordE') {
            $data[intval($pieces[1])]['Coord_E'] = $v;
        } elseif ($pieces[0] == 'EMin') {
            $data[intval($pieces[1])]['E_Min'] = $v;
        } elseif ($pieces[0] == 'ESec') {
            $data[intval($pieces[1])]['E_Sec'] = $v;
        } elseif ($pieces[0] == 'altitudemin') {
            $data[intval($pieces[1])]['altitude_min'] = $v;
        } elseif ($pieces[0] == 'altitudemax') {
            $data[intval($pieces[1])]['altitude_max'] = $v;
        } elseif ($pieces[0] == 'digitalimage') {
            $data[intval($pieces[1])]['digital_image'] = $v;
        } elseif ($pieces[0] == 'digitalimageobs') {
            $data[intval($pieces[1])]['digital_image_obs'] = $v;
        } elseif ($pieces[0] == 'observation') {
            $data[intval($pieces[1])]['observation'] = $v;
        } elseif ($pieces[0] == 'Fundortengl') {
            $data[intval($pieces[1])]['Fundort_engl'] = $v;
        } elseif (    $pieces[0] == 'HerbNummer'    || $pieces[0] == 'seriesID'   || $pieces[0] == 'taxonID' || $pieces[0] == 'SammlerID'
                   || $pieces[0] == 'collectionID'  || $pieces[0] == 'Nummer'     || $pieces[0] == 'Datum'   || $pieces[0] == 'Datum2'
                   || $pieces[0] == 'Bemerkungen'   || $pieces[0] == 'typified'   || $pieces[0] == 'typusID' || $pieces[0] == 'NationID'
                   || $pieces[0] == 'provinceID'    || $pieces[0] == 'Fundort'    || $pieces[0] == 'det'
                   || $pieces[0] == 'exactness'     || $pieces[0] == 'Habitat'    || $pieces[0] == 'Habitus' || $pieces[0] == 'quadrant'
                   || $pieces[0] == 'quadrant_sub'
                   || $pieces[0] == 'identstatusID' || $pieces[0] == 'importTaxa' || $pieces[0] == 'contentid') {
            $data[intval($pieces[1])][$pieces[0]] = $v;
        }
    }

    // the real database-insert happens down below to show what the DB returns
} else {
    /**
     *  do nothing special, just show the basic form.
     */
    $type = 0;
}
?>

<?php if ($blocked): ?>
<script type="text/javascript" language="JavaScript">
  alert('You have no sufficient rights for the desired operation');
</script>
<?php endif; ?>

<h1>Import Specimens</h1>

<form enctype="multipart/form-data" Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f">

<input type="hidden" name="MAX_FILE_SIZE" value="8000000" />
Import this file: <input name="userfile" type="file" />
<input type="submit" value="check Import" />
<p>

<?php
if ($type == 1) {  // file uploaded
    if ($importableTaxaPresent) {
        echo "If taxa will be imported use this external-ID: "
           . "<select name='externalID'>\n"
           . "<option value='0'></option>\n";
        $result = db_query("SELECT externalID, description FROM tbl_external_import WHERE used = 0 ORDER BY externalID");
        while ($row = mysql_fetch_array($result)) {
            echo "<option value='" . $row['externalID'] . "'>" . htmlspecialchars($row['description']) . "&nbsp;&lt;" . $row['externalID'] . "&gt;</option>\n";
        }
        echo "</select><br>\n";
    }
    echo "$importable / " . count($import) . " entries are ready to be imported&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
       . "<input type=\"submit\" name=\"import_data\" value=\"import them now\"><br>\n";
    $ctr = 0;
    for ($block = 0; $block < 5; $block++) {
        switch ($block) {
            case 0: echo "<hr>Ready to be imported:<br>\n"; break;
            case 1: echo "<hr>Taxon not found but similar taxon found: (choose action)<br>\n"; break;
            case 2: echo "<hr>Taxon not found: (choose action)&nbsp;&nbsp;&nbsp;&nbsp;"
                       . "<button onclick=\"$('.importTaxaOn').attr('checked','checked'); return false;\">select all new taxa</button>"
                       . "<br>\n"; break;
            case 3: echo "<hr>One or more errors occurred:<br>\n"; break;
            case 4: echo "<hr>HerbNummer exists already in database:<br>\n"; break;
        }
        echo "<table border=\"1\">\n";
        for ($i = 0; $i < count($import); $i++) {
            if ($block == $position[$i]) {
                if ($status[$i] == "OK") {
                    echo "<tr style=\"background-color:#00FF00\">";
                } elseif (strpos($status[$i], "exists") !== false) {
                    echo "<tr style=\"background-color:#0000FF\">";
                } else {
                    echo "<tr>";
                }

                //echo "<td>" . $status[$i] . "</td>";

                if (strpos($status[$i], "exists") !== false) {
                    echo "<td><a href=\"../editSpecimens.php?sel=" . htmlspecialchars("<" . $exists[$i] . ">") . "\" target=\"Specimens\">" . htmlspecialchars($import[$i][0]) . "</a></td>";
                } else {
                    echo "<td>" . $import[$i][0] . "</td>";
                }
                echo "<td" . ((strpos($status[$i], "no_collection") !== false) ? " style=\"background-color:red\"" : "") . ">" . htmlspecialchars($import[$i][1]) . "</td>";
                echo "<td" . ((strpos($status[$i], "no_identstatus") !== false) ? " style=\"background-color:red\"" : "") . ">" . htmlspecialchars($import[$i][2]) . "</td>";
                if (strpos($status[$i], "similar_taxa") !== false) {
                    echo "<td style=\"background-color:yellow\" title=\"similar taxa found, choose in row below\">";
                } elseif (strpos($status[$i], "no_taxa") !== false) {
                    echo "<td style=\"background-color:red\" title=\"" . $status[$i] . "\">";
                } else {
                    echo "<td>";
                }
                echo htmlspecialchars($import[$i][3]) . "</td>";
                echo "<td" . ((strpos($status[$i], "no_collector") !== false) ? " style=\"background-color:red\"" : "") . ">" . htmlspecialchars($import[$i][4]) . "</td>";
                echo "<td" . ((strpos($status[$i], "no_series") !== false) ? " style=\"background-color:red\"" : "") . ">" . htmlspecialchars($import[$i][5]) . "</td>";
                echo "<td>" . $import[$i][6] . "</td>";
                echo "<td>" . $import[$i][7] . "</td>";
                echo "<td>" . $import[$i][8] . "</td>";
                echo "<td>" . $import[$i][9] . "</td>";
                echo "<td>" . $import[$i][10] . "</td>";
                echo "<td>" . $import[$i][11] . "</td>";
                echo "<td>" . $import[$i][12] . "</td>";
                echo "<td" . ((strpos($status[$i], "no_type") !== false) ? " style=\"background-color:red\"" : "") . ">" . htmlspecialchars($import[$i][13]) . "</td>";
                echo "<td>" . $import[$i][14] . "</td>";
                echo "<td" . ((strpos($status[$i], "no_nation") !== false) ? " style=\"background-color:red\"" : "") . ">" . htmlspecialchars($import[$i][15]) . "</td>";
                echo "<td" . ((strpos($status[$i], "no_province") !== false) ? " style=\"background-color:red\"" : "") . ">" . htmlspecialchars($import[$i][16]) . "</td>";
                echo "<td>" . $import[$i][17] . "</td>";
                echo "<td>" . $import[$i][18] . "</td>";
                echo "<td>" . $import[$i][19] . "</td>";
                echo "<td>" . $import[$i][20] . "</td>";
                echo "<td>" . $import[$i][21] . "</td>";
                echo "<td>" . $import[$i][22] . "</td>";
                echo "<td>" . $import[$i][23] . "</td>";
                echo "<td>" . $import[$i][24] . "</td>";
                echo "<td>" . $import[$i][25] . "</td>";
                echo "<td>" . $import[$i][26] . "</td>";
                echo "<td>" . $import[$i][27] . "</td>";
                echo "<td>" . $import[$i][28] . "</td>";
                echo "<td>" . $import[$i][29] . "</td>";
                echo "<td>" . $import[$i][30] . "</td>";
                echo "<td>" . $import[$i][31] . "</td>";
                echo "<td>" . $import[$i][32] . "</td>";
                echo "<td>" . $import[$i][33] . "</td>";
                echo "<td>" . $import[$i][34] . "</td>";
                echo "<td>" . $import[$i][35] . "</td>";
                echo "<td>" . $import[$i][36] . "</td>";
                echo "<td>" . $import[$i][37] . "</td>";
                echo "</tr>\n";

                if (strpos($status[$i], "similar_taxa") !== false) {
                    echo "<tr><td></td><td></td><td></td><td colspan=\"28\" style=\"background-color:yellow\">";
                    if ($block == 1) {
                        echo "<input type='hidden' name='similarTaxa_${ctr}_orig' value='" . htmlspecialchars($import[$i][3]) . "' />";
                        echo "<input type=\"radio\" name=\"similarTaxa_$ctr\" value=\"0\" checked>no import<br>";
                        foreach ($taxamatch[$i] as $val) {
                            echo "<input type=\"radio\" name=\"similarTaxa_$ctr\" value=\"" . htmlspecialchars($val['taxonID']) . "\">"
                               . $val['taxon'] . "<br>";
                        }
                        if (strpos($status[$i], "no_genus") === false) {
                            echo "<input type=\"radio\" name=\"similarTaxa_$ctr\" value=\"ID" . htmlspecialchars($import[$i][3]) . "\">Import \""
                               . htmlspecialchars($import[$i][3])
                               . "\" first<br>";
                        }
                    } else {
                        foreach ($taxamatch[$i] as $val) {
                            echo $val['taxon'] . "<br>";
                        }
                    }
                    echo "</td></tr>\n";
                } elseif ($block == 2) {
                    echo "<tr><td></td><td></td><td></td><td colspan=\"28\" style=\"background-color:red\">"
                       . "<input type=\"radio\" name=\"importTaxa_$ctr\" value=\"0\" checked>no import<br>"
                       . "<input type=\"radio\" name=\"importTaxa_$ctr\" class=\"importTaxaOn\" value=\"" . htmlspecialchars($import[$i][3]) . "\">Import \""
                       . htmlspecialchars($import[$i][3])
                       . "\" first</td></tr>\n";
                }
                if ($block < 3) {
                    echo "<input type=\"hidden\" name=\"HerbNummer_$ctr\" value=\""    . htmlspecialchars($data[$i]['HerbNummer']) . "\">"
                       . "<input type=\"hidden\" name=\"collectionID_$ctr\" value=\""  . htmlspecialchars($data[$i]['collectionID']) . "\">"
                       . "<input type=\"hidden\" name=\"identstatusID_$ctr\" value=\"" . htmlspecialchars($data[$i]['identstatusID']) . "\">"
                       . "<input type=\"hidden\" name=\"taxonID_$ctr\" value=\""       . htmlspecialchars($data[$i]['taxonID']) . "\">"
                       . "<input type=\"hidden\" name=\"SammlerID_$ctr\" value=\""     . htmlspecialchars($data[$i]['SammlerID']) . "\">"
                       . "<input type=\"hidden\" name=\"Sammler2ID_$ctr\" value=\""    . htmlspecialchars($data[$i]['Sammler_2ID']) . "\">"
                       . "<input type=\"hidden\" name=\"seriesID_$ctr\" value=\""      . htmlspecialchars($data[$i]['seriesID']) . "\">"
                       . "<input type=\"hidden\" name=\"seriesnumber_$ctr\" value=\""  . htmlspecialchars($data[$i]['series_number']) . "\">"
                       . "<input type=\"hidden\" name=\"Nummer_$ctr\" value=\""        . htmlspecialchars($data[$i]['Nummer']) . "\">"
                       . "<input type=\"hidden\" name=\"altnumber_$ctr\" value=\""     . htmlspecialchars($data[$i]['alt_number']) . "\">"
                       . "<input type=\"hidden\" name=\"Datum_$ctr\" value=\""         . htmlspecialchars($data[$i]['Datum']) . "\">"
                       . "<input type=\"hidden\" name=\"Datum2_$ctr\" value=\""        . htmlspecialchars($data[$i]['Datum2']) . "\">"
                       . "<input type=\"hidden\" name=\"det_$ctr\" value=\""           . htmlspecialchars($data[$i]['det']) . "\">"
                       . "<input type=\"hidden\" name=\"typified_$ctr\" value=\""      . htmlspecialchars($data[$i]['typified']) . "\">"
                       . "<input type=\"hidden\" name=\"typusID_$ctr\" value=\""       . htmlspecialchars($data[$i]['typusID']) . "\">"
                       . "<input type=\"hidden\" name=\"taxonalt_$ctr\" value=\""      . htmlspecialchars($data[$i]['taxon_alt']) . "\">"
                       . "<input type=\"hidden\" name=\"NationID_$ctr\" value=\""      . htmlspecialchars($data[$i]['NationID']) . "\">"
                       . "<input type=\"hidden\" name=\"provinceID_$ctr\" value=\""    . htmlspecialchars($data[$i]['provinceID']) . "\">"
                       . "<input type=\"hidden\" name=\"Fundort_$ctr\" value=\""       . htmlspecialchars($data[$i]['Fundort']) . "\">"
                       . "<input type=\"hidden\" name=\"Fundortengl_$ctr\" value=\""   . htmlspecialchars($data[$i]['Fundort_engl']) . "\">"
                       . "<input type=\"hidden\" name=\"Habitat_$ctr\" value=\""       . htmlspecialchars($data[$i]['Habitat']) . "\">"
                       . "<input type=\"hidden\" name=\"Habitus_$ctr\" value=\""       . htmlspecialchars($data[$i]['Habitus']) . "\">"
                       . "<input type=\"hidden\" name=\"Bemerkungen_$ctr\" value=\""   . htmlspecialchars($data[$i]['Bemerkungen']) . "\">"
                       . "<input type=\"hidden\" name=\"CoordW_$ctr\" value=\""        . htmlspecialchars($data[$i]['Coord_W']) . "\">"
                       . "<input type=\"hidden\" name=\"WMin_$ctr\" value=\""          . htmlspecialchars($data[$i]['W_Min']) . "\">"
                       . "<input type=\"hidden\" name=\"WSec_$ctr\" value=\""          . htmlspecialchars($data[$i]['W_Sec']) . "\">"
                       . "<input type=\"hidden\" name=\"CoordN_$ctr\" value=\""        . htmlspecialchars($data[$i]['Coord_N']) . "\">"
                       . "<input type=\"hidden\" name=\"NMin_$ctr\" value=\""          . htmlspecialchars($data[$i]['N_Min']) . "\">"
                       . "<input type=\"hidden\" name=\"NSec_$ctr\" value=\""          . htmlspecialchars($data[$i]['N_Sec']) . "\">"
                       . "<input type=\"hidden\" name=\"CoordS_$ctr\" value=\""        . htmlspecialchars($data[$i]['Coord_S']) . "\">"
                       . "<input type=\"hidden\" name=\"SMin_$ctr\" value=\""          . htmlspecialchars($data[$i]['S_Min']) . "\">"
                       . "<input type=\"hidden\" name=\"SSec_$ctr\" value=\""          . htmlspecialchars($data[$i]['S_Sec']) . "\">"
                       . "<input type=\"hidden\" name=\"CoordE_$ctr\" value=\""        . htmlspecialchars($data[$i]['Coord_E']) . "\">"
                       . "<input type=\"hidden\" name=\"EMin_$ctr\" value=\""          . htmlspecialchars($data[$i]['E_Min']) . "\">"
                       . "<input type=\"hidden\" name=\"ESec_$ctr\" value=\""          . htmlspecialchars($data[$i]['E_Sec']) . "\">"
                       . "<input type=\"hidden\" name=\"exactness_$ctr\" value=\""     . htmlspecialchars($data[$i]['exactness']) . "\">"
                       . "<input type=\"hidden\" name=\"quadrant_$ctr\" value=\""      . htmlspecialchars($data[$i]['quadrant']) . "\">"
                       . "<input type=\"hidden\" name=\"quadrantsub_$ctr\" value=\""   . htmlspecialchars($data[$i]['quadrant_sub']) . "\">"
                       . "<input type=\"hidden\" name=\"altitudemin_$ctr\" value=\""   . htmlspecialchars($data[$i]['altitude_min']) . "\">"
                       . "<input type=\"hidden\" name=\"altitudemax_$ctr\" value=\""   . htmlspecialchars($data[$i]['altitude_max']) . "\">"
                       . "<input type=\"hidden\" name=\"digitalimage_$ctr\" value=\""  . htmlspecialchars($data[$i]['digital_image']) . "\">"
                       . "<input type=\"hidden\" name=\"digitalimageobs_$ctr\" value=\""  . htmlspecialchars($data[$i]['digital_image_obs']) . "\">"
                       . "<input type=\"hidden\" name=\"observation_$ctr\" value=\""   . htmlspecialchars($data[$i]['observation']) . "\">"
                       . "<input type=\"hidden\" name=\"contentid_$ctr\" value=\""     . htmlspecialchars($data[$i]['contentID']) . "\">"
                       . "<input type=\"hidden\" name=\"position_$ctr\" value=\""      . htmlspecialchars($position[$i]) . "\">\n";

                    $ctr++;
                }
            }
        }
        echo "</table>\n";
    }
} elseif ($type ==2) {  // insert data
    echo '<div id="import_tasks">' . count($data) . ((count($data) > 1) ? " entries are" : " entry is") . " to be imported</div>\n";
    echo '<div id="import_errors" class="error">' . "\n";
    $imported = 0;
    // $imported_taxa: an associative array to remember taxa that have been imported already
    //      key = taxon name string, value = taxonID in db
    $imported_taxa = array();
    for ($i = 0; $i < count($data); $i++) {
        if (   $data[$i]['position'] == 0
            || ($data[$i]['position'] == 1 && !empty($data[$i]['similarID']))
            || (!empty($data[$i]['importTaxa']) && ($data[$i]['position'] == 1 || $data[$i]['position'] == 2))) {

            if ($data[$i]['position'] == 1 && empty($data[$i]['importTaxa'])) {
                $data[$i]['taxonID'] = $data[$i]['similarID'];
            } elseif (!empty($data[$i]['importTaxa']) && ($data[$i]['position'] == 1 || $data[$i]['position'] == 2)) {
                // this taxon was not yet in the db on CheckImport
                if(!array_key_exists($data[$i]['importTaxa'], $imported_taxa)) {
                  $result = insertTaxon($data[$i]['importTaxa'], $_POST['externalID'], $data[$i]['contentid'], $_OPTIONS['staging_area']['ignore_no_genus']);
                  if (!$result['error']) {
                    $data[$i]['taxonID'] = $result['taxonID'];
                    $imported_taxa[$data[$i]['importTaxa']] = $result['taxonID'];
                  }
                  else {
                    echo $data[$i]['importTaxa'] . ": " . $result['error'] . "<br>\n";
                    continue;  // abort the insertion of this taxon and the specimen because something went very wrong
                  }
                } else {
                  $data[$i]['taxonID'] = $imported_taxa[$data[$i]['importTaxa']];
                }
            }
            $sql = "SELECT specimen_ID FROM tbl_specimens_import WHERE 1 = 1";
            foreach ($data[$i] as $k => $v) {
                if ($k != 'position' && $k != 'similarID' && $k != 'importTaxa' && $k != 'contentid') {
                    if (strlen($v) > 0) {
                        $sql .= " AND $k = " . quoteString($v);
                    } else {
                        $sql .= " AND $k IS NULL";
                    }
                }
            }
            $result = db_query($sql);
            if (mysql_num_rows($result) == 0) {
                $sqlInsert = "INSERT INTO tbl_specimens_import SET
                               HerbNummer = "    . quoteString($data[$i]['HerbNummer'])    . ",
                               collectionID = "  . quoteString($data[$i]['collectionID'])  . ",
                               identstatusID = " . quoteString($data[$i]['identstatusID']) . ",
                               taxonID = "       . quoteString($data[$i]['taxonID'])       . ",
                               SammlerID = "     . quoteString($data[$i]['SammlerID'])     . ",
                               Sammler_2ID = "   . quoteString($data[$i]['Sammler_2ID'])   . ",
                               seriesID = "      . quoteString($data[$i]['seriesID'])      . ",
                               series_number = " . quoteString($data[$i]['series_number']) . ",
                               Nummer = "        . quoteString($data[$i]['Nummer'])        . ",
                               alt_number = "    . quoteString($data[$i]['alt_number'])    . ",
                               Datum = "         . quoteString($data[$i]['Datum'])         . ",
                               Datum2 = "        . quoteString($data[$i]['Datum2'])        . ",
                               det = "           . quoteString($data[$i]['det'])           . ",
                               typified = "      . quoteString($data[$i]['typified'])      . ",
                               typusID = "       . quoteString($data[$i]['typusID'])       . ",
                               taxon_alt = "     . quoteString($data[$i]['taxon_alt'])     . ",
                               NationID = "      . quoteString($data[$i]['NationID'])      . ",
                               provinceID = "    . quoteString($data[$i]['provinceID'])    . ",
                               Fundort = "       . quoteString($data[$i]['Fundort'])       . ",
                               Fundort_engl = "  . quoteString($data[$i]['Fundort_engl'])  . ",
                               Habitat = "       . quoteString($data[$i]['Habitat'])       . ",
                               Habitus = "       . quoteString($data[$i]['Habitus'])       . ",
                               Bemerkungen = "   . quoteString($data[$i]['Bemerkungen'])   . ",
                               Coord_W = "       . quoteString($data[$i]['Coord_W'])       . ",
                               W_Min = "         . quoteString($data[$i]['W_Min'])         . ",
                               W_Sec = "         . quoteString($data[$i]['W_Sec'])         . ",
                               Coord_N = "       . quoteString($data[$i]['Coord_N'])       . ",
                               N_Min = "         . quoteString($data[$i]['N_Min'])         . ",
                               N_Sec = "         . quoteString($data[$i]['N_Sec'])         . ",
                               Coord_S = "       . quoteString($data[$i]['Coord_S'])       . ",
                               S_Min = "         . quoteString($data[$i]['S_Min'])         . ",
                               S_Sec = "         . quoteString($data[$i]['S_Sec'])         . ",
                               Coord_E = "       . quoteString($data[$i]['Coord_E'])       . ",
                               E_Min = "         . quoteString($data[$i]['E_Min'])         . ",
                               E_Sec = "         . quoteString($data[$i]['E_Sec'])         . ",
                               exactness = "     . quoteString($data[$i]['exactness'])     . ",
                               quadrant = "      . quoteString($data[$i]['quadrant'])      . ",
                               quadrant_sub = "  . quoteString($data[$i]['quadrant_sub'])  . ",
                               altitude_min = "  . quoteString($data[$i]['altitude_min'])  . ",
                               altitude_max = "  . quoteString($data[$i]['altitude_max'])  . ",
                               digital_image = " . quoteString($data[$i]['digital_image']) . ",
                               digital_image_obs = " . quoteString($data[$i]['digital_image_obs']) . ",
                               observation = "   . quoteString($data[$i]['observation'])   . ",
                               userID = "        . quoteString($_SESSION['uid']);
                db_query($sqlInsert);
                $specimen_ID = mysql_insert_id();
                db_query("UPDATE tbl_external_import_content SET
                           specimen_ID = $specimen_ID,
                           pending = 1
                          WHERE contentID = " . makeInt($data[$i]['contentid']));
                $imported++;
            }
        }
    }
    echo "</div>\n";
    echo '<div id="import_success">' . $imported . (($imported > 1) ? " entries have" : " entry has") . " been imported </div>\n";
}

?></form>

</body>
</html>