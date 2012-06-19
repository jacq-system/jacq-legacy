<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require("jsonRPCClient.php");

/**
 * Description of jacqServletJsonRPCClient
 *
 * @author wkoller
 */
class jacqServletJsonRPCClient extends jsonRPCClient {
    /**
     *
     * @var PDO
     */
    private $db_input = false;
    
    /**
     *
     * @var string
     */
    private $key = '';
    
    /**
     *
     * @var string 
     */
    private $url = '';
    
    /**
     * Construct new JSON-RPC client by fetching the required properties
     * @param string $imgserver_IP Address of image server
     * @throws Exception 
     */
    public function __construct($imgserver_IP) {
        $this->db_input = clsDbAccess::Connect('INPUT');
        
        $dbst = $this->db_input->query('SELECT * FROM `tbl_img_definition` WHERE `imgserver_IP` = ' . $this->db_input->quote($imgserver_IP));
        $row = $dbst->fetch();
        
        if( !$row ) {
            throw new Exception( 'No valid IP' );
        }
        else if( $row['is_djatoka'] != 1 ) {
            throw new Exception( 'Not a djatoka server' );
        }
        
        // Fetch required properties
        $this->key = $row['key'];
        $this->url = 'http://' . $row['imgserver_IP'] . $row['img_service_directory'] . '/jacq-servlet/ImageServer';
        
        // Finally call parent constructor
        parent::__construct($this->url, false);
    }
    
    /**
     * Call a JSON-RPC function, but add key as first parameter
     * @param string $method
     * @param array $params 
     */
    public function __call($method, $params) {
        // Always add key as first parameter
        array_unshift($params, $this->key);
        
        // Finally call the method
        return parent::__call($method, $params);
    }
}
