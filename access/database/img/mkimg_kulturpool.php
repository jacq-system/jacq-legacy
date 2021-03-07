<?php
/**
 * CONFIG STUFF
 * */
$destDir = "/mnt/ext_usb/kulturpool/";    // Destination Directory
$geometry = "667x1000";                   // Output Geometry (passed to ImageMagick)
$format = "jpc";                          // Output Format (used as filter for ImageMagick and file-extension)

/**
 * PRE STUFF
 * */
class Picture {
  var $basepath;
  var $path;
  var $pic;

  function findPicture() {
    $filelist = shell_exec("find " . $this->basepath . " -name '" . basename($this->pic) . "*'");
    $parts = explode("\n", $filelist);
    $path_parts = pathinfo(trim($parts[0]));

    if ($path_parts['extension']=='tif' || $path_parts['extension']=='jpg' || $path_parts['extension']=='nef') {
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
             tbl_specimens.collectionID
            FROM tbl_specimens, tbl_management_collections, tbl_img_definition
            WHERE tbl_specimens.collectionID = tbl_management_collections.collectionID
             AND tbl_management_collections.source_id = tbl_img_definition.source_id_fk
             AND specimen_ID = '" . intval($id) . "'";
    $result = dbi_query($sql);
    $row = mysqli_fetch_array($result);

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

/**
 * MAIN-PART
 * */
if( $argc <= 1 ) {
  echo "Usage: mkimg.php <input_file>\n" .
        "\t<input_file>: Name of File containing a list of all image-names to export.\n";
  exit( 0 );
}

//header ("Content-type: image/jpeg");
$fp = fopen( $argv[1], "r");

if( !$fp ) die( "Unable to open input file!" );

require_once("../../inc/connect.php");
//require_once("inc/getPathPic.php");

// Get list of files already on the drive
$scanList = scandir( $destDir );
// Remove '.' and '..' entries
unset( $scanList[0] );
unset( $scanList[1] );
// Convert into index-array for easy checking
$dirContent = array();
foreach( $scanList as $dirEntry ) {
  $dirContent[$dirEntry] = 1;
}

while( !feof($fp) ) {
  $usedName = trim( fgets($fp) );

  /* Conversion Code taken from getPathPic.php */
  $picture = new Picture();
  $path = "";
  $pic = "";
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
      $result = dbi_query($sql);
      $row = mysqli_fetch_array($result);
      $picture->basepath = $row['img_directory']."/";
      $picture->pic = $row['coll_short_prj']."_".$pieces[1];
    }
    if ($picture->findPicture()) {
      $path = $picture->path;
      $pic = $picture->pic;
    }
  }

  if( $path == "" && $pic == "" ) {
    echo "WARNING: No Image found for '$usedName'!\n";
    continue;
  }

  $newpic = basename( $pic, '.tif' ) . ".$format";
  //if( !file_exists($destDir . $newpic) ) {
  if( !isset($dirContent[$newpic]) ) {
    echo "Converting Image '" . $path . $pic . "' to '" . $destDir . $newpic . "'!\n";
    passthru("convert -geometry $geometry $path$pic $format:" . $destDir . $newpic );
    // Check if there where two files embedded
    if( file_exists( $destDir . basename( $newpic, '.' . $format ) . "-0." . $format ) ) {
      echo "Found file with embedded TIFFs, converting -0 to the master!\n";

      passthru( "mv " . $destDir . basename( $newpic, '.' . $format ) . "-0." . $format . " " . $destDir . $newpic );
      unlink( $destDir . basename( $newpic, '.' . $format ) . "-1." . $format );
    }
  }
  else {
    //echo "NOTE: Image '" . $destDir . $newpic . "' already exists!\n";
    unset($dirContent[$newpic]);
  }

  // Finally convert the image
  //passthru( "convert -geometry $geometry $path$pic JPC:" . $destDir . $newpic );
  // Now add it to the zipfile
  //passthru( "zip -j /mnt/ext_usb/kulturpool/" . basename($argv[1], '.txt' ) . ".zip /mnt/ext_usb/kulturpool/$newpic" );
  //Finally delete the created thumbnail file
  //unlink( "/mnt/ext_usb/kulturpool/$newpic" );
}

echo "Here is a list of all files in the destDir wich do not exist in the file-List:\n";
print_r( $dirContent );
