<?php

namespace Jacq\Jaxon;

use Jacq\DbAccess;
use Exception;
use Jacq\UuidMinter;

class ListTaxServer extends \Jaxon\CallableClass
{

    /**
     * stores the number of labels to print for a given specimenID
     *
     * @param int $id taxonID
     * @param int $ctr
     */
    function updateScientificNameLabel($id, $ctr)
    {
        $id = intval($id);
        $ctr = intval($ctr);

        try {
            $dbLink = DbAccess::ConnectTo('INPUT');

            $constraint = "`taxonID` = $id AND `userID` = '" . $_SESSION['uid'] . "'";
            $result = $dbLink->query("SELECT `uuid`, `nr` FROM `tbl_labels_scientificName` WHERE $constraint");
            if ($result->num_rows > 0) {
                if ($ctr) {
                    $dbLink->query("UPDATE `tbl_labels_scientificName` SET `nr` = $ctr WHERE $constraint");
                } else {
                    $dbLink->query("DELETE FROM `tbl_labels_scientificName` WHERE $constraint");
                }
            } else {
                $uuidMinter = new UuidMinter();
                $dbLink->query("INSERT INTO `tbl_labels_scientificName` SET
                                 `uuid`    = '" . $uuidMinter->getUUIDfromTaxonID($id) . "',
                                 `taxonID` = $id,
                                 `userID`  = '" . $_SESSION['uid'] . "',
                                 `nr`      = $ctr");
            }
        } catch (Exception $e) {
            error_log("ListTaxServer.updateScientificNameLabel: " . $e->__toString() . "\n");
        }

        return $this->response;
    }

    /**
     * clears all counters for the name labels
     */
    function clearScientificNameLabels()
    {
        try {
            $dbLink = DbAccess::ConnectTo('INPUT');

            $result = $dbLink->query("SELECT `taxonID`, `uuid` FROM `tbl_labels_scientificName` WHERE `userID` = '" . $_SESSION['uid'] . "'");
            while ($row = $result->fetch_array()) {
                $id = $row['taxonID'];
                $dbLink->query("DELETE FROM `tbl_labels_scientificName` WHERE `taxonID` = '$id' AND `userID` = '" . $_SESSION['uid'] . "'");
                $this->response->assign("inpScientificNameLabel_$id", 'value', 0);
            }
        } catch (Exception $e) {
            error_log("ListTaxServer.clearScientificNameLabels: " . $e->__toString() . "\n");
        }

        return $this->response;
    }

    /**
     * set label counter of every shown line to 1
     */
    function setAll()
    {
        try {
            $dbLink = DbAccess::ConnectTo('INPUT');

            if ($_SESSION['labelTaxSQL']) {
                $result = $dbLink->query($_SESSION['labelTaxSQL']);
                while ($row = $result->fetch_array()) {
                    $id = $row['taxonID'];

                    $constraint = "`taxonID` = $id AND `userID` = '" . $_SESSION['uid'] . "'";
                    $result2 = $dbLink->query("SELECT `uuid`, `nr` FROM `tbl_labels_scientificName` WHERE $constraint");
                    if ($result2->num_rows > 0) {
                        $dbLink->query("UPDATE `tbl_labels_scientificName` SET `nr` = 1 WHERE $constraint");
                    } else {
                        $uuidMinter = new UuidMinter();
                        $dbLink->query("INSERT INTO `tbl_labels_scientificName` SET
                                         `uuid`    = '" . $uuidMinter->getUUIDfromTaxonID($id) . "',
                                         `taxonID` = $id,
                                         `userID`  = '" . $_SESSION['uid'] . "',
                                         `nr`      = 1");
                    }
                    $this->response->assign("inpScientificNameLabel_$id", 'value', 1);
                }
            }
        } catch (Exception $e) {
            error_log("ListTaxServer.setAll: " . $e->__toString() . "\n");
        }

        return $this->response;
    }

    /**
     * clear label counter of every shown line
     */
    function clearAll()
    {
        try {
            $dbLink = DbAccess::ConnectTo('INPUT');

            if ($_SESSION['labelTaxSQL']) {
                $result = $dbLink->query($_SESSION['labelTaxSQL']);
                while ($row = $result->fetch_array()) {
                    $id = intval($row['taxonID']);
                    $this->response->assign("inpScientificNameLabel_$id", 'value', 0);
                    $dbLink->query("DELETE FROM `tbl_labels_scientificName` WHERE `taxonID` = $id AND `userID` = '" . $_SESSION['uid'] . "'");
                }
            }
        } catch (Exception $e) {
            error_log("ListTaxServer.clearAll: " . $e->__toString() . "\n");
        }

        return $this->response;
    }
}
