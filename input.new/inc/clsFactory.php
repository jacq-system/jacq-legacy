<?php
/**
 * class factory - instances a given class
 *
 * A singleton to generate a given class
 *
 * @author Johannes Schachner
 * @version 1.0
 * @package clsFactory
 */


/**
 * class factory - instances a given class
 * @package clsFactory
 * @subpackage classes
 */
class clsFactory
{
/********************\
|                    |
|  static variables  |
|                    |
\********************/

private static $instances = array();

/********************\
|                    |
|  static functions  |
|                    |
\********************/

/**
 * instances a class
 * only one instance of a given classname is possible
 *
 * @param string $class which class should be created
 * @return mixed new instance of that class
 */
public static function Create($class)
{
    if (empty(self::$instances[$class])) {
        $settings = clsSettings::Load();
        $type = $settings->getSettings(strtoupper($class));
        if ($type) {
            $newclass = $class . '_' . $type;
            self::$instances[$class] = new $newclass;
        } else {
            self::$instances[$class] = new $class;
        }
    }
    return self::$instances[$class];
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

protected function __construct() {}

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