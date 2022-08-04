<?php
require_once( "../inc/connect.php" );

$basedir = $_CONFIG['FILESYSTEM']['BATCHEXPORT'];
$dirprefix = "batch";
$fileprefix = "API";
$extension = "xml";

$batchID = intval($_GET['ID']);

function getPicturePaths($path, $pic)
{

    return array();

    $filelist = trim(shell_exec("find '$path' -name '$pic*.tif'"));
    $ret = array();
    if (trim($filelist)) {
        $files = explode("\n", $filelist);
        for ($i = 0; $i < count($files); $i++) {
            $name = basename($files[$i]);
            if (!isset($ret[$name])) {
                $ret[$name] = array('path'  => trim($files[$i]),
                                    'count' => 1);
            } else {
                $ret[$name]['count']++;
            }
        }
    }

    return $ret;
}


function symlinkImages($specimenID, $dir)
{
    $sql = "SELECT HerbNummer, specimen_ID, coll_short_prj, HerbNummerNrDigits,
             tbl_specimens.collectionID, tbl_management_collections.source_id
            FROM tbl_specimens, tbl_management_collections, tbl_img_definition
            WHERE tbl_specimens.collectionID = tbl_management_collections.collectionID
             AND tbl_management_collections.source_id = tbl_img_definition.source_id_fk
             AND specimen_ID = '" . intval($specimenID) . "'";
    $result = dbi_query($sql);
    if ($row = mysqli_fetch_array($result)) {
        $path = $row['img_directory'] . "/";
        $pic = $row['coll_short_prj'] . "_";
        $picNumber = "";
        if ($row['HerbNummer']) {
            if (strpos($row['HerbNummer'],"-") === false) {
                if ($row['collectionID'] == 89) {
                    $picNumber .= sprintf("%08d", $row['HerbNummer']);
                } else {
                    $picNumber .= sprintf("%0" . $row['HerbNummerNrDigits'] . "d", $row['HerbNummer']);
                }
            } else {
              $picNumber .= str_replace("-", "", $row['HerbNummer']);
            }
        } else {
            $picNumber .= $row['specimen_ID'];
        }
        $pic .= $picNumber;

        $search = array();
        if ($row['source_id'] == 1 || $row['source_id'] == 6) {  // w and wu
            $search['img'] = array(array('path' => str_replace("web", "originale", $path),
                                         'pic'  => $pic),
                                   array('path' => str_replace("web", "herbed01/specimens/originale", $path),
                                         'pic'  => $pic));
        } elseif ($row['source_id'] == 4) {  // gzu
            $search['img'] = array(array('path' => str_replace("web", "originale", $path),
                                         'pic'  => $pic));
        } elseif ($row['source_id'] == 13) {  //hdrog
            $search['img'] = array(array('path' => str_replace("web", "herbed01/specimens/originale", $path),
                                         'pic'  => $pic));
        } else {
            $search['img'] = array(array('path' => str_replace("web", "originale/other herbaria", $path),
                                         'pic'  => $pic));
        }
        $search['tab'] = array(array('path' => str_replace("web", "originale", $path),
                                     'pic'  => "tab_" . $row['specimen_ID']),
                               array('path' => str_replace("tabulae/web", "specimens/herbed01/tabulae/originale", $path),
                                     'pic'  => "tab_" . $row['specimen_ID']));
        $search['obs'] = array(array('path' => str_replace("web", "originals", $path),
                                     'pic'  => "obs_" . $row['specimen_ID']));

        foreach ($search as $searchType => $searchEntries) {
            $found = false;
            foreach ($searchEntries as $searchEntry) {
                $files = getPicturePaths($searchEntry['path'], $searchEntry['pic']);
                if (count($files) > 0) {
                    $found = true;
                    foreach ($files as $file) {
                        if ($file['count'] > 0) {
                            $parts = explode('_', basename($file['path']), 2);
                            $symlink = $dir . "/" . strtoupper($parts[0]) . $parts[1];
                            if ($file['count'] == 1) {
                                if (@symlink($file['path'], $symlink)) {
                                    echo "$symlink &rarr; " . $file['path'] . "<br>\n";
                                } elseif (file_exists($symlink)) {
                                    echo "$symlink already there<br>\n";
                                } else {
                                    echo "$symlink error<br>\n";
                                }
                            } else {
                                echo "$symlink <b>found more than once!</b><br>\n";
                            }
                        }
                    }
                }
                if ($found) break;
            }
            if (!$found && $searchType == 'img') {
                $parts = explode('_', $pic, 2);
                $symlink = $dir . "/" . strtoupper($parts[0]) . $parts[1];
                echo "$symlink &rarr; <b>original missing</b><br>\n";
            }
        }
    }
}

