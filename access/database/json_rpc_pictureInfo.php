<?php
require_once('inc/jsonRPCServer.php');
require_once( '../inc/variables.php' );

$options['key'] = 'DKsuuewwqsa32czucuwqdb576i12';         // key to use this service

class PictureServer {
    public function getImageInfo( $basename, $secret ) {
        global $options, $_CONFIG;

        // wrong key => just abort
        if ($secret != $options['key']) return array();

        // catch all output to the console
        ob_start();

        // initialise return data structure
        $ret = array( 'errors' => array(), 'images' => array() );

        // connect to the database or stop on any connect error
        try {
            $db = new PDO('mysql:host=' . $_CONFIG['DATABASE']['PICTURES']['host'] . ';dbname=' . $_CONFIG['DATABASE']['PICTURES']['db'],
                          $_CONFIG['DATABASE']['PICTURES']['readonly']['user'],
                          $_CONFIG['DATABASE']['PICTURES']['readonly']['pass'],
                          array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET character set utf8"));
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch (PDOException $e) {
            return array('errors' => array('Connection failed: ' . $e->getMessage()));
        }

        $start = microtime(true);
        try {
            $dbst = $db->prepare("SELECT `file`
                                  FROM `files`
                                  WHERE `basefile` = :basename ORDER BY `file`");
            $dbst->execute(array(':basename' => $basename));
            foreach ($dbst as $row) {
                $ret['images'][] = $row['file'];
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
};

//var_export( $_REQUEST );
//var_export( $_SERVER );

/**
 * implementation of the json rpc functionality
 */
$service = new PictureServer();
jsonRPCServer::handle($service)
    or print 'no request';
