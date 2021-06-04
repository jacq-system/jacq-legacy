<?php
/**
 * Settings singleton - Management of all settings
 *
 * A singleton to handle all kinds of settings
 *
 * @author Johannes Schachner
 * @version 1.0
 * @package clsSettings
 */


/**
 * Settings singleton - Management of all settings
 * @package clsSettings
 * @subpackage classes
 */
class clsSettings
{
/********************\
|                    |
|  static variables  |
|                    |
\********************/

private static $instance = null;

/********************\
|                    |
|  static functions  |
|                    |
\********************/

/**
 * instances the class clsSettings
 *
 * @return clsSettings new instance of that class
 */
public static function Load()
{
    if (self::$instance == null) {
        self::$instance = new clsSettings();
    }
    return self::$instance;
}

/*************\
|             |
|  variables  |
|             |
\*************/

protected $options = array();

/***************\
|               |
|  constructor  |
|               |
\***************/

protected function __construct()
{
    global $_OPTIONS;

    $this->options = $_OPTIONS;
}

/********************\
|                    |
|  public functions  |
|                    |
\********************/

/**
 * get a single or a group of settings
 *
 * @param string $key1 first level key
 * @param string[optional] $key2 second level key
 * @param string[optional] $key3 third level key
 * @return mixed single or multi settings
 */
public function getSettings($key1, $key2 = NULL, $key3 = NULL)
{
    if ($key3) {
        if (isset($this->options[$key1][$key2][$key3])) {
            return $this->options[$key1][$key2][$key3];
        } else {
            return '';
        }
    } elseif ($key2) {
        if (isset($this->options[$key1][$key2])) {
            return $this->options[$key1][$key2];
        } else {
            return '';
        }
    } else {
        if (isset($this->options[$key1])) {
            return $this->options[$key1];
        } else {
            return '';
        }
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

private function __clone() {}


}