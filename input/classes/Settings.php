<?php

namespace Jacq;

class Settings
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
 * @return Settings new instance of that class
 */
public static function Load()
{
    if (self::$instance == null) {
        self::$instance = new Settings();
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

/**
 * constructor of the class
 */
protected function __construct()
{
    include __DIR__ . "/../inc/variables.php";
    /** @var array $_CONFIG */
    $this->options = $_CONFIG;
}

/********************\
|                    |
|  public functions  |
|                    |
\********************/

/**
 * get a single or a group of settings
 *
 * @param string group options group
 * @param string[optional] $key key within that group (if any)
 * @return mixed single or multi settings
 */
public function get($group, $key = null)
{
    if ($key) {
        return $this->options[$group][$key] ?? null;
    } else {
        return $this->options[$group] ?? null;
    }
}

/**
 * get all necessary data for db access
 *
 * @param string $db which database
 * @return array necessary data for db access
 */
public function getDbAccess($db)
{
    $db = strtoupper($db);
    if (isset($this->options['DATABASE'][$db])) {
        return array('host' => $this->options['DATABASE'][$db]['host'],
                     'user' => $this->options['DATABASE'][$db]['readonly']['user'],
                     'pass' => $this->options['DATABASE'][$db]['readonly']['pass'],
                     'db'   => $this->options['DATABASE'][$db]['name']);
    } else {
        return array('host' => '',
                     'user' => '',
                     'pass' => '',
                     'db'   => '');
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
private function __clone()
{
}

}
