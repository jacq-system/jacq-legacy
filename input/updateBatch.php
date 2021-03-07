<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/api_functions.php");

//---------- check every input ----------
$lock = false;
if (!checkRight('batch'))  // only user with right "batch" can change API
  $lock = true;

if ($_GET['sw']==1) { // delete all unsent batches
  $type = 1;
  $id = intval($_GET['nr']);
}
else if ($_POST['submitUpdate']) {  // edit the entries or insert new ones
  $type = 2;
  $id = intval($_POST['ID']);
  $batch_id = intval($_POST['batch']);
}
else if (isset($_GET['del'])) {  // delete single entry, then act like standard
  $type = 3;
  $id = intval($_GET['nr']);
  $del_id = intval($_GET['del']);
  if ($del_id==0) $lock = true;
}
else {  // standard
  $type = 4;
  $id = intval($_GET['nr']);
}

if ($id==0)
  $lock = true;
//---------- check finished ----------

// something went very wrong
if ($lock) {
  echo "<html><head></head><body>\n".
       "<h1>Error</h1>\n".
       "</body></html>\n";
  die();
}

if (!checkRight('batchAdmin')) {  // if group of user is no batch Administrator further checking is necessary
  $sql = "SELECT sourceID_fk
          FROM tbl_api_batches
          WHERE ";
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Batch</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>

<body>

<?php
switch ($type) {
case 1: // delete all unsent batches
  $sql = "SELECT specimen_ID, batchID_fk
          FROM api.tbl_api_specimens, api.tbl_api_batches
          WHERE batchID=batchID_fk
           AND (sent='0' OR sent IS NULL)
           AND specimen_ID='$id'";
  if (!checkRight('batchAdmin')) $sql .= " AND api.tbl_api_batches.sourceID_fk=".$_SESSION['sid'];  // check right and sourceID
  $result = dbi_query($sql);
  while ($row=mysqli_fetch_array($result)) {
    $sql = "DELETE FROM api.tbl_api_specimens
            WHERE specimen_ID='".$row['specimen_ID']."'
             AND batchID_fk='".$row['batchID_fk']."'";
    dbi_query($sql);
  }

  // garbage collection: if tbl_api_specimens is empty -> delete tbl_api_units and tbl_api_units_identifications
  garbageCollection($id);

  echo "<script language=\"JavaScript\">\n".
       "  self.close();\n".
       "</script>\n";
  break;

case 2: // edit the entries or insert new ones
  // edit tbl_api_specimens
  $sql = "SELECT api_specimensID, batchID_fk
          FROM api.tbl_api_specimens, api.tbl_api_batches
          WHERE batchID=batchID_fk
           AND (sent='0' OR sent IS NULL)
           AND specimen_ID='$id'";
  if (!checkRight('batchAdmin')) $sql .= " AND api.tbl_api_batches.sourceID_fk=".$_SESSION['sid'];  // check right and sourceID
  $result = dbi_query($sql);
  while ($row=mysqli_fetch_array($result)) {
    $bid = 'batch'.$row['api_specimensID'];
    if (isset($_POST[$bid]) && intval($_POST[$bid])!=$row['batchID_fk']) {
      $sql = "UPDATE api.tbl_api_specimens
              SET batchID_fk='".intval($_POST[$bid])."'
              WHERE api_specimensID='".$row['api_specimensID']."'";
      dbi_query($sql);
    }
  }

  // insert new entry
  if ($batch_id!=-1) {
    $blocked = false;
    if (!checkRight('batchAdmin')) {
      $sql = "SELECT source_id
              FROM tbl_specimens, tbl_management_collections
              WHERE tbl_specimens.collectionID=tbl_management_collections.collectionID
               AND specimen_ID='$id'";
      $row = dbi_query($sql)->fetch_array();
      if ($row['source_id'] != $_SESSION['sid'])
        $blocked = true;
    }

    if (!$blocked) {
      $sql = "INSERT INTO api.tbl_api_specimens SET
               specimen_ID='$id',
               batchID_fk='$batch_id'";
      dbi_query($sql);
    }
  }

  // update or insert into update_tbl_api_units
  $result = update_tbl_api_units($id);
  update_tbl_api_units_identifications($id);
  garbageCollection($id);
  if ($result) {
    echo "<script language=\"JavaScript\">\n".
         "  self.close();\n".
         "</script>\n";
  }
  break;

case 3: // delete single entry
  if (!checkRight('batchAdmin')) {
    $sql = "SELECT api_specimensID
            FROM api.tbl_api_specimens, api.tbl_api_batches
            WHERE batchID=batchID_fk
             AND api_specimensID='$del_id'
             AND api.tbl_api_batches.sourceID_fk=".$_SESSION['sid'];  // check right and sourceID
    $result = dbi_query($sql);
    $lock = (mysqli_num_rows($result)>0) ? false : true;
  }
  else
    $lock = false;
  if (!$lock) {   // user may delete
    dbi_query("DELETE FROM api.tbl_api_specimens WHERE api_specimensID='$del_id'");
    garbageCollection($id);
  }
  // no break, type 3 acts like type 4 after deleting the single entry

case 4: // standard
  unset($newbatchValue);
  unset($newbatchText);
  $newbatchValue[] = -1;
  $newbatchText[] = "";
  unset($batchValue);
  unset($batchText);
  $sql = "SELECT remarks, date_supplied, batchID, batchnumber, source_code
          FROM api.tbl_api_batches
           LEFT JOIN herbarinput.meta ON api.tbl_api_batches.sourceID_fk=herbarinput.meta.source_id
          WHERE sent='0'";
  if (!checkRight('batchAdmin')) $sql .= " AND api.tbl_api_batches.sourceID_fk=".$_SESSION['sid'];  // check right and sourceID
  $sql .= " ORDER BY source_code, batchnumber, date_supplied DESC";
  $result = dbi_query($sql);
  while ($row=mysqli_fetch_array($result)) {
    $batchValue[] = $newbatchValue[] = $row['batchID'];
    $batchNr = " <".(($row['source_code']) ? $row['source_code']."-" : "").$row['batchnumber']."> ";
    $batchText[] = $newbatchText[] = $row['date_supplied']."$batchNr (".htmlspecialchars(trim($row['remarks'])).")";
  }

  echo "<form name=\"f\" Action=\"".$_SERVER['PHP_SELF']."\" Method=\"POST\">\n";

  $cf = new CSSF();

  echo "<input type=\"hidden\" name=\"ID\" value=\"$id\">\n";
  $cf->label(8,0.5,"ID");
  $cf->text(8,0.5,"&nbsp;$id");

  $sql = "SELECT api_specimensID, specimen_ID, batchID_fk, sent, date_supplied, remarks
          FROM api.tbl_api_specimens, api.tbl_api_batches
          WHERE batchID=batchID_fk
           AND specimen_ID='$id'";
  if (!checkRight('batchAdmin')) $sql .= " AND api.tbl_api_batches.sourceID_fk=".$_SESSION['sid'];  // check right and sourceID
  $result = dbi_query($sql);
  $y = 2;
  while ($row=mysqli_fetch_array($result)) {
    $cf->label(8,$y,"Batch");
    if ($row['sent'])
      $cf->text(8,$y,"&nbsp;".$row['date_supplied']." (".trim($row['remarks']).")");
    else {
      $cf->dropdown(8,$y,"batch".$row['api_specimensID'],$row['batchID_fk'],$batchValue,$batchText);
      $link = $_SERVER['PHP_SELF']."?nr=$id&del=".$row['api_specimensID'];
      $cf->buttonLink(1,$y," Del",$link,0,"red");
    }
    $y += 2;
  }
  $cf->label(8,$y,"new Batch");
  $cf->dropdown(8,$y,"batch",-1,$newbatchValue,$newbatchText);

  $cf->buttonSubmit(8,$y+4,"submitUpdate"," OK ");

  echo "</form>\n";
} // end switch ($type)
?>
</body>
</html>