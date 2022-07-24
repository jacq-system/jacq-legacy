<?php
require_once( "../inc/connect.php" );

$basedir = "/api-batches/export";
$dirprefix = "batch";
$fileprefix = "API";
$extension = "xml";

$batchID = intval($_GET['ID']);

function symlinkImages($specimenID, $dir) {

    $sql = "SELECT HerbNummer, specimen_ID, coll_short_prj, HerbNummerNrDigits,
             tbl_specimens.collectionID
            FROM tbl_specimens, tbl_management_collections, tbl_img_definition
            WHERE tbl_specimens.collectionID = tbl_management_collections.collectionID
             AND tbl_management_collections.source_id = tbl_img_definition.source_id_fk
             AND specimen_ID = '" . intval($specimenID) . "'";
    $result = dbi_query($sql);
    if ($row = mysqli_fetch_array($result)) {
        $path = $row['img_directory'] . "/";
        $pic = $row['coll_short_prj'] . "_";
        $symlink = $dir . "/" . strtoupper($row['coll_short_prj']);
        $picNumber = "";
        if ($row['HerbNummer']) {
            if (strpos($row['HerbNummer'], "-") === false) {
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
        $symlink .= $picNumber;

        $picLink = trim(shell_exec("find $path -name $pic.tif"));
        if (strlen($picLink) > 0) {
            echo "$symlink &rarr; $picLink<br>\n";
            @symlink($picLink, $symlink . ".tif");
        } else {
            echo "$symlink &rarr; <b>original missing</b><br>\n";
        }
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

<?php
if ($batchID) {
  $dir_base = $basedir."/".$dirprefix.sprintf("%03d",$batchID)."/web";
  @mkdir($dir_base);

  $result = dbi_query("SELECT * FROM herbarinput.meta, herbarinput.metadb WHERE herbarinput.meta.source_id=herbarinput.metadb.source_id_fk");
  while ($row=mysqli_fetch_array($result)) {
    $sql = "SELECT *
            FROM api.tbl_api_units, api.tbl_api_specimens
            WHERE api.tbl_api_units.specimenID=api.tbl_api_specimens.specimen_ID
             AND source_id_fk=".quoteString($row['source_id'])."
             AND batchID_fk=".quoteString($batchID)."
            ORDER BY UnitID";
    $result2 = dbi_query($sql);
    if (mysqli_num_rows($result2)>0) {
      $dir = $dir_base."/".$row['source_code'];
      @mkdir($dir);

      while ($row2=mysqli_fetch_array($result2)) {
        symlinkImages($row2['specimenID'], $dir);
      }
    }
  }
}
?>

</body>
</html>