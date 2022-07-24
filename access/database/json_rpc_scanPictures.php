<?php
// uses PDO

require_once('inc/jsonRPCServer.php');
require_once( '../inc/variables.php' );

$options['extensions']  = array('.tif', '.jpc', '.jpg');  // which extensions are allowed
$options['key'] = 'DKsuuewwqsa32czucuwqdb576i12';         // key to use this service

/**
 * scanPictures service class
 *
 * @package scanPicturesService
 * @subpackage classes
 */
class scanPicturesService
{


/*******************\
|                   |
|  public functions |
|                   |
\*******************/

public function getPictures ($ip, $secret)
{
    global $options, $_CONFIG;

    // wrong key => just abort
    if ($secret != $options['key']) return array();

    // catch all output to the console
    ob_start();

    // initialise return data structure
    $ret = array('errors' => array());

    // connect to the database or stop on any connect error
    try {
        $db = new PDO('mysql:host=' . $_CONFIG['DATABASE']['INPUT']['host'] . ';dbname=' . $_CONFIG['DATABASE']['INPUT']['db'],
                      $_CONFIG['DATABASE']['INPUT']['readonly']['user'],
                      $_CONFIG['DATABASE']['INPUT']['readonly']['pass'],
                      array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET character set utf8"));
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch (PDOException $e) {
        return array('errors' => array('Connection failed: ' . $e->getMessage()));
    }

    $start = microtime(true);
    try {
        $dbst = $db->prepare("SELECT source_id_fk
                              FROM tbl_img_definition
                              WHERE imgserver_IP = :ip");
        $dbst->execute(array(':ip' => $ip));
        foreach ($dbst as $row) {
            try {
                if (file_exists($row['img_directory'])) {
                    $rits = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($row['img_directory']), RecursiveIteratorIterator::SELF_FIRST);
                    foreach($rits as $filename => $fileobj){
                        $pos = strrpos($filename, '.');
                        if ($pos && in_array(strtolower(substr($filename, $pos)), $options['extensions'])) {
                            $ret['results'][$row['source_id_fk']][] = array('filename' => $filename, 'mtime' => filemtime($filename));
                        }
                    }
                } else {
                    $ret['errors'][] = "directory " . $row['img_directory'] . " doesn't exist";
                }
            }
            catch (Exception $e) {
                $ret['errors'][] = $e->getMessage();
            }
        }
    }
    catch (Exception $e) {
        $ret['errors'][] = $e->getMessage();
    }
    $end = microtime(true);
    $ret['duration'] = $end - $start;

    $errors = ob_get_clean();
    if ($errors) $ret['errors'][] = $errors;

    return $ret;
}


/********************\
|                    |
|  private functions |
|                    |
\********************/

} // class scanPicturesService


/**
 * implementation of the json rpc functionality
 */
$service = new scanPicturesService();
jsonRPCServer::handle($service)
    or print 'no request';