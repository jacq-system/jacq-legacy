<?php
/**
 * xajax-class to be called by the dispatcher - tests
 *
 * An example for a dispatcher-class
 *
 * @author Johannes Schachner
 * @version 1.0
 * @package xclsTest
 */

require_once('inc/jsonRPCClient.php');

set_time_limit(3600);

/**
 * xajax-class to be called by the dispatcher - tests
 * @package xclsTest
 * @subpackage classes
 */
class xclsCheckPictures extends xclsBase
{
/*************\
|             |
|  variables  |
|             |
\*************/


/***************\
|               |
|  constructor  |
|               |
\***************/

public function __construct ()
{
    parent::__construct();
}


/*******************\
|                   |
|  public functions |
|                   |
\*******************/

public function x_listInstitutions ($formData)
{
    try {
        $text = "<option value=\"0\">--- all ---</option>\n";

        /* @var $db clsDbAccess */
        $db = clsDbAccess::Connect('INPUT');
        /* @var $dbst PDOStatement */
        $dbst = $db->prepare("SELECT source_name, tbl_management_collections.source_id
                              FROM tbl_management_collections, herbarinput.meta, tbl_img_definition
                              WHERE tbl_management_collections.source_id = herbarinput.meta.source_id
                               AND tbl_management_collections.source_id = tbl_img_definition.source_id_fk
                               AND imgserver_IP = :imgserver_IP
                              GROUP BY source_name ORDER BY source_name");
        $dbst->execute(array(":imgserver_IP" => $formData['serverIP']));
        foreach ($dbst as $row) {
            $text .= "<option value=\"{$row['source_id']}\">{$row['source_name']}</option>\n";
        }

        $this->objResponse->assign('source_id', 'innerHTML', $text);
    }
    catch (Exception $e) {
        $this->objResponse->alert($e->getMessage());
    }
}

public function x_rescanPictureServer ($formData)
{
    if (preg_match('/^((1?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(1?\d{1,2}|2[0-4]\d|25[0-5]){1}$/', $formData['serverIP'])) {

        // connect to the database and abort if this is not possible
        try {
            /* @var $db clsDbAccess */
            $db = clsDbAccess::Connect('INPUT');
        }
        catch (Exception $e) {
            $this->objResponse->alert($e->getMessage());
            return;
        }

        try {
            // check if any script has died (db entry older than ten minutes) and correct the entry
            /* @var $dbst PDOStatement */
            $dbst = $db->query("SELECT count(ID)
                                FROM herbar_pictures.scans
                                WHERE TIME_TO_SEC(TIMEDIFF(NOW(), start)) > 600
                                 AND finish IS NULL
                                 AND IP = " . $db->quote($formData['serverIP']));
            if ($dbst->fetchColumn() > 0) {
                $db->query("UPDATE herbar_pictures.scans SET finish = NOW(), errors = 'script terminated, entry corrected' WHERE finish IS NULL AND IP = " . $db->quote($formData['serverIP']));
            }

            // check if any open entry is still left and stop if yes
            $dbst = $db->query("SELECT count(ID)
                                FROM herbar_pictures.scans
                                WHERE TIME_TO_SEC(TIMEDIFF(NOW(), start)) < 600
                                 AND finish IS NULL
                                 AND IP = " . $db->quote($formData['serverIP']));
            if ($dbst->fetchColumn() > 0) {
                $this->objResponse->alert('A scan is already running');
                return;
            }

            // mark the beginning
            $db->query("INSERT INTO herbar_pictures.scans SET IP = " . $db->quote($formData['serverIP']) . ", start = NOW()");
            $scanID = $db->lastInsertId();
        }
        catch (Exception $e) {
            $this->objResponse->alert($e->getMessage());
            $scanID = 0;
        }

        // call the json-rpc-script on the picture server and transfer the directory data
        try {
            $service = new jsonRPCClient('http://' . $formData['serverIP'] . '/database/json_rpc_scanPictures.php');
            $data = $service->getPictures($formData['serverIP'], 'DKsuuewwqsa32czucuwqdb576i12');
        }
        catch (Exception $e) {
            // anything went wrong so close the attempt and log the errors
            $db->query("UPDATE herbar_pictures.scans SET finish = NOW(), errors = " . $db->quote($e->getMessage()) . " WHERE ID = $scanID");
            $this->objResponse->alert($e->getMessage());
            $this->x_getLastScan($formData);
            return;
        }

        if (!empty($data['results'])) {
            // fill the column "filename" of tbl_specimens
            try {
                $dbst = $db->query("SELECT s.specimen_ID, s.HerbNummer, s.collectionID, mc.coll_short_prj, mc.source_id
                                    FROM tbl_specimens s, tbl_management_collections mc
                                    WHERE s.collectionID = mc.collectionID
                                     AND HerbNummer IS NOT NULL");
                foreach ($dbst as $row) {
                    $filename = $this->_formatPictureName($row['coll_short_prj'], $row['HerbNummer'], $row['collectionID'], $row['source_id']);
                    if ($filename) $db->query("UPDATE tbl_specimens SET
                                                filename = '$filename',
                                                aktualdatum = aktualdatum
                                               WHERE specimen_ID = " . $row['specimen_ID']);
                }
            }
            catch (Exception $e) {
                $this->objResponse->alert($e->getMessage());
            }

            // store the metadata of all image files in these subdirs
            try {
                $dbst = $db->prepare("DELETE FROM herbar_pictures.files WHERE IP = :ip");  // erase all of my data in table
                $dbst->execute(array(':ip' => $formData['serverIP']));
                $ctr = 0;
                foreach ($data['results'] as $source => $files) {
                    $source_id = intval($source);
                    foreach ($files as $file) {
                        $pathname = dirname($file['filename']);
                        $filename = basename($file['filename']);
                        $pos = strrpos($filename, '.');
                        if ($pos) {
                            $extension = substr($filename, $pos + 1);
                            $filename = substr($filename, 0, $pos);
                        } else {
                            $extension = '';
                        }
                        $pos = strpos($filename, '_');
                        $basefile = (strpos($filename, '_', $pos + 1)) ? substr($filename, 0, strpos($filename, '_', $pos + 1)) : $filename;
                        $collShort = substr($filename, 0, $pos);
                        $dbst2 = $db->query("SELECT s.specimen_ID
                                             FROM tbl_specimens s, tbl_management_collections mc
                                             WHERE  s.collectionID = mc.collectionID
                                              AND mc.source_id = $source_id
                                              AND filename = " . $db->quote($basefile));
                        $rows = $dbst2->fetchAll();
                        $specimen_ID = (count($rows) > 0) ? $rows[0]['specimen_ID'] : NULL;

                        if ($ctr == 0) {
                            $values = array();
                            $insert = array();
                        }

                        $insert[] = "(?, ?, ?, ?, ?, ?, FROM_UNIXTIME(?), ?, ?)";
                        $values[] = $source_id;
                        $values[] = $filename;
                        $values[] = $extension;
                        $values[] = $pathname;
                        $values[] = $basefile;
                        $values[] = $collShort;
                        $values[] = $file['mtime'];
                        $values[] = $specimen_ID;
                        $values[] = $formData['serverIP'];
                        $ctr++;

                        if ($ctr > 100) {
                            $dbst = $db->prepare("INSERT INTO herbar_pictures.files (source_id, file, extension, path, basefile, img_coll_short, mtime, specimen_ID, IP) VALUES " . implode(",\n", $insert));
                            $dbst->execute($values);
                            $ctr = 0;
                        }
                    }
                }
                if ($ctr > 0) {
                    $dbst = $db->prepare("INSERT INTO herbar_pictures.files (source_id, file, extension, path, basefile, img_coll_short, mtime, specimen_ID, IP) VALUES " . implode(",\n", $insert));
                    $dbst->execute($values);
                }
            }
            catch (Exception $e) {
                $this->objResponse->alert($e->getMessage());
            }
        }

        // everything is finished here so close the attempt and log the errors (if any)
        $db->query("UPDATE herbar_pictures.scans SET finish = NOW(), errors = " . $db->quote(implode("\n", $data['errors'])) . " WHERE ID = $scanID");
    }

    $this->x_getLastScan($formData);
}

public function x_getLastScan ($formData)
{
    try {
        /* @var $db clsDbAccess */
        $db = clsDbAccess::Connect('INPUT');
        /* @var $dbst PDOStatement */
        $dbst = $db->prepare("SELECT start, finish
                              FROM herbar_pictures.scans
                              WHERE IP = :IP
                              ORDER BY start DESC
                              LIMIT 1");
        $dbst->execute(array(":IP" => $formData['serverIP']));
        $row = $dbst->fetch();
        if ($row) {
            if (!$row['finish']) {
                $response = "scan in progress (started " . $row['start'] . " UTC)";
            } else {
                $response = "last scan " . $row['finish'] . " UTC";
            }

        } else {
            $response = "no scan yet";
        }
    
        $this->objResponse->assign('lastScan', 'innerHTML', $response);
        $this->objResponse->assign('checkResults', 'innerHTML', '');
    }
    catch (Exception $e) {
        $this->objResponse->alert($e->getMessage());
    }
}

public function x_checkPictures ($formData)
{
    $source_id = intval($formData['source_id']);
    $family    = trim($formData['family']);
    $serverIP  = trim($formData['serverIP']);

    $picHarddiskMissing = $picDatabaseMissing = $picDatabaseNocheck = $picDatabaseFaulty = array();

    if (preg_match('/^((1?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(1?\d{1,2}|2[0-4]\d|25[0-5]){1}$/', $serverIP)) {
        try {
            /* @var $db clsDbAccess */
            $db = clsDbAccess::Connect('INPUT');
        }
        catch (Exception $e) {
            $this->objResponse->alert($e->getMessage());
            return;
        }

        try {
            // get a list of files checked in db but missing on disk
            $sql = "SELECT s.HerbNummer, s.specimen_ID, coll_short_prj, s.collectionID, s.filename, mc.source_id
                    FROM (tbl_specimens s, tbl_management_collections mc, tbl_img_definition id)
                     LEFT JOIN herbar_pictures.files hpf ON hpf.specimen_ID = s.specimen_ID";
            if ($family) $sql .= " LEFT JOIN tbl_tax_species ts ON ts.taxonID = s.taxonID
                                   LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                                   LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID";
            $sql .= " WHERE s.collectionID = mc.collectionID
                       AND mc.source_id = id.source_id_fk
                       AND s.filename IS NOT NULL
                       and s.filename != 'error'
                       AND digital_image != 0
                       AND id.imgserver_IP = " . $db->quote($serverIP) . "
                       AND hpf.basefile IS NULL";
            if ($source_id) $sql .= " AND mc.source_id = $source_id";
            if ($family)    $sql .= " AND tf.family LIKE " . $db->quote ($family . '%');
            $sql .= " ORDER BY s.filename";

            $dbst = $db->query($sql);
            foreach ($dbst as $row) {
                $picHarddiskMissing[$row['specimen_ID']] = $row['filename'];
            }

            if (!$family) {   // if family is set it doesn't make sense here
                // get a list of files where no specimen-ID is present means files on disk but not in db
                $sql = "SELECT basefile
                        FROM herbar_pictures.files
                        WHERE specimen_ID IS NULL
                         AND IP = " . $db->quote($serverIP);
                if ($source_id) $sql .= " AND source_id = $source_id";
                $sql .= " GROUP BY basefile
                          ORDER by basefile";

                $dbst = $db->query($sql);
                foreach ($dbst as $row) {
                    $picDatabaseMissing[] = $row['basefile'];
                }
            }

            // get a list of files not checked in db but on disk
            $sql = "SELECT s.specimen_ID, hpf.basefile
                    FROM tbl_specimens s
                     LEFT JOIN herbar_pictures.files hpf ON hpf.specimen_ID = s.specimen_ID";
            if ($family) $sql .= " LEFT JOIN tbl_tax_species ts ON ts.taxonID = s.taxonID
                                   LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                                   LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID";
            $sql .= " WHERE digital_image = 0
                       AND hpf.IP = " . $db->quote($serverIP);
            if ($source_id) $sql .= " AND hpf.source_id = $source_id";
            if ($family)    $sql .= " AND tf.family LIKE " . $db->quote ($family . '%');
            $sql .= " GROUP BY hpf.basefile
                      ORDER BY hpf.basefile";

            $dbst = $db->query($sql);
            foreach ($dbst as $row) {
                $picDatabaseNocheck[$row['specimen_ID']] = $row['basefile'];
            }

            // get a list of all faulty database entries regarding herbNummer
            $dbst = $db->query("SELECT specimen_ID, HerbNummer
                                FROM tbl_specimens
                                WHERE filename = 'error'
                                ORDER BY collectionID, HerbNummer");
            foreach ($dbst as $row) {
                $picDatabaseFaulty[$row['specimen_ID']] = $row['HerbNummer'];
            }

            // and now put everything together and send it to the display
            $response = "<table align='center'>
                           <tr>
                             <th>" . count($picHarddiskMissing) . " missing pictures</th>
                             <th></th>
                             <th>" . count($picDatabaseMissing) . " missing database entries</th>
                             <th></th>
                             <th>" . count($picDatabaseNocheck) . " missing database checks</th>
                             <th></th>
                             <th>" . count($picDatabaseFaulty) . " faulty database entries</th>
                           </tr><tr>
                             <td>";
            if ($picHarddiskMissing) {
                foreach ($picHarddiskMissing as $key => $value)
                    $response .= "<a href=\"javascript:editSpecimens('<$key>')\">"
                               . htmlspecialchars($value)
                               . "</a><br>\n";
            }
            $response .=  "</td>
                           <td width='20'>&nbsp;</td>
                           <td>";
            if ($picDatabaseMissing) {
                $dbst = $db->query("SELECT collectionID, coll_short_prj FROM tbl_management_collections GROUP BY coll_short_prj");
                foreach ($dbst as $row) {
                    $collection[$row['coll_short_prj']] = $row['collectionID'];
                }
                $collection['gjo']     = 17;  // these are ambiguous
                $collection['gzu']     = 18;
                $collection['w']       = 19;
                $collection['w-krypt'] = 90;
                $collection['wu']      = 1;

                $response .=  "<table class='missing'>\n";
                foreach ($picDatabaseMissing as $value) {
                    $pieces = explode("_", basename($value),2);
                    $collNr = $collection[$pieces[0]];
                    $HerbNr = intval($pieces[1]);
                    $response .= "<tr class='missing'>\n"
                               . "<td class='missing'>"
                               . "<form Action='editSpecimensSimple.php' Method='POST' target='editSimple' class='missing'>"
                               . "<input type='hidden' name='checked' value='1'>"
                               . "<input type='hidden' name='accessible' value='1'>"
                               . "<input type='hidden' name='digital_image' value='1'>"
                               . "<input type='hidden' name='collection' value='$collNr'>"
                               . "<input type='hidden' name='HerbNummer' value='$HerbNr'>"
                               . "<input type='submit' value='insert' onclick=\"openBrowser('$value', '$serverIP')\">"
                               . "</form></td>\n"
                               . "<td class=\"missing\">"
                               . "<a href=\"javascript:openBrowser('$value', '$serverIP')\">"
                               . htmlspecialchars($value)
                               . "</a></td>\n"
                               . "</tr>\n";
                }
                $response .= "</table>\n";
            } else if (trim($family)) {
                $response .= "not solvable\n";
            }
            $response .= "</td>
                          <td width='20'>&nbsp;</td>
                          <td>";
            if ($picDatabaseNocheck) {
                foreach ($picDatabaseNocheck as $key => $value) {
                    $dbst = $db->query("SELECT collection
                                        FROM tbl_specimens s, tbl_management_collections mc
                                        WHERE s.collectionID = mc.collectionID
                                         AND specimen_ID = '" . intval($key) . "'
                                        LIMIT 1");
                    $row = $dbst->fetch();
                    $response .= "<a href=\"javascript:editSpecimens('<$key>')\">"
                               . htmlspecialchars($row['collection'] . ': ' . $value)
                               . "</a><br>\n      ";
                }
            }
            $response .= "</td>
                          <td width='20'>&nbsp;</td>
                          <td>";
            if ($picDatabaseFaulty) {
                foreach ($picDatabaseFaulty as $key => $value) {
                    $dbst = $db->query("SELECT collection
                                        FROM tbl_specimens s, tbl_management_collections mc
                                        WHERE s.collectionID = mc.collectionID
                                         AND specimen_ID = '" . intval($key) . "'
                                        LIMIT 1");
                    $row = $dbst->fetch();
                    $response .= "<a href=\"javascript:editSpecimens('<$key>')\">"
                               . htmlspecialchars($row['collection'] . ': ' . $value)
                               . "</a><br>\n      ";
                }
            }
            $response .= "</td>
                          </tr>
                          </table>";
        }
        catch (Exception $e) {
            $this->objResponse->alert($e->getMessage());
        }

        $this->objResponse->assign('checkResults', 'innerHTML', $response);
    }
}

/********************\
|                    |
|  private functions |
|                    |
\********************/

//formatPictureName($row['coll_short_prj'], $row['HerbNummer'], $row['collectionID'], $row['source_id'], $row['specimen_ID']);
private function _formatPictureName($collShort, $herbNumber, $collectionID, $sourceID) {
    if (trim($herbNumber)) {
        $pic = $collShort . "_";
        if (strpos($herbNumber, "-") === false) {
            if ($collectionID == 89) {
                if (strlen($herbNumber) > 8) {
                    $pic = 'error';
                } else {
                    $pic .= sprintf("%08d", $herbNumber);
                }
            } elseif ($sourceID == 4) {
                if (strlen($herbNumber) > 9) {
                    $pic = 'error';
                } else {
                    $pic .= sprintf("%09d", $herbNumber);
                }
            } else {
                if (strlen($herbNumber) > 7) {
                    $pic = 'error';
                } else {
                    $pic .= sprintf("%07d", $herbNumber);
                }
            }
        } else {
            $pic .= str_replace("-","", $herbNumber);
        }
    } else {
        $pic = '';
    }

    return $pic;
}


}