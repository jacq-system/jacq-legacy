<?php
/**
 * database access
 */
$options['hrdb']['dbhost'] = "host";        // hostname for heimo reiners database
$options['hrdb']['dbname'] = "dbname";      // database name for heimo reiners database
$options['hrdb']['dbuser'] = "username";    // username for heimo reiners database
$options['hrdb']['dbpass'] = "password";    // password for heimo reiners database
$options['col']['dbhost']  = "dbhost";      // hostname for catalogue of life
$options['col']['dbname']  = "dbname";      // database name for catalogue of life
$options['col']['dbuser']  = "dbuser";      // username for catalogue of life
$options['col']['dbpass']  = "dbpass";      // password for catalogue of life
$options['col2011']['dbhost']  = "dbhost";  // hostname for catalogue of life
$options['col2011']['dbname']  = "dbname";  // database name for catalogue of life
$options['col2011']['dbuser']  = "dbuser";  // username for catalogue of life
$options['col2011']['dbpass']  = "dbpass";  // password for catalogue of life
$options['fe']['dbhost']  = "host";         // hostname for fauna europea
$options['fe']['dbname']  = "dbname";       // database name for fauna europea
$options['fe']['dbuser']  = "username";     // username for fauna europea
$options['fe']['dbpass']  = "password";     // password for fauna europea

$options['fev2']['dbhost']  = "host";       // hostname for fauna europeaV2
$options['fev2']['dbname']  = "dbname";     // database name for fauna europeaV2
$options['fev2']['dbuser']  = "username";   // username for fauna europeaV2
$options['fev2']['dbpass']  = "password";   // password for fauna europeaV2

$options['log']['dbhost'] = 'host';         // hostname for logging
$options['log']['dbname'] = "dbname";       // database for logging
$options['log']['dbuser'] = "username";     // username for logging
$options['log']['dbpass'] = "password";     // password for logging


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