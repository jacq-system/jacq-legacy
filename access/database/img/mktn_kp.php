<?php
header ("Content-type: image/jpeg");

if (isset($_GET['name']))
  $usedName = $_GET['name'];
else
  $usedName = $_GET['ID'];
  
// Remove the extension from the name
$info = pathinfo( $usedName );
$usedName = basename( $usedName, '.' . $info['extension'] );

// Try to find the image in the cache first
$cache_path = '/data/images/specimens/msa20/web/cache/x1300/';
$cache_name = $cache_path . $usedName . '.jpg';
if( file_exists( $cache_name ) ) {
  @readfile( $cache_name );
  exit(0);
}
else {
  // Required to prevent _a _b files, etc.
  $usedName = $usedName . '.';

  require_once("../../inc/connect.php");
  require_once("inc/getPathPic.php");
  
  if ($pic != 'blind.png' && file_exists($path.$pic)) {
    //passthru("convert -geometry x1300 $path$pic JPG:- > $cache_name"); 
    exec( "convert -geometry x1300 $path$pic JPG:- > $cache_name" );

    // Check if the cache image was successfullly created
    if( file_exists($cache_name) ) {
      @readfile( $cache_name );
    }
    // Something went wrong during caching
    else {
      // Just output the image
      passthru( "convert -geometry x1300 $path$pic JPG:-" );
    }

    exit(0);
  }
}

// Actually we should never reach here
@readfile("pics/blind.png");
