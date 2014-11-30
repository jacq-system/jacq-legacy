#!/usr/bin/php -qC
<?php
$host = "localhost";    // hostname
$user = "gbif";         // username
$pass = "gbif";         // password
$db   = "herbarinput";  // database

ini_set("max_execution_time","3600");

mysql_connect($host,$user,$pass) or die("Database not available!");
mysql_select_db($db) or die ("Access denied!");
mysql_query("SET character set utf8");

function db_query($sql) {
  $result = @mysql_query($sql);
  if (!$result) {
    echo $sql."\n";
    echo mysql_error()."\n";
  }
  return $result;
}
function quoteString($text) {

  if (strlen($text)>0)
    return "'".mysql_escape_string($text)."'";
  else
    return "NULL";
}


if ($argc != 2 || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
?>
This is a command line PHP script with one option.

  Usage:
  <?php echo $argv[0]; ?> <batch-number>

<?php
} else {
  $resultID = db_query("SELECT specimen_ID FROM api.tbl_api_specimens WHERE batchID_fk='".intval($argv[1])."'");
  while ($rowID=mysql_fetch_array($resultID)) {
    $sql = "SELECT HerbNummer, specimen_ID, img_coll_short, img_directory, tbl_specimens.collectionID ".
           "FROM tbl_specimens, tbl_management_collections, tbl_img_definition ".
           "WHERE tbl_specimens.collectionID=tbl_management_collections.collectionID ".
            "AND tbl_management_collections.source_id=tbl_img_definition.source_id_fk ".
            "AND specimen_ID='".$rowID['specimen_ID']."'";
    $result = mysql_query($sql);
    if ($row=mysql_fetch_array($result)) {
      $path = $row['img_directory']."/";
      $pic = $row['img_coll_short']."_";
      $symlink = strtoupper($row['img_coll_short']);
      $picNumber = "";
      if ($row['HerbNummer']) {
        if (strpos($row['HerbNummer'],"-")===false) {
          if ($row['collectionID']==21) $picNumber .= "rchb_orch_";
          $picNumber .= sprintf("%07d",$row['HerbNummer']);
        } else
          $picNumber .= str_replace("-","",$row['HerbNummer']);
      } else
        $picNumber .= $row['specimen_ID'];
      $pic .= $picNumber;
      $symlink .= $picNumber;

      $path = str_replace("web","originale",$path);

      $pic = trim(shell_exec("find $path -name $pic.tif"));

      if (strlen($pic)>0)
        symlink($pic,$symlink.".tif");
    }
  }
}
?>
