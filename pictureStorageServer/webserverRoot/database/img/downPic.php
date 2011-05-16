<?php

if (isset($_GET['name']))
  $usedName = $_GET['name'];
else
  $usedName = $_GET['ID'];

// Remove the extension from the name
$info = pathinfo( $usedName );
$usedName = basename( $usedName, '.' . $info['extension'] );

// Required to prevent _a _b files, etc.
$usedName = $usedName . '.';

require_once("../../inc/connect.php");
require_once("inc/getPathPic.php");
  
if ($pic != 'blind.png' && file_exists($path.$pic)) {
  $imginfo = getimagesize($path.$pic);
  
  $type = 0;
  if( isset($_GET['type'] ) ) $type = $_GET['type'];
  
  $transform = false;
  $targetFormat = '';
  $targetFile = $pic;
  
  switch( $type ) {
    // TIFF
    case 1:
      if( $imginfo['mime'] != 'image/tiff' ) {
        $transform = true;
        $targetFormat = 'tiff';
        $imginfo['mime'] = 'image/tiff';
        $targetFile = $usedName . 'tif';
      }
      break;
    // JPEG2000
    default:
      if( $imginfo['mime'] != 'application/octet-stream' ) {
        $transform = true;
        $targetFormat = 'jpc';
        $imginfo['mime'] = 'application/octet-stream';
        $targetFile = $usedName . 'jpc';
      }
      break;
  }
  
  Header( 'Content-Type: ' . $imginfo['mime'] );
  Header( 'Content-Disposition: attachment; filename="' . $targetFile . '"' );

  if( $transform ) {
    passthru( "convert $path$pic $targetFormat:-" );
  }
  else {
    $filesize = filesize($path.$pic);
    Header( 'Content-Length: ' . $filesize );
  
    @readfile( $path.$pic );
  }
}
else {
  // Actually we should never reach here
  @readfile("pics/blind.png");
}
