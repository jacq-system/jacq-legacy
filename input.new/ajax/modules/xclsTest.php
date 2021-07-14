<?php
/**
 * xajax-class to be called by the dispatcher - tests
 *
 * An example for a dispatcher-class
 *
 * @author Johannes Schachner
 * @version 1.0
 * @package xclsTest
 */


/**
 * xajax-class to be called by the dispatcher - tests
 * @package xclsTest
 * @subpackage classes
 */
class xclsTest extends xclsBase
{
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


/*******************\
|                   |
|  public functions |
|                   |
\*******************/

/**
 * displays a form
 *
 */
public function x_showForm()
{
    global $tplObject;

    $tplObject->setFileName('test/form');
    $tplObject->setVariable('back', (!empty($_SESSION['back'])) ? 'back' : 'dummy');  // switch on the back-button if a target exists
    $tplObject->parse();
    $this->objResponse->assign('xajax_content', 'innerHTML', $tplObject->getFileContent());
}

/**
 * send the form values to a different target
 *
 * @param array $formValues
 */
public function x_showFormValues($formValues)
{
    $this->objResponse->assign('xajaxTarget', 'innerHTML', "<pre>\n" . var_export($formValues, true) . "</pre>\n");
}

/**
 * displays a text
 *
 * @param string[optional] $text text to display, defaults to 'init'
 */
public function x_showText($text = 'init')
{
    $this->objResponse->assign('xajax_content', 'innerHTML', $text);
}

/**
 * displays a jump-button
 *
 */
public function x_showJump()
{
    global $tplObject;

    $tplObject->setFileName('test/jump');
    $tplObject->parse();
    $this->objResponse->assign('xajax_content', 'innerHTML', $tplObject->getFileContent());
}


/********************\
|                    |
|  private functions |
|                    |
\********************/

}