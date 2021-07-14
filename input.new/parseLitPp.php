<?php
session_start();
require("inc/connect.php");
require("inc/herbardb_input_functions.php");


function rom2arab ($r)
{
    $r = strtolower($r);
    $f = "mdclxvi";

    if (strlen($r) == 1) {
        switch ($r) {
            case 'i': return    1; break;
            case 'v': return    5; break;
            case 'x': return   10; break;
            case 'l': return   50; break;
            case 'c': return  100; break;
            case 'd': return  500; break;
            case 'm': return 1000; break;
        }
    } elseif (strlen($r) == 0) {
        return 0;
    } else {
        for ($i = 0; $i < strlen($f); $i++) {
            for ($j = 0; $j < strlen($r); $j++) {
                if (substr($r, $j, 1) == substr($f, $i, 1)) {
                    $p = $j;
                    $z = substr($f, $i, 1);
                    break 2;
                }
            }
        }
        return rom2arab($z) - rom2arab(substr($r, 0, $p)) + rom2arab(substr($r, $p + 1));
    }
}


function parsePp ($pp)
{
    $exclude = "[],;. -";
    $roman   = "mdclxvi";

    $result = '';
    $part = '';
    for ($i = 0; $i < strlen($pp); $i++) {
        $needle = substr($pp, $i, 1);
        if (strpos($exclude, $needle) === false) {
            $part .= $needle;
        } else {
            if ($part) {
                if (strpos($roman, substr($part, 0, 1)) !== false) {
                    $result .= sprintf('-%04d', rom2arab($part));
                } else {
                    $result .= sprintf('%05d', $part);
                }
                $part = '';
            }
        }
    }
    if ($part) {
        if (strpos($roman, substr($part, 0, 1)) !== false) {
            $result .= sprintf('-%04d', rom2arab($part));
        } else {
            $result .= sprintf('%05d', $part);
        }
    }

    return $result;
}


$result = dbi_query("SELECT * FROM tbl_lit WHERE pp IS NOT NULL");
while ($row = mysqli_fetch_array($result)) {
    dbi_query("UPDATE tbl_lit SET
                ppSort = " . quoteString(parsePp($row['pp'])) . "
               WHERE citationID = " . $row['citationID']);
}