class XML_api     // special Class for API
{
    var $content;   // the XML-Data
    var $indent;    // actual indent level
    var $pad;       // spaces for indention

    function XML_api($header = "")
    {
        $this->indent = 0;
        $this->pad = "  ";
        $this->content = $header;
    }

    function addSingle($tag, $value, $optional=false)
    {
        if (!$optional || strlen($value) > 0) {
            $this->content .= str_repeat($this->pad,$this->indent)."<$tag>".htmlspecialchars($value, ENT_NOQUOTES)."</$tag>\n";
            //$this->content .= str_repeat($this->pad,$this->indent)."<$tag>$value</$tag>\n";
        }
    }

    function addMultiBegin($tag, $value="")
    {
        $this->content .= str_repeat($this->pad,$this->indent) . "<$tag";
        if ($value) $this->content .= " " . htmlspecialchars($value, ENT_NOQUOTES);
        //if ($value) $this->content .= " $value";
        $this->content .= ">\n";
        $this->indent++;
    }

    function addMultiEnd($tag)
    {
        $this->indent--;
        $this->content .= str_repeat($this->pad, $this->indent) . "</$tag>\n";
    }

    function addDate($tag, $startDate, $endDate="")
    {
        $this->addMultiBegin($tag);
        if (strlen($startDate)<1 || trim(strtolower($startDate))=="s.d.") {
            $this->addSingle("OtherText", "Not on sheet");
        } else {
            $pieces = explode("-", $startDate);
            if( count($pieces) >= 3 ) {
                $this->addSingle("StartDay", $pieces[2], true);
                $this->addSingle("StartMonth", $pieces[1], true);
                $this->addSingle("StartYear", $pieces[0], true);
            }
        }

        $pieces = explode("-", $endDate);
        if( count($pieces) >= 3 ) {
            $this->addSingle("EndDay", $pieces[2], true);
            $this->addSingle("EndMonth", $pieces[1], true);
            $this->addSingle("EndYear", $pieces[0], true);
        }

        $this->addMultiEnd($tag);
    }
}
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Batch</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>

<body>

<h1>Unsent API-Batches:</h1>

<ul>
<?php
$sql = "SELECT remarks, date_supplied, batchID
        FROM api.tbl_api_batches
        WHERE sent='0'
        ORDER BY date_supplied DESC";
