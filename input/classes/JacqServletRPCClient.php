<?php

namespace Jacq;

use org\jsonrpcphp\JsonRPCClient;
use Exception;

class JacqServletRPCClient extends JsonRPCClient
{
    /********************\
    |                    |
    |  static variables  |
    |                    |
    \********************/

    /********************\
    |                    |
    |  static functions  |
    |                    |
    \********************/

    /*************\
    |             |
    |  variables  |
    |             |
    \*************/

    private PdoAccess $db_input;

    private string $key;

    private string $url;

    /***************\
    |               |
    |  constructor  |
    |               |
    \***************/

    /**
     * Construct new JSON-RPC client by fetching the required properties
     * @param string $imgserver_IP Address of image server
     * @throws Exception
     */
    public function __construct($imgserver_IP)
    {
        $this->db_input = PdoAccess::ConnectTo('INPUT');

        $dbst = $this->db_input->query('SELECT source_id_fk, imgserver_type, iiif_capable, imgserver_url, key 
                                        FROM `tbl_img_definition` 
                                        WHERE `imgserver_IP` = ' . $this->db_input->quote($imgserver_IP));
        $row = $dbst->fetch();

        if (!$row) {
            throw new Exception('No valid IP');
        } else if ($row['imgserver_type'] != 'djatoka') {
            throw new Exception('Not a djatoka server');
        }

        // Fetch required properties
        $this->key = $row['key'];
        if ($row['iiif_capable']) {
            // get url from herbar_pictures.iiif_definition instead of tbl_img_definition
            $this->url = substr($this->db_input->query("SELECT manifest_backend FROM herbar_pictures.iiif_definition WHERE source_id_fk = {$row['source_id_fk']}")
                                               ->fetch()['manifest_backend'], 5);
        } else {
            $this->url = $row['imgserver_url'] . 'jacq-servlet/ImageServer';
        }

        // Finally call parent constructor
        parent::__construct($this->url);
    }

    /********************\
    |                    |
    |  public functions  |
    |                    |
    \********************/

    /**
     * Call a JSON-RPC function, but add key as first parameter
     * @param string $method
     * @param array $params
     * @throws Exception
     */
    public function __call($method, $params)
    {
        // Always add key as first parameter
        array_unshift($params, $this->key);

        // Finally call the method
        return parent::__call($method, $params);
    }

    /***********************\
    |                       |
    |  protected functions  |
    |                       |
    \***********************/

    /*********************\
    |                     |
    |  private functions  |
    |                     |
    \*********************/

}
