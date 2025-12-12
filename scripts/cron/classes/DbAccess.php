<?php

namespace Jacq;

use Exception;
use mysqli;
use mysqli_result;
use mysqli_sql_exception;

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
 * instantiates the class DbAccess for a given database
 *
 * @param string $db connect to that DB
 * @param int $id [optional] return connection with this ID (default = 1)
 * @return DbAccess new instance of that class
 * @throws Exception
 */
public static function ConnectTo(string $db, int $id = 1): DbAccess
{
    $config = Settings::Load();

    if ($config->getDbAccess($db)) {
        if (!isset(self::$instances[$db][$id])) {
            self::$instances[$db][$id] = new DbAccess($db);
        }
        return self::$instances[$db][$id];
    } else {
        throw new Exception("No Connection to Database $db");
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

/**
 * executes a query and catches exceptions
 *
 * @param string $query The query string. For further explanation, see mysqli::query()
 * @param int $result_mode The result mode. For further explanation, see mysqli::query()
 * @return bool|mysqli_result The result of the query. For further explanation, see mysqli::query()
 */
public function queryCatch(string $query, int $result_mode = MYSQLI_STORE_RESULT): bool|mysqli_result
{
    try {
        $result = parent::query($query, $result_mode);
    } catch (mysqli_sql_exception $e) {
        echo $query . "\n";
        echo $e->__toString() . "\n";
        die();
    }

    return $result;
}

/**
 * Prepares a string for use in a SQL query. If the string is empty, NULL is returned, else the escaped string with single quotes
 *
 * @param string|null $text the text to quote
 * @return string the quoted text
 */
public function quoteString(?string $text): string
{
    if (mb_strlen($text) > 0) {
        return "'" . $this->real_escape_string($text) . "'";
    }
    else {
        return "NULL";
    }
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
 * to prevent cloning of this singleton
 *
 */
private function __clone() {}

}
