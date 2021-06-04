<?php
/**
 * Base class for all xajax-classes to be called by the dispatcher - general methods and variables
 *
 * @author Johannes Schachner
 * @version 1.0
 * @package xclsBase
 */


/**
 * Base class for all xajax-classes to be called by the dispatcher - general methods and variables
 * @package xclsBase
 * @subpackage classes
 */
abstract class xclsBase
{
/*************\
|             |
|  variables  |
|             |
\*************/

/**
 * xajax-object for the responses
 *
 * @var xajaxResponse
 */
protected $objResponse;


/***************\
|               |
|  constructor  |
|               |
\***************/

/**
 * constructor
 */
public function __construct ()
{
    $this->objResponse = clsFactory::Create('xajaxResponse');
}


/*******************\
|                   |
|  public functions |
|                   |
\*******************/


/**********************\
|                      |
|  protected functions |
|                      |
\**********************/

/**
 * makes an array for a dropdown list
 *
 * @param array $list items to display (result of a db query)
 * @param string $nameValue name of column which holds the values
 * @param string $nameText name of column which holds the textes
 * @param string $selectedItem currently selected item (should be a value)
 * @param bool $withEmptyItem set to true if an empty item is to be inserted at the beginning
 * @return array produced list, ready to send to the template system
 */
protected function _makeDropdown($list, $nameValue, $nameText, $selectedItem, $withEmptyItem = false)
{
    $display_list = array();
    if ($withEmptyItem) {
        $display_list[] = array('optValue'  => '',
                                'optText'   => '',
                                'currValue' => cleanData($selectedItem));
    }
    if (!empty($list)) {
        foreach ($list as $row) {
            $display_list[] = array('optValue'  => cleanData($row[$nameValue]),
                                    'optText'   => cleanData($row[$nameText]),
                                    'currValue' => cleanData($selectedItem));
        }
    }

    return $display_list;
}

protected function _makeMultiDropdown($list, $nameValue, $nameText, $selectedItems)
{
    $display_list = array();
    if (!empty($list)) {
        foreach ($list as $row) {
            $display_list[] = array('optValue'  => cleanData($row[$nameValue]),
                                    'optText'   => cleanData($row[$nameText]),
                                    'currValue' => (in_array($row[$nameValue], $selectedItems)) ? cleanData($row[$nameValue]) : '');
        }
    }

    return $display_list;
}

}