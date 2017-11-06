<?php
/**
 * Return formatted string for sending to a spreadsheet program
 * @param array $IDlist list of specimen-IDs
 * @param string[optional] $type type of export, allowed are 'csv' (default) or 'xls'
 * @param array[optional] $headerList list of DB-columns to include, default is all
 * @return string formatted string for sending
 */
function exportSpecimens($IDlist, $type = 'csv', $headerList = array())
{
    // extend memory and timeout settings
    ini_set("memory_limit", "512M");
    set_time_limit(0);

    if ($type == 'xls') {
        
    }
}