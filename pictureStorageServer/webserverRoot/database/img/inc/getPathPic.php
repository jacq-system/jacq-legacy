<?php
class Picture {
  var $basepath;
  var $path;
  var $pic;

  function findPicture() {
    $filelist = shell_exec("find " . $this->basepath . " -name '" . basename($this->pic) . "*'");
    $parts = explode("\n", $filelist);
    $path_parts = pathinfo(trim($parts[0]));

    if ($path_parts['extension']=='tif' || $path_parts['extension']=='jpg' || $path_parts['extension']=='nef' || $path_parts['extension']=='jpc' || $path_parts['extension']=='jp2') {
      $this->path = $path_parts['dirname'] . "/";
      $this->pic  = $path_parts['basename'];
      return true;
    }
    else
      return false;
  }
}

function getData($id) {
    $sql = "SELECT HerbNummer, specimen_ID, coll_short_prj, img_directory, img_obs_directory, img_tab_directory, HerbNummerNrDigits,
             tbl_specimens.collectionID, tbl_management_collections.source_id
            FROM tbl_specimens, tbl_management_collections, tbl_img_definition
            WHERE tbl_specimens.collectionID = tbl_management_collections.collectionID
             AND tbl_management_collections.source_id = tbl_img_definition.source_id_fk
             AND specimen_ID = '" . intval($id) . "'";
    $result = mysql_query($sql);
    $row = mysql_fetch_array($result);

    return $row;
}

function getSpecimenPicName($row) {
    $pic = $row['coll_short_prj'] . "_";
    if ($row['HerbNummer']) {
        if (strpos($row['HerbNummer'], "-") === false) {
            if ($row['collectionID'] == 89) {
                $pic .= sprintf("%08d", $row['HerbNummer']);
            } else {
                $pic .= sprintf("%0" . $row['HerbNummerNrDigits'] . "d", $row['HerbNummer']);
            }
        } else {
            $pic .= str_replace("-", "", $row['HerbNummer']);
        }
    } else {
        $pic .= $row['specimen_ID'];
    }

    return $pic;
}

//-----------
// main part
//-----------
$picture = new Picture();

$path = "pics/";
$pic = "blind.png";
if (is_numeric($usedName)) {
  $row = getData($usedName);
  $ID = $row['specimen_ID'];

  for ($i=1;$i<=3;$i++) {
    switch ($i) {
      case 1:
        $picture->basepath = $row['img_directory']."/";
        $picture->pic = getSpecimenPicName($row);
        break;
      case 2:
        $picture->basepath = $row['img_obs_directory']."/";
        $picture->pic = "obs_".$row['specimen_ID'];
        break;
      case 3:
        $picture->basepath = $row['img_tab_directory']."/";
        $picture->pic = "tab_".$row['specimen_ID'];
        break;
    }
    if ($picture->findPicture()) {
      $path = $picture->path;
      $pic = $picture->pic;
      break;
    }
  }
}
else {
  $pieces = explode("_",basename($usedName),2);
  if ($pieces[0]=='obs') {
    $row = getData($pieces[1]);
    $ID = $row['specimen_ID'];
    $picture->basepath = $row['img_obs_directory']."/";
    $picture->pic = "obs_".$pieces[1];
  }
  elseif ($pieces[0]=='tab') {
    $row = getData($pieces[1]);
    $ID = $row['specimen_ID'];
    $picture->basepath = $row['img_tab_directory']."/";
    $picture->pic = "tab_".$pieces[1];
  }
  else {
    $ID = (isset($_GET['ID'])) ? $_GET['ID'] : 0;
//    $sql = "SELECT img_directory, img_coll_short ".
//           "FROM tbl_img_definition ".
//           "WHERE img_coll_short='".$pieces[0]."'";
    $sql = "SELECT img_directory, coll_short_prj
            FROM tbl_management_collections mc, tbl_img_definition id
            WHERE mc.source_id=id.source_id_fk
             AND coll_short_prj='".$pieces[0]."'";
    $result = mysql_query($sql);
    $row = mysql_fetch_array($result);
    $picture->basepath = $row['img_directory']."/";
    $picture->pic = $row['coll_short_prj']."_".$pieces[1];
  }
  if ($picture->findPicture()) {
    $path = $picture->path;
    $pic = $picture->pic;
  }
}