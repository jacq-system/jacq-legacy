<?php
/**
 * database access
 */
// JACQ database
$options['hrdb']['dbhost']            = 'localhost';
$options['hrdb']['dbname']            = 'input';
$options['hrdb']['dbnameCommonNames'] = 'names';
$options['hrdb']['dbuser']            = '';
$options['hrdb']['dbpass']            = '';
// Catalogue of Life 2010
$options['col']['dbhost'] = 'localhost';
$options['col']['dbname'] = 'ref_col2010ac';
$options['col']['dbuser'] = '';
$options['col']['dbpass'] = '';
// Catalogue of Life 2011
$options['col2011']['dbhost'] = 'localhost';
$options['col2011']['dbname'] = 'ref_col2011ac';
$options['col2011']['dbuser'] = '';
$options['col2011']['dbpass'] = '';
// Fauna Europea
$options['fe']['dbhost'] = 'localhost';
$options['fe']['dbname'] = 'ref_faunaeuropaea';
$options['fe']['dbuser'] = '';
$options['fe']['dbpass'] = '';
// Fauna Europea v2
$options['fev2']['dbhost'] = 'localhost';
$options['fev2']['dbname'] = 'ref_faunaeuropaea_v2';
$options['fev2']['dbuser'] = '';
$options['fev2']['dbpass'] = '';
// JACQ logging database
$options['log']['dbhost'] = 'localhost';
$options['log']['dbname'] = 'input_log';
$options['log']['dbuser'] = '';
$options['log']['dbpass'] = '';

/**
 * GNA nameParser config
 */
$options['nameParser']['address'] = '127.0.0.1';
$options['nameParser']['port'] = '4334';
$options['nameParser']['timeout'] = 1;  // timeout in seconds

/**
 * strings that should be ignored by parsing/atomizing functions
 * suffice genus or connect genus and epithet
 */
$options['taxonExclude'] = array('aff', 'aff.', 'cf', 'cf.', 'cv', 'cv.', 'agg', 'agg.', 'sect', 'sect.', 'ser', 'ser.', 'grex');

/**
 * strings of rank are recognized as seperators between species- and infraspecific-epithet
 */
$options['taxonRankTokens'] = array('1a' => 'subsp.',  '1b' => 'subsp',
                                    '2a' => 'var.',    '2b' => 'var',
                                    '3a' => 'subvar.', '3b' => 'subvar',
                                    '4a' => 'forma',
                                    '5a' => 'subf.',   '5b' => 'subf',   '5c' => 'subforma');  // forma my be f.