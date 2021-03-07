<?php
$key = $_GET['key'];
if ($key!='DKsuuewwqsa32czucuwqdb576i12') die('');  // security

ob_start();

require_once('../inc/connect.php');

$id = intval($_GET['ID']);

class Picture {
  var $path;
  var $pic;

  function getPicturePaths() {
    $filelist = shell_exec("find " . $this->path . " -name '" . basename($this->pic) . ".*' -or -name '" . basename($this->pic) . "_*'");
    $files = explode("\n", $filelist);
    for ($i=0;$i<count($files);$i++)
      $files[$i] = trim($files[$i]);

    return $files;
  }
}

$picture = new Picture();

$sql = "SELECT HerbNummer, specimen_ID, coll_short_prj, img_directory, img_obs_directory, img_tab_directory, HerbNummerNrDigits,
         tbl_specimens.collectionID, tbl_management_collections.source_id
        FROM tbl_specimens, tbl_management_collections, tbl_img_definition
        WHERE tbl_specimens.collectionID = tbl_management_collections.collectionID
         AND tbl_management_collections.source_id = tbl_img_definition.source_id_fk
         AND specimen_ID = '$id'";
$result = dbi_query($sql);
if (mysqli_num_rows($result) > 0) {
      $row = mysqli_fetch_array($result);

      // ----- pictures of specimen -----
      $picture->path = $row['img_directory'] . "/";
      $picture->pic = $row['coll_short_prj'] . "_";
      if ($row['HerbNummer']) {
          if (strpos($row['HerbNummer'], "-") === false) {
              if ($row['collectionID'] == 89) {
                  $picture->pic .= sprintf("%08d", $row['HerbNummer']);
              } else {
                  $picture->pic .= sprintf("%0" . $row['HerbNummerNrDigits'] . "d", $row['HerbNummer']);
              }
          } else {
              $picture->pic .= str_replace("-", "", $row['HerbNummer']);
          }
      } else {
          $picture->pic .= $row['specimen_ID'];
      }
}

$transfer['output'] = ob_get_clean();
$transfer['pics'] = array();

if (mysqli_num_rows($result) > 0) {
    //foreach(glob($path.$pic."*") as $v)
    //  print basename($v)."\n";
    foreach($picture->getPicturePaths() as $v)
      if ($v) $transfer['pics'][] = basename($v);

    //----- observation pictures -----
    $picture->path = $row['img_obs_directory'] . "/";
    $picture->pic  = "obs_".$row['specimen_ID'];
    foreach($picture->getPicturePaths() as $v)
      if ($v) $transfer['pics'][] = basename($v);

    //----- tabulae pictures -----
    $picture->path = $row['img_tab_directory'] . "/";
    $picture->pic  = "tab_".$row['specimen_ID'];
    foreach($picture->getPicturePaths() as $v)
      if ($v) $transfer['pics'][] = basename($v);
}

print serialize($transfer);