<?php

namespace Jacq;

use Exception;
use PDO;

class PdoAccess extends PDO
{
/********************\
|                    |
|  static variables  |
|                    |
\********************/

/**
 * holds a list of instances
 *
 * @var array
 */
private static array $instances = array();

/********************\
|                    |
|  static functions  |
|                    |
\********************/

/**
 * instantiates the class PdoAccess for a given database
 *
 * @param string $db connect to that DB
 * @return PdoAccess new instance of that class
 * @throws Exception
 */
public static function ConnectTo(string $db): PdoAccess
{
    $config = Settings::Load();

    if ($config->getDbAccess($db)) {
        if (!isset(self::$instances[$db])) {
            self::$instances[$db] = new PdoAccess($db);
        }
        return self::$instances[$db];
    } else {
        throw new Exception("Database $db doesn't exist");
    }
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

/**
 * constructor of the class
 */
protected function __construct($db)
{
    $config = Settings::Load();
    $dbAccess = $config->getDbAccess($db);

    parent::__construct("mysql:host={$dbAccess['host']};dbname={$dbAccess['db']};charset=utf8", $dbAccess['user'], $dbAccess['pass']);
    $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

/********************\
|                    |
|  public functions  |
|                    |
\********************/

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
 * to prevent cloning of this singleton
 *
 */
private function __clone() {}

}
