<?php
require_once './inc/variables.php';

/** @var mysqli $dbLink */
$dbLink = new mysqli($_CONFIG['DATABASE']['JACQ']['host'],
                     $_CONFIG['DATABASE']['JACQ']['readonly']['user'],
                     $_CONFIG['DATABASE']['JACQ']['readonly']['pass'],
                     $_CONFIG['DATABASE']['JACQ']['name']);
if ($dbLink->connect_errno) {
    die("Database not available!");
}
$dbLink->set_charset('utf8');

$uuid = $dbLink->real_escape_string(filter_input(INPUT_GET, 'uuid', FILTER_SANITIZE_STRING));
$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);

function makeNomServiceLabels($taxonID)
{
    global $dbLink;

    $rows = $dbLink->query("SELECT param1, provider, url_head, serviceID
                            FROM herbar_view.view_taxon_link_service         
                            WHERE taxonID = " . intval($taxonID))
                   ->fetch_all(MYSQLI_ASSOC);
    $labels = array();
    foreach ($rows as $row) {
        $labels[$row['serviceID']] = "<a href='{$row['url_head']}{$row['param1']}' title='{$row['provider']}' target='_blank'>"
            . "<img src='https://input.jacq.org/herbarium/webimages/nomService/serviceID{$row['serviceID']}_logo.png' alt='{$row['provider']}' height='50px' style='border: 1px solid black'>"
            . "</a>";
    }
    return $labels;
}

function displayScientificName($scientificName, $labels)
{
    echo "<html lang='en'>\n"
       . "<head></head>\n"
       . "<body>$scientificName<br>\n";
    foreach ($labels as $label) {
        echo $label . "&nbsp;";
    }
    echo "</body></html>\n";
}


$result = $dbLink->query("SELECT `uuid_minter_type`, `internal_id` FROM `uuid_replica` WHERE `uuid` = '$uuid'");
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($type == 'internal_id') {
        echo $row['internal_id'];
    } elseif ($type == 'type') {
        echo $row['uuid_minter_type'];
    } else {
        if ($row['uuid_minter_type'] == 'scientific_name') {
            $dbLink->query("CALL herbar_view.GetScientificNameComponents({$row['internal_id']},@genericEpithet,@specificEpithet,@infraspecificRank,@infraspecificEpithet,@author)");
            $res = $dbLink->query("SELECT @genericEpithet,@specificEpithet,@infraspecificRank,@infraspecificEpithet,@author");
            $row2 = $res->fetch_assoc();
            if ($row2) {
                $scientificName = $row2['@genericEpithet'] . " " . $row2['@specificEpithet'] . (($row2['@infraspecificEpithet']) ? "\n" . $row2['@infraspecificRank'] . " " . $row2['@infraspecificEpithet'] : "") . " " . $row2['@author'];
            } else {
                $scientificName = '';
            }
            displayScientificName($scientificName, makeNomServiceLabels($row['internal_id']));
        } elseif ($row['uuid_minter_type'] == 'citation') {
            $result = $dbLink->query("SELECT `herbar_view`.GetProtolog('{$row['internal_id']}') AS protolog");
            if ($result->num_rows > 0) {
                $row2 = $result->fetch_assoc();
                echo($row2['protolog']);
            }
        } elseif ($row['uuid_minter_type'] == 'specimen') {
            echo "specimen: " . $row['internal_id'];
        }
    }
}
