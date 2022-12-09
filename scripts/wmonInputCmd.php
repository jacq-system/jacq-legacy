#!/usr/bin/php -qC
<?php
$dbLink = new mysqli('localhost', 'wmon', 'F0]FTx_Nxg0g7RCW', 'monitor');

$ret = shell_exec('/opt/wildfly/bin/jboss-cli.sh -c "/core-service=platform-mbean/type=memory:read-attribute(name=heap-memory-usage)"');
$json = str_replace([" => ", "L,\n", "L\n"],
                    [": "  , ",\n" , "\n"],
                    $ret);  // change format to json
$data = json_decode($json, true);

if ($data['outcome'] == 'success') {  // got a result
    $dbLink->query("INSERT INTO tbl_wildfly_input SET 
                     outcome   = '" . $data['outcome'] . "',
                     init      = " . $data['result']['init'] . ",
                     used      = " . $data['result']['used'] . ",
                     committed = " . $data['result']['committed'] . ",
                     max       = " . $data['result']['max']);
} else {  // something went wrong
    $dbLink->query("INSERT INTO tbl_wildfly_input SET 
                     outcome   = '" . $data['outcome'] . "'");
}
