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

/**
 * executes a query, catches exceptions and logs errors, if any occurs
 *
 * @param string $query The query string. For further explanation, see mysqli::query()
 * @param int $result_mode The result mode. For further explanation, see mysqli::query()
 * @return bool|mysqli_result The result of the query. For further explanation, see mysqli::query()
 */
public function queryCatch(string $query, int $result_mode = MYSQLI_STORE_RESULT): bool|mysqli_result
{
    if (empty($query)) {
        error_log("EMPTY SQL QUERY FROM USER-ID {$_SESSION['uid']}.");
        $result = false;
    } else {
        try {
            $result = parent::query($query, $result_mode);
        } catch (mysqli_sql_exception $e) {
            $result = false;
            error_log("SEVERE SQL-ERROR IN SCRIPT. USER-ID = {$_SESSION['uid']}\n"
                . "$query\n"
                . "--- Error: " . $e->__toString() . "\n"
                . "In script {$_SERVER['PHP_SELF']}");
        }
    }

    return $result;
}

/**
 * quotes a string or returns NULL if string is empty or is NULL
 *
 * @param string $text what to quote
 * @return string quoted string or "NULL"
 */
public function quoteString($text): string
{
    if (strlen($text) > 0) {
        if (trim($text) == "0000-00-00 00:00:00") {
            return "NULL";
        } else {
            return "'" . $this->real_escape_string($text) . "'";
        }
    } else {
        return "NULL";
    }
}

/**
 * checks an INT-value and returns NULL if zero
 *
 * @param integer $value
 * @return string quoted string or "NULL"
 */
public function makeInt($value): string
{
    if (intval($value)) {
        return "'" . intval($value) . "'";
    } else {
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
