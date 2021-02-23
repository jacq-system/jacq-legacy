#!/usr/bin/php -qC
<?php
$host = "localhost";    // hostname
$user = "gbif";         // username
$pass = "gbif";         // password
$db   = "herbarinput";  // database

ini_set("max_execution_time","3600");

class DB extends mysqli {

    public function __construct($host, $user, $pass, $db)
    {
        parent::__construct($host, $user, $pass, $db);

        if (mysqli_connect_error()) {
            die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
        }

        $this->query("SET character set utf8");
    }

    public function query($query, $resultmode = MYSQLI_STORE_RESULT)
    {
        $result = parent::query($query, $resultmode);
        if (!$result) {
            echo $query . "\n";
            echo $this->error . "\n";
        }

        return $result;
    }
}
$dbLink  = new DB($host, $user, $pass, $db);


if ($argc != 2 || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
    echo "This is a command line PHP script with one option.\n"
       . "\n"
       . "  Usage:\n"
       . "  " . $argv[0] . " <batch-number>\n"
       . "\n";
} else {
  $resultID = $dbLink->query("SELECT specimen_ID FROM api.tbl_api_specimens WHERE batchID_fk='".intval($argv[1])."'");
  while ($rowID = mysqli_fetch_array($resultID)) {
    $sql = "SELECT HerbNummer, specimen_ID, img_coll_short, img_directory, tbl_specimens.collectionID ".
           "FROM tbl_specimens, tbl_management_collections, tbl_img_definition ".
           "WHERE tbl_specimens.collectionID=tbl_management_collections.collectionID ".
            "AND tbl_management_collections.source_id=tbl_img_definition.source_id_fk ".
            "AND specimen_ID='".$rowID['specimen_ID']."'";
    $result = $dbLink->query($sql);
    if ($row = mysqli_fetch_array($result)) {
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