<?php
/**
 * CONFIG STUFF
 * */
$source_format = "tif";                   // Input Format (required to filter the files)
$format = "jpc";                          // Output Format (used as filter for ImageMagick and file-extension)

/**
 * FUNCTIONS
 * */
function convertDir( $directory ) {
  global $source_format, $format, $currIdent;
  
  echo $currIdent . "-> Entering " . $directory . "\n";

  // Get list of files already on the drive
  $scanList = scandir( $directory );
  
  foreach( $scanList as $entry ) {
    //echo "Checking: " . $entry . "\n";
  
    // Don't handle '.' and '..' entries
    if( $entry == '.' || $entry == '..' ) continue;
    
    // If we have a directory, jump into it
    if( is_dir( $directory . $entry ) ) {
      $currIdent .= "\t";
      convertDir( $directory . $entry . '/' );
      continue;
    }
    
    // Now that we got a file, check if it has the correct format
    $fileInfo = pathinfo( $directory . $entry );
    if( $fileInfo['extension'] == $source_format ) {
      $entry_new = $fileInfo['filename'] . '.' . $format;
    
      echo $currIdent . "Converting & Removing Image '" . $directory . $entry . "' to '" . $directory . $entry_new . "'!\n";
      passthru("convert -limit memory 128 -limit map 256 " . $directory . $entry . " " . $format . ":" . $directory . $entry_new );
      passthru("rm " . $directory . $entry );
    }
  }
  
  echo $currIdent . "<- Leaving " . $directory . "\n";
  $currIdent = substr( $currIdent, 0, -1 );
}

/**
 * MAIN-PART
 * */
if( $argc <= 1 ) {
  echo "Usage: tools_convertImageFormat.php <directory>\n" .
        "\t<directory>: Path to start from! WARNING: The script works recursive!\n";
  exit( 0 );
}

$currIdent = "";

convertDir( $argv[1] );