$result = dbi_query($sql);
while ($row=mysqli_fetch_array($result)) {
    echo "<li><a href=\"" . $_SERVER['PHP_SELF'] . "?ID=" . $row['batchID'] . "\">"
       . $row['date_supplied'] . " (" . trim($row['remarks']) . ") <" . $row['batchID'] . "></a>";
    if ($batchID == $row['batchID']) echo "&nbsp;<b>processed</b>";
    echo "</li>";
}
?>
</ul>
<?php
if ($batchID) {
    $dir_base = $basedir . "/" . $dirprefix . sprintf("%03d", $batchID);
    @mkdir($dir_base);

    // Fetch date_supplied
    $sql = "SELECT ab.`date_supplied` FROM `api`.`tbl_api_batches` ab WHERE ab.`batchID` = " . quoteString($batchID);
    $result = dbi_query($sql);
    $row = mysqli_fetch_array($result);
    $date_supplied = $row['date_supplied'];

    // set `source_update` to the current date only for the used institutions
    $sql = "SELECT source_id_fk
            FROM api.tbl_api_units, api.tbl_api_specimens
            WHERE api.tbl_api_units.specimenID = api.tbl_api_specimens.specimen_ID
             AND batchID_fk = " . quoteString($batchID) . "
            GROUP BY source_id_fk";
    $result = dbi_query($sql);
    while ($row=mysqli_fetch_array($result)) {
        dbi_query("UPDATE herbarinput.metadata SET source_update = NOW() WHERE source_id = " . quoteString($row['source_id_fk']));
    }

    $result = dbi_query("SELECT * FROM herbarinput.meta, herbarinput.metadb WHERE herbarinput.meta.source_id = herbarinput.metadb.source_id_fk");
    while ($row=mysqli_fetch_array($result)) {
        $sql = "SELECT *
                FROM api.tbl_api_units, api.tbl_api_specimens
                WHERE api.tbl_api_units.specimenID = api.tbl_api_specimens.specimen_ID
                 AND source_id_fk = " . quoteString($row['source_id']) . "
                 AND batchID_fk = " . quoteString($batchID) . "
                ORDER BY UnitID";
        $result2 = dbi_query($sql);
        if (mysqli_num_rows($result2) > 0) {
            $dir = $dir_base . "/" . $row['source_code'];
            mkdir($dir);

            $xml = new XML_api("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<!-- edited with php -->\n<!--" . date('j F Y') . "-->\n");
            $xml->addMultiBegin("DataSet","xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:noNamespaceSchemaLocation=\"http://plants.jstor.org/XSD/AfricanTypesv2.xsd\"");

            $xml->addSingle("InstitutionCode", $row['source_code']);
            $xml->addSingle("InstitutionName", $row['source_name']);
            $xml->addSingle("DateSupplied", $date_supplied);
            $xml->addSingle("PersonName", $row['supplier_person']);

            while ($row2=mysqli_fetch_array($result2)) {
                $xml->addMultiBegin("Unit");

                $xml->addSingle("UnitID", $row2['UnitID']);
                $xml->addSingle("DateLastModified", $row2['DateLastModified']);

                $sql = "SELECT *
                        FROM api.tbl_api_units_identifications
                        WHERE specimenID_fk = " . quoteString($row2['specimenID']) . "
                        ORDER BY StoredUnderName DESC";
                $result3 = dbi_query($sql);
                if (mysqli_num_rows($result3) > 0) {
                    while ($row3=mysqli_fetch_array($result3)) {
                        $xml->addMultiBegin("Identification", "StoredUnderName=\"" . $row3['StoredUnderName'] . "\"");

                        $xml->addSingle("Family", $row3['Family']);
                        $xml->addSingle("GenusQualifier", "", true);     // not in use
                        $xml->addSingle("Genus", $row3['Genus']);
                        $xml->addSingle("SpeciesQualifier", "", true);   // not in use
                        $xml->addSingle("Species", $row3['Species']);
                        $xml->addSingle("Author", $row3['Author']);
                        $xml->addSingle("Infra-specificRank", $row3['Infra_specificRank'], true);
                        $xml->addSingle("Infra-specificEpithet", $row3['Infra_specificEpithet'], true);
                        $xml->addSingle("Infra-specificAuthor", $row3['Infra_specificAuthor'], true);
                        $xml->addSingle("PlantNameCode", "", true);     // not in use
                        $xml->addSingle("Identifier", $row3['Identifier']);
                        $xml->addDate("IdentificationDate", $row3['IdentificationDate']);
                        $xml->addSingle("TypeStatus", ($row3['TypifiedName'] == 'false') ? '-' : $row3['Typestatus']);

                        $xml->addMultiEnd("Identification");
                    }
                }

                $xml->addSingle("Collectors", $row2['Collectors']);
                $xml->addSingle("CollectorNumber", $row2['CollectorNumber']);
                $xml->addDate("CollectionDate", $row2['CollectionDateBegin'], $row2['CollectionDateEnd']);
                if ($row2['CountryName']) {
                    $xml->addSingle("CountryName", $row2['CountryName'], true);
                    $xml->addSingle("ISO2Letter", $row2['ISO2Letter'], true);
                } else {
                    $xml->addSingle("CountryName", 'ZZ', true);
                    $xml->addSingle("ISO2Letter", 'ZZ', true);
                }
                if( !empty($row2['ProvinceName']) ) {
                    $row2['Locality'] = $row2['ProvinceName'] . ': ' . $row2['Locality'];
                }
                $xml->addSingle("Locality", $row2['Locality'], true);
                $xml->addSingle("Altitude", $row2['Altitude_min'], true);
                $xml->addSingle("Notes", $row2['Notes'], true);

                $xml->addMultiEnd("Unit");

                symlinkImages($row2['specimenID'], $dir);
            }

            $xml->addMultiEnd("DataSet");

            $filename = $fileprefix . "_" . $row['source_code'] . "_batch" . sprintf("%03d", $batchID) . "." . $extension;
            $handle = fopen($dir_base . "/" . $row['source_code'] . "/" . $filename, 'w');
            fwrite($handle, $xml->content);
            fclose($handle);

            unset($xml);
        }
    }
}
?>

</body>
</html>