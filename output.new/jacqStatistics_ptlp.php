<?php
// this is the local pass through landing page for all ajax-operations of the statistics browser

// require configuration
require('inc/variables.php');
require('inc/RestClient.php');

$rest = new RestClient($_CONFIG['JACQ_SERVICES']);

header('Content-Type: application/json');

$type = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
switch ($type) {
    case 'statistics':
        echo json_encode(createTableAndPlot(filter_input(INPUT_GET, 'periodStart', FILTER_SANITIZE_STRING),
                                            filter_input(INPUT_GET, 'periodEnd', FILTER_SANITIZE_STRING),
                                            filter_input(INPUT_GET, 'updated', FILTER_SANITIZE_NUMBER_INT),
                                            filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING),
                                            filter_input(INPUT_GET, 'interval', FILTER_SANITIZE_STRING)));
//        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=jSONStatistics/japi&action=showResults'
//           . '&periodStart=' . filter_input(INPUT_GET, 'periodStart', FILTER_SANITIZE_STRING)
//           . '&periodEnd=' . filter_input(INPUT_GET, 'periodEnd', FILTER_SANITIZE_STRING)
//           . '&updated=' . filter_input(INPUT_GET, 'updated', FILTER_SANITIZE_NUMBER_INT)
//           . '&type=' . filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING)
//           . '&interval=' . filter_input(INPUT_GET, 'interval', FILTER_SANITIZE_STRING));
        break;
}

////////////////////////////// service functions //////////////////////////////
/////////////////////////// for statistics browser ////////////////////////////


/**
 * calls the statistics jacq-service, formats the answer and creates the plot
 *
 * @global RestClient $rest the rest-client
 * @param type $periodStart
 * @param type $periodEnd
 * @param type $updated
 * @param type $type
 * @param type $interval
 * @return type
 */
function createTableAndPlot($periodStart, $periodEnd, $updated, $type, $interval)
{
    global $rest;

    $data = $rest->jsonGet("statistics/results", array($periodStart, $periodEnd, $updated, $type, $interval));

    if (count($data['results']) > 0) {
        $ret = "<table style='width:auto;'><tr>"
             . "<td style='border-bottom:1px solid'></td>"
             . "<td style='border-bottom:1px solid; border-left:1px solid'>min</td>"
             . "<td style='border-bottom:1px solid'>max</td>"
             . "<td style='border-bottom:1px solid'>avg</td>"
             . "<td style='border-bottom:1px solid; border-right:1px solid'>median</td>";
        for ($i = $data['periodMin']; $i <= $data['periodMax']; $i++) {
            $ret .= "<td style='text-align:center; border-bottom:1px solid'>" . $i . "</td>";
            $periodSum[$i] = 0;
        }
        $ret .= "</tr>";

        $plotIndex = 0;
        $plot = array();
        foreach ($data['results'] as $result) {
            if ($result['total'] > 0) {
                $ret .= "<tr>"
                      . "<td><a href='#' onclick='plotInstitution($plotIndex);' style='text-decoration:none;'>" . $result['source_code'] . "</a></td>"
                      . "<td style='text-align:center; border-left:1px solid'>" . min($result['stat']) . "</td>"
                      . "<td style='text-align:center'>" . max($result['stat']) . "</td>"
                      . "<td style='text-align:center'>" . round(avg($result['stat']), 1) . "</td>"
                      . "<td style='text-align:center; border-right:1px solid'>" . median($result['stat']) . "</td>";
                $plot[$plotIndex]['label'] = $result['source_code'];
                for ($i = $data['periodMin']; $i <= $data['periodMax']; $i++) {
                    $ret .= "<td style='text-align:center'>" . $result['stat'][$i] . "</td>";
                    $periodSum[$i] += $result['stat'][$i];
                    $plot[$plotIndex]['data'][] = array($i, $result['stat'][$i]);
                }
                $ret .= "</tr>";
                $plotIndex++;
            }
        }

        $ret .= "<tr>"
              . "<td style='border-top:1px solid'><a href='#' onclick='plotInstitution($plotIndex);' style='text-decoration:none;'>&sum;</a></td>"
              . "<td style='text-align:center; border-top:1px solid; border-left:1px solid'>" . min($periodSum) . "</td>"
              . "<td style='text-align:center; border-top:1px solid'>" . max($periodSum) . "</td>"
              . "<td style='text-align:center; border-top:1px solid'>" . round(avg($periodSum), 1) . "</td>"
              . "<td style='text-align:center; border-top:1px solid; border-right:1px solid'>" . median($periodSum) . "</td>";
        $plot[$plotIndex]['label'] = '&sum;';
        for ($i = $data['periodMin']; $i <= $data['periodMax']; $i++) {
            $ret .= "<td style='text-align:center; border-top:1px solid'>" . $periodSum[$i] . "</td>";
            $plot[$plotIndex]['data'][] = array($i, $periodSum[$i]);
        }
        $ret .= "</tr></table>";

        $plotIndex++;
        return array('display' => $ret, 'plot' => $plot, 'plotMaxIndex' => $plotIndex);
    } else {
        return array('display' => 'nothing found', 'plot' => array(), 'plotMaxIndex' => 0);
    }
}

/**
 * Return average of given data
 * @param array $data
 * @return float
 */
function avg($data)
{
    return array_sum($data) / count($data);
}

/**
 * Return median of given data
 * @param array $data
 * @return float
 */
function median($data)
{
    $anzahl = count($data);
    if ($anzahl == 0 ) {
        return 0;
    }
    sort($data);
    if($anzahl % 2 == 0) {
        // even number => median is average of the two middle values
        return ($data[($anzahl / 2) - 1] + $data[$anzahl / 2]) / 2 ;
    } else {
        // odd number => median is the middle value
        return $data[$anzahl / 2];
    }
}
