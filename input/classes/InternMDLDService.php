<?php

namespace Jacq;

use org\jsonrpcphp\JsonRPCClient;

class InternMDLDService extends JsonRPCClient
{
    /********************\
    |                    |
    |  static variables  |
    |                    |
    \********************/

    private static ?InternMDLDService $instance = null;
    private static string $password;

    /********************\
    |                    |
    |  static functions  |
    |                    |
    \********************/

    public static function Load(string $url, string $password = '', bool $debug = false): InternMDLDService
    {
        if (self::$instance == null) {
            self::$password = $password;
            self::$instance = new InternMDLDService($url, $debug);
        }
        return self::$instance;
    }

    /*************\
    |             |
    |  variables  |
    |             |
    \*************/

    /***************\
    |               |
    |  constructor  |
    |               |
    \***************/

    /********************\
    |                    |
    |  public functions  |
    |                    |
    \********************/

    /**
     * Performs a jsonRCP request and gets the results as an array
     *
     * @param string $method
     * @param array $params
     * @return array
     */
    public function __call($method, $params)
    {
        if (self::$password != '') {
            $params[] = $this->makeKey(self::$password);
        }

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

    /**
     * generates the salted hash key
     *
     * @return string hashed key
     */
    private function makeKey(string $password): string
    {
        $salt = substr(uniqid(mt_rand(), true), 0, 5);
        $key = $salt . md5($salt . md5($password) . date('d.m.Y H:i'));
        return $key;
    }

}
