<?php
require_once("../inc/connect.php");

// Connect to database
db_connect( $_CONFIG['DATABASES']['INPUT'] );

header ("Content-type: image/jpeg");

/*
als Sicherung:
IP des Aufrufers in imgBrowser und showPic �berpr�fen und von 1. zum 2. senden
aktuelle zeit mitschicken und in showPic �berpr�fen
*/

function allocatePicture($path, $pic) {
  $filelist = shell_exec("find " . $path . " -name '" . basename($pic) . "'");
  $parts = explode("\n", $filelist);

  return trim($parts[0]);
}

$pieces = explode("_",basename($_GET['name']),2);
if ($pieces[0]=='obs') {
  $sql = "SELECT specimen_ID, img_obs_directory, source_name
          FROM tbl_specimens, tbl_management_collections, tbl_img_definition, herbarinput.meta
          WHERE tbl_specimens.collectionID=tbl_management_collections.collectionID
           AND tbl_management_collections.source_id=tbl_img_definition.source_id_fk
           AND tbl_img_definition.source_id_fk=herbarinput.meta.source_id
           AND specimen_ID='".intval($pieces[1])."'";
  $result = mysql_query($sql);
  $row = mysql_fetch_array($result);
  $img = allocatePicture($row['img_obs_directory'], "obs_".$pieces[1]);
}
elseif ($pieces[0]=='tab') {
  $sql = "SELECT specimen_ID, img_tab_directory, source_name
          FROM tbl_specimens, tbl_management_collections, tbl_img_definition, herbarinput.meta
          WHERE tbl_specimens.collectionID=tbl_management_collections.collectionID
           AND tbl_management_collections.source_id=tbl_img_definition.source_id_fk
           AND tbl_img_definition.source_id_fk=herbarinput.meta.source_id
           AND specimen_ID='".intval($pieces[1])."'";
  $result = mysql_query($sql);
  $row = mysql_fetch_array($result);
  $img = allocatePicture($row['img_tab_directory'], "tab_".$pieces[1]);
}
else {
  $sql = "SELECT img_directory, coll_short_prj, source_name
          FROM tbl_img_definition, tbl_management_collections, herbarinput.meta
          WHERE tbl_img_definition.source_id_fk=herbarinput.meta.source_id
           AND tbl_management_collections.source_id=tbl_img_definition.source_id_fk
           AND coll_short_prj='".$pieces[0]."'";
  $result = mysql_query($sql);
  $row = mysql_fetch_array($result);
  $img = allocatePicture($row['img_directory'], "".$row['coll_short_prj']."_".$pieces[1]);
}

if (!file_exists($img))
  @readfile("pics/blind.png");
else {
  $ip = sprintf("%8X",ip2long($_SERVER['REMOTE_ADDR']));

  $param = $_GET['p'];

  switch (intval(substr($param,8,1))) {
    case 2: $width = 800;
            $height = 600;
            break;
    case 3: $width = 1024;
            $height = 768;
            break;
    case 4: $width = 1200;
            $height = 960;
            break;
    case 5: $width = 1600;
            $height = 1200;
            break;
    default: $width = 640;
             $height = 480;
  }

  $cw = intval(substr($param,9,5)); // crop width
  $ch = intval(substr($param,14,5)); // crop height
  $cx = intval(substr($param,19,5)); // crop x
  $cy = intval(substr($param,24,5)); // crop y
  //$text = " ".chr(169)." John Doe ";
  $text = " ".chr(169)." ".utf8_decode($row['source_name']);
  $copyright = "-rotate 90 -gravity southwest pics/cc-by-sa.png -composite -rotate -90";
//  $copyright = "-font Helvetica -pointsize 16 -fill white -box '#000000A0' -rotate 90 -gravity southwest -annotate +5+5 '$text' -rotate -90";
//  $copyright = "-font Arial -pointsize 16 -fill white -box '#000000A0' -rotate 90 -draw 'gravity southwest text 5 25  \"$text\"' -rotate -90";
//  $copyright = "-fill white -font Arial -pointsize 16 -gravity south -undercolor dodgerblue -draw 'text 5 5 \"$text\"'";
//  $copyright = "-rotate 90 -font Helvetica -pointsize 16 -fill white -undercolor '#000000A0' -draw 'gravity southwest text 5 5  \"$text\"' -rotate -90";

  if ($ip==substr($param,0,8)) {
    if ($cw && $ch)
      passthru("convert $img -crop ".$cw."x".$ch."+".$cx."+".$cy." +repage -resize ".$width."x".$height." $copyright JPG:-");
    else {
      // Check if we have a default image with height 480
      if( $height == 480 ) {
        // Get info about image
        $imginfo = pathinfo( $img );
        $imgname = basename( $img, '.' . $imginfo['extension'] );

        // Try to find the image in the cache first
        $cache_path = '/data/images/specimens/msa20/web/cache/640x480/';
        $cache_name = $cache_path . $imgname . '.jpg';
        
        // Check if the thumbnail is outdated
        $fmtime = filemtime( $cache_name );
        if( $fmtime ) {
          // Connect to the DB
          db_connect($_CONFIG['DATABASES']['PICTURES']);
            
          // Check if the picture was updated
          $result = mysql_query( "SELECT ID FROM files WHERE file LIKE '$imgname' AND mtime > FROM_UNIXTIME( '$fmtime' )" );
          // Delete the cache file to force re-creation
          if( $result && mysql_num_rows($result) > 0 ) {
            unlink( $cache_name );
          }
        }
        
        if( file_exists( $cache_name ) ) {
          @readfile( $cache_name );
        }
        else {
          exec( "convert $img -resize " . $width . "x" . $height . " $copyright JPG:- > $cache_name" );
          // Check if caching was successfull
          if( file_exists($cache_name) ) {
            @readfile( $cache_name );
          }
          // Something went wrong, just output image
          else {
            passthru("convert $img -resize ".$width."x".$height." $copyright JPG:-");
          }
        }
      }
      else {
        passthru("convert $img -resize ".$width."x".$height." $copyright JPG:-");
      }    
    }
  }
  else
     @readfile("pics/blind.png");

  //passthru("/opt/x11/bin/convert -pen 0 -geometry $heightx$width -bordercolor white -border 0x0 $img JPG:-");
  //passthru("convert -geometry ".$height."x".$width." $img JPG:-");
}