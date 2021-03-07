#!/usr/bin/php -q
<?php
$host = "localhost";      // hostname
$user = "hdb_".$argv[1];  // username
$pass = $argv[2];         // password
$db   = "herbarinput";    // database

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


$sql = "SELECT specimen_ID, HerbNummer
        FROM tbl_specimens
        WHERE SUBSTRING(HerbNummer,1,1)='1'
         AND INSTR(HerbNummer,'-')!=0";
$result = $dbLink->query($sql);
while ($row = mysqli_fetch_array($result)) {
  if (($pos = strpos($row['HerbNummer'],"-"))!==false) {
    for ($end = $pos + 1; $end < strlen($row['HerbNummer']); $end++) {
        if (!ctype_digit(substr($row['HerbNummer'], $end, 1))) {
            break;
        }
    }
    if (($end - $pos) != 8) {
      $newnum = substr($row['HerbNummer'], 0, $pos) . "-" . sprintf("%07d", substr($row['HerbNummer'], $pos + 1, $end - 1)) . substr($row['HerbNummer'], $end);
      $sql = "UPDATE tbl_specimens
              SET HerbNummer = '$newnum'
              WHERE specimen_ID = '" . $row['specimen_ID'] . "'";
      $dbLink->query($sql);
      //echo sprintf("%10s - %20s - %20s\n",$row['specimen_ID'],$row['HerbNummer'],$newnum);
    }
  }
}
?>