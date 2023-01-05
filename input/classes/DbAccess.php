<?php

namespace Jacq;

use Exception;
use mysqli;

class DbAccess extends mysqli
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
 * instances the class DbAccess for a given database
 *
 * @param string $db connect to that DB
 * @return DbAccess new instance of that class
 * @throws Exception
 */
public static function ConnectTo(string $db): DbAccess
{
    $config = Settings::Load();

    if ($config->getDbAccess($db)) {
        if (!isset(self::$instances[$db])) {
            self::$instances[$db] = new DbAccess($db);
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

    parent::__construct($dbAccess['host'],
        $dbAccess['user'],
        $dbAccess['pass'],
        $dbAccess['db']);
    $this->set_charset('utf8');
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
