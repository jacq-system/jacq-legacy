<?php
/**
 * ajax module dispatch
 *
 * central callback functions dispatch and back
 * @author Johannes Schachner <joschach@eunet.at>
 * @version 19.05.2009
 */

/**
 * Include necessary files
 */
require("inc/init.php");

/**
 * Require all necessary definitions
 */
require_once("inc/xajax/xajax_core/xajax.inc.php");

/**
 * create the xajax object
 * @global xajax $xajaxObject
 */
$xajaxObject = new xajax();

/**
 * xajax dispatcher function, calls everything else
 *
 * calls a method within a class, the method-name within the class must start with a 'x_',
 * the call must not contain that header!
 * possible parameters are:
 * module name (without the 'xcls', first letter may be lowercase)
 * method name (without the 'x_', just these are callable)
 * parameters of the call (if any)
 * the rest is optional (just needed for a backjump)
 * 'back' (codeword, from there on everything else relates to the backjump)
 * name of the menu-item to activate (may contain the trailing 'mnu_', if empty the menu remains unchanged)
 * module name (without the 'xcls', first letter may be lowercase)
 * method name (without the 'x_', just these are callable)
 * parameters of the call (if any)
 *
 * @return xajaxResponse
 */
function dispatch()
{
    $objResponse = clsFactory::Create('xajaxResponse');

    if (func_num_args() > 1) {
        $moduleName = 'xcls' . ucfirst(basename(func_get_arg(0), '.php'));
        $methodName = 'x_' . func_get_arg(1);

        $obj = new $moduleName();

        if (method_exists($obj, $methodName)) {
            $params = array();
            $backIncluded = false;
            for ($pctr = 2; $pctr < func_num_args(); $pctr++) {
                if (func_get_arg($pctr) != 'back') {
                    $params[] = func_get_arg($pctr);
                } else {
                    $backIncluded = true;
                    break;
                }
            }
            if ($backIncluded) {
                $back['menuID'] = func_get_arg($pctr + 1);
                $back['moduleName'] = basename(func_get_arg($pctr + 2), '.php');
                $back['methodName'] = func_get_arg($pctr + 3);
                $back['params'] = array();
                for ($i = $pctr + 4; $i < func_num_args(); $i++) {
                    $back['params'][] = func_get_arg($i);
                }
                $_SESSION['back'][] = serialize($back);
            }

            if (count($params) == 0) {
                $obj->$methodName();
            } else {
                call_user_func_array(array($obj, $methodName), $params);
            }
        } else {
            $objResponse->alert("The requested method " . func_get_arg(1) . " could not be found.");
        }
    }

    return $objResponse;
}

/**
 * xajax backjump function, jumps back to origin (if it exists), no parameters whatsoever
 *
 * @return xajaxResponse
 */
function back()
{
    $objResponse = clsFactory::Create('xajaxResponse');

    if (!empty($_SESSION['back'])) {
        $back = unserialize(array_pop($_SESSION['back']));
        if ($back['menuID']) {
            $menu = new xclsMenu();
            $menu->x_showMenu($back['menuID'], true, true, false);
        }

        $moduleName = 'xcls' . ucfirst(basename($back['moduleName'], '.php'));
        $methodName = 'x_' . $back['methodName'];

        $obj = new $moduleName();

        if (method_exists($obj, $methodName)) {
            if (count($back['params']) == 0) {
                $obj->$methodName();
            } else {
                call_user_func_array(array($obj, $methodName), $back['params']);
            }
        } else {
            $objResponse->alert("The requested method " . $back['methodName'] . " could not be found.");
        }
    } else {
        $objResponse->alert("No Backjump defined.");
    }

    return $objResponse;
}


/**
 * register all xajax-functions in this file
 */
$xajaxObject->registerFunction("dispatch");
$xajaxObject->registerFunction("back");
$xajaxObject->processRequest();