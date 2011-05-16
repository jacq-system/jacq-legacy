<?php
/**
 * database-access singleton - access to a database
 *
 * A singleton to connect to various databases, extends PDO
 *
 * @author Johannes Schachner
 * @version 1.0
 * @package clsDbAccess
 */


/**
 * database-access singleton - access to a database
 * @package clsDbAccess
 * @subpackage classes
 */
class clsDbAccess extends PDO
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
private static $instances = array();

/********************\
|                    |
|  static functions  |
|                    |
\********************/

/**
 * instances the class clsDbAccess with a given database keyword
 *
 * @param string $db connect to that DB
 * @return intDbAccess new instance of that class
 */
public static function Connect($db)
{
    if (!isset(self::$instances[$db])) {
        self::$instances[$db] = new clsDbAccess($db);
    }
    return self::$instances[$db];
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
public function __construct($db) {
    $settings = clsSettings::Load();
    parent::__construct('mysql:host=' . $settings->getSettings('DB', $db, 'HOST') . ';dbname=' . $settings->getSettings('DB', $db, 'NAME'),
                        $_SESSION['username'],
                        $_SESSION['password'],
                        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET character set utf8"));
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