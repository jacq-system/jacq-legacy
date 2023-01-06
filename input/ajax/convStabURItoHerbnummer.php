<?php

require __DIR__ . '/../vendor/autoload.php';

$herbnummer = new \Jacq\HerbNummerScan($_POST['stableuri']);
echo json_encode(array('HerbNummer' => $herbnummer->getHerbNummer()), JSON_NUMERIC_CHECK);

/*
$url = $_POST['stableuri'];

if (filter_var($url, FILTER_VALIDATE_URL)) {

$herbariumid = substr($url, strrpos($url, '/') + 1);
$herbariumid_length = strlen($herbariumid);
$col = parse_url($url, PHP_URL_HOST);
$col_length = strlen(explode('.', $col)[0]);

switch($col)
{
    case 'herbarium.bgbm.org':
        $herbariumid_begin = substr($herbariumid, 0, 3);
        switch ($herbariumid_begin) {
            case 'BW0':
            case 'BW1':
            case 'BW2':
               if($herbariumid_length == 10)
                    {
                        $herbnummer = substr($herbariumid, 0, 1).' -'.substr($herbariumid, 1, 1).' '.substr($herbariumid, 2, 5).' -'.substr($herbariumid, 7, 2).' '.substr($herbariumid, -1, 1);
                    }
                else
                    {
                        $herbnummer = substr($herbariumid, 0, 1).' -'.substr($herbariumid, 1, 1).' '.substr($herbariumid, 2, 6).'-'.substr($herbariumid, 8, 2).' '.substr($herbariumid, -1, 1);
                    }
            break;
            case 'B31':
                $herbnummer = substr($herbariumid, 0, 1).' '.substr($herbariumid, 1, 2).' '.substr($herbariumid, 3, 4).' '.substr($herbariumid, strlen($herbariumid_length)+6);
            break;
            default:
                $herbnummer = substr($herbariumid, 0, 1).' '.substr($herbariumid, 1, 2).' '.substr($herbariumid, strlen($herbariumid_length)+1);
        }
    break;
    case 'pav.jacq.org':
    case 'wu.jacq.org':
        $herbariumid_begin = substr($herbariumid, 0, 6);
        switch ($herbariumid_begin) {
            case 'PAV-LOM':
            case 'WU-MYC':
                $herbnummer = substr($herbariumid, 6 - $herbariumid_length);
            break;
            default:
                $herbnummer = substr($herbariumid, $col_length - $herbariumid_length);
        }
        break;
    case 'bp.jacq.org':
        $herbnummer = substr($herbariumid, 0, 4).'-'.substr($herbariumid, 4, 3).' '.substr($herbariumid, strlen($herbariumid_length)+5);
        break;

    case 'tbi.jacq.org':
    case 'lagu.jacq.org':
        $herbnummer = $herbariumid;
        break;
    default:
        $herbnummer = substr($herbariumid, $col_length - $herbariumid_length);
}
} else {
         $herbnummer = $url;
}

echo json_encode(array('HerbNummer' => $herbnummer), JSON_NUMERIC_CHECK);
*/
