<?php

//$_POST=$_GET;
ob_start();  // intercept all output


require_once('../inc/jacqServletJsonRPCClient.php');
require_once("../inc/init.php");

$checkDjatoka = new checkDjatoka();
$methodName = (isset($_POST['method'])) ? $_POST['method'] : "";

$ret = array();
if (method_exists($checkDjatoka, $methodName)) {
    try {
        $params = $_POST['params'];
        $ret = array(
            'res' => $checkDjatoka->$methodName($params)
        );
        if (($a = $checkDjatoka->getInfo())) {
            $ret['info'] = $a;
        }
    } catch (Exception $e) {
        $ret = array(
            'error' => $e->getMessage() // . '<br />' . var_export($e->getTrace(), true)
        );
    }
}
else {
    $ret = array(
        'error' => "Metod: '{$methodName}' doesn't exist.",
    );
}

$ob = ob_get_clean();
if (strlen($ob) > 0) {
    $ret['ob'] = $ob;
}
echo json_encode($ret);

class checkDjatoka {

    private $service = false;
    private $db = false;
    private $db_pictures = false;
    private $sharedkey = false;
    private $wrong = false;
    private $info = false;

    public function getInfo() {
        return $this->info;
    }

    public function __construct() {
    }

    /**
     * Handler for input database
     * @return clsDbAccess
     */
    private function getdB() {
        if (!$this->db) {
            $this->db = clsDbAccess::Connect('INPUT');
        }
        return $this->db;
    }
    
    /**
     * Return a handler for accessing the pictures database
     * @return clsDbAccess 
     */
    private function getDbPictures() {
        if( !$this->db_pictures ) {
            $this->db_pictures = clsDbAccess::Connect('PICTURES');
        }
        
        return $this->db_pictures;
    }

    private function getService($serverIP) {
        if (!$this->service) {
            $this->service = new jacqServletJsonRPCClient($serverIP);
        }
        return $this->service;
    }

    /*
      $_SESSION['checkPictures']['serverIP']=0;
      $_SESSION['checkPictures']['family']=0;
      $_SESSION['checkPictures']['source_id']=0;
     */

    public function x_listInstitutions($params) {
        $serverIP = $params['serverIP'];
        $source_id = isset($params['source_id']) ? $params['source_id'] : false;

        $db = $this->getdB();
        $serverIPd = $db->quote($serverIP);

        $dbst = $db->prepare("
SELECT 
  source_name,
  tbl_management_collections.source_id 
FROM
  tbl_management_collections,
  herbarinput.meta,
  tbl_img_definition 
WHERE tbl_management_collections.source_id = herbarinput.meta.source_id 
  AND tbl_management_collections.source_id = tbl_img_definition.source_id_fk 
  AND imgserver_IP = :imgserver_IP -- AND tbl_img_definition.is_djatoka=1
GROUP BY source_name 
ORDER BY source_name
	");
        $res = '';
        $x = 0;
        $dbst->execute(array(":imgserver_IP" => $serverIP));
        foreach ($dbst as $row) {
            $res .= "<option value=\"{$row['source_id']}\">{$row['source_name']}</option>\n";
            $x++;
        }
        if ($x > 1) {
            $res = "<option value=\"0\">--- all ---</option>\n" . $res;
        }
        $res = str_replace("<option value=\"{$source_id}\"",
                "<option value=\"{$source_id}\" selected", $res);

        $dbst2 = $db->query("SELECT finish FROM herbar_pictures.djatoka_scans WHERE finish IS NOT NULL AND errors is null and IP ={$serverIPd} ORDER BY `finish` DESC LIMIT 1");
        $finish = '';
        $scan_id = false;
        if (($row = $dbst2->fetch()) > 0) {
            $finish = $row['finish'];
        }

        $res = array(
            'inst' => $res,
            'lastscan' => $finish
        );

        return $res;
    }

    // triggers an image import process
    public function x_ImportImages($params) {
        $serverIP = $params['serverIP'];
        
        $service = &$this->getService($serverIP);
        $result = $service->importImages();

        if ($result > 0) {
            $message = "Import was successfully triggered";
        }
        else {
            $message = "Thread Already running.";
        }
        return $message;
    }

    public function x_listImportLogs($params) {
        $serverIP = $params['serverIP'];
        $thread_id = $params['thread_id'];
        $start = isset($params['page_index']) ? intval($params['page_index']) : 0;
        $limit = isset($params['limit']) ? intval($params['limit']) : 20;
        $type = $params['type'];

        $start = $start * $limit;

        $end = $start + $limit;
        $service = &$this->getService($serverIP);
        $logs = $service->listImportLogs($thread_id);
        
        $maxc = count($logs);
        $result = "";
        $x = 0;
        foreach ($logs as $logmsg) {
            $x++;
            if ($x <= $start)
                continue;
            
            $logmsg['logtime'] = date( 'Y-m-d H:i', $logmsg['logtime'] );
            
            if( $type == 1 && !empty($logmsg['identifier']) ) {
                $result.="<a href=\"javascript:processItem('" . $logmsg['identifier'] . "')\">[". $logmsg['logtime'] . "] [" . $logmsg['identifier'] . "] " . $logmsg['message'] . "</a><br>";
            }
            else if( !empty($logmsg['identifier']) ) {
                $result.="[". $logmsg['logtime'] . "] [" . $logmsg['identifier'] . "] " . $logmsg['message'] . "<br>";
            }
            else {
                $result.="[". $logmsg['logtime'] . "] " . $logmsg['message'] . "<br>";
            }
            if ($x >= $end)
                break;
        }
        return array('html' => $result, 'maxc' => $maxc);
    }

    public function x_listImportThreads($params) {
        $serverIP = $params['serverIP'];
        $starttime = $params['starttime'];
        $start = isset($params['page_index']) ? intval($params['page_index']) : 0;
        $limit = isset($params['limit']) ? intval($params['limit']) : 20;

        $start = $start * $limit;
        $end = $start + $limit;

        $timestamp = strtotime($starttime); // 2011/1/19
        $d = date('d.m.Y H:i', $timestamp);

        $service = &$this->getService($serverIP);
        $ImportThreads = $service->listThreads($timestamp);

        $maxc = count($ImportThreads);

        $result = "";
        $x = 0;
        foreach ($ImportThreads as $threadid => $threadInfo) {
            $x++;
            if ($x <= $start)
                continue;
            $d = date('d.m.Y H:i', $threadInfo['starttime']);
            $de = date('d.m.Y H:i', $threadInfo['endtime']);
            $type = ( $threadInfo['type'] == 1 ) ? 'Import' : 'Export';

            $result.="<a href=\"javascript:loadImportLog('{$threadid}','{$d}', {$threadInfo['type']})\">[{$type}] [{$threadid}] {$d} - {$de}</a><br>";
            if ($x >= $end)
                break;
        }

        return array('html' => $result, 'maxc' => $maxc);
    }

    public function x_pictures_check($params) {
        $serverIP = $params['serverIP'];
        $family = isset($params['family']) ? $params['family'] : false;
        $source_id = isset($params['source_id']) ? intval($params['source_id']) : false;
        $faulty = isset($params['faulty']) ? $params['faulty'] : false;

        $start = isset($params['page_index']) ? intval($params['page_index']) : 0;
        $limit = isset($params['limit']) ? intval($params['limit']) : 20;

        $start = $start * $limit;

        $db = $this->getdB();

        $limit = " LIMIT {$start}, {$limit}";


        $scan_id = $this->getLatestScanId($serverIP);

        if ($scan_id == 0) {
            throw new Exception("A Scan is already running on '{$serverIP}'. refresh in a few seconds.");
        }

        $c = array(0, 0, 0, 0);
        $tab1 = '';
        
        // Analyze entries in file table & update them
        $dbPictures = $this->getDbPictures();
        $dbsth = $dbPictures->query("SELECT `ID`, `filename` FROM `djatoka_files` WHERE `specimen_ID` IS NULL AND `faulty` = 0 AND `scan_id` = $scan_id");
        foreach( $dbsth as $row ) {
            $filename = $row['filename'];
            $ID = $row['ID'];
            $specimen_ID  = null;
            
            // Extract herbar-number from filename
            $filename_parts = explode('_', $filename);
            if( count($filename_parts) < 2 || count($filename_parts) > 3 ) {
                // Invalid file naming
                $dfuSth = $dbPictures->prepare("UPDATE `djatoka_files` SET `faulty` = 1 WHERE `ID` = :ID");
                $dfuSth->execute(array(
                    ':ID' => $ID
                ));
                continue;
            }
            
            // Check if we have a tab or obs entry
            $matches = array();
            if( preg_match('/^(tab|obs)_(\d+)/i', $filename, $matches) > 0 ) {
                $file_specimen_ID = $matches[2];
                
                // Check if specimen_ID exists
                $sidSth = $db->prepare( "SELECT `specimen_ID` FROM `tbl_specimens` WHERE `specimen_ID` = :specimen_ID" );
                $sidSth->execute(array(
                    ':specimen_ID' => $file_specimen_ID
                ));
                
                // If we found an entry, set the specimen_ID
                $rows = $sidSth->fetchAll();
                if( count($rows) > 0 ) {
                    $specimen_ID = $file_specimen_ID;
                }
            }
            else {
                // use parts of filename
                $coll_short_prj = $filename_parts[0];
                $HerbNummer = $filename_parts[1];
                $HerbNummerAlternative = substr($HerbNummer, 0, 4) . '-' . substr($HerbNummer, 4);

                // Find specimen entry for this file
                $sidSth = $db->prepare("
                    SELECT s.`specimen_ID`
                    FROM `tbl_specimens` s
                    LEFT JOIN `tbl_management_collections` mc ON mc.`collectionID` = s.`collectionID`
                    WHERE
                    ( s.`HerbNummer` = :HerbNummer OR s.`HerbNummer` = :HerbNummerAlternative )
                    AND
                    mc.`coll_short_prj` = :coll_short_prj
                    ");

                $sidSth->execute(array(
                    ':HerbNummer' => $HerbNummer,
                    ':HerbNummerAlternative' => $HerbNummerAlternative,
                    ':coll_short_prj' => $coll_short_prj
                ));

                // Fetch the results
                $rows = $sidSth->fetchAll();
                if( count($rows) > 0 ) {
                    $specimen_ID = $rows[0]['specimen_ID'];
                }
            }

            // If we found a fitting specimen ID, update database
            if( $specimen_ID != null ) {
                $dfuSth = $dbPictures->prepare("UPDATE `djatoka_files` SET `specimen_ID` = :specimen_ID WHERE `ID` = :ID");
                $dfuSth->execute(array(
                    ':specimen_ID' => $specimen_ID,
                    ':ID' => $ID
                ));
            }
        }

        // inDBchecked_butnotinArchive
        $fields = "
 sp.specimen_ID,
 sp.filename
";

        $sql = "
SELECT
 :fields
FROM
 herbarinput.tbl_specimens sp  -- herbar_view.view_tbl_specimens sp -- too slow!!!
 LEFT JOIN herbar_pictures.djatoka_files dj ON ( dj.filename=sp.filename and dj.scan_id='{$scan_id}')
";
        if ($family) {
            $sql.="LEFT JOIN tbl_tax_species ts ON ts.taxonID = sp.taxonID
 LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
 LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID
";
        }

        if ($source_id) {
            $sql.="
 LEFT JOIN herbarinput.tbl_management_collections mc ON mc.collectionID=sp.collectionID
";
        }

        $sql.="
WHERE
  sp.filename IS NOT NULL
 AND sp.filename not LIKE 'error%'
 AND digital_image != 0
 AND dj.ID is null

";
        if ($source_id) {
            $sql.= " AND mc.source_id = " . $db->quote($source_id) . "";
        }
        if ($family) {
            $sql .= " AND tf.family LIKE " . $db->quote($family . '%');
        }
        // count
        $dbst = $db->query(str_replace(':fields', ' COUNT(*)', $sql));
        $c[0] = $dbst->fetchColumn();


        $sql = str_replace(':fields', $fields, $sql) . " {$limit}";

        $dbst = $db->query($sql);
        foreach ($dbst as $row) {
            $value = htmlspecialchars($row['filename']);
            if (!empty($row['specimen_ID'])) {
                $specLink = " href=\"javascript:editSpecimens('<{$row['specimen_ID']}>')>\"";
            }
            else {
                $specLink = "";
            }
            $tab1.=<<<EOF
<a href="javascript:editSpecimens('<{$row['specimen_ID']}>')">{$value}</a><br>
EOF;
        }
        $tab1.=<<<EOF
</td><td width='20'>&nbsp;</td><td>
EOF;
        // inArchive_butnotinDB
        // filter with prefix (e.g. wu_) at institution
        $dbsth = $dbPictures->query("SELECT count(*) FROM `djatoka_files` WHERE `specimen_ID` IS NULL AND `scan_id` = $scan_id");
        $c[1] = $dbsth->fetchColumn();
        $dbsth = $dbPictures->query("SELECT `filename`, `inconsistency`, `faulty` FROM `djatoka_files` WHERE `specimen_ID` IS NULL AND `scan_id` = $scan_id ORDER BY `filename` {$limit}");
        foreach ($dbsth as $row) {
            $val = htmlspecialchars($row['filename']);
            $inc = '';
            if ($row['inconsistency'] != 0) {
                if ($row['inconsistency'] == 1) {
                    $inc = " (pic not in djatoka)";
                }
                else if ($row['inconsistency'] == 2) {
                    $inc = " (pic not in archive!!!)";
                }
            }
            if( $row['faulty'] != 0 ) {
                $inc .= " (faulty)";
            }
            $specLink = " href=\"javascript:editSpecimensSimple('{$row['filename']}')\"";

            $tab1.=<<<EOF
<a{$specLink}">{$val}{$inc}</a><br>
EOF;
        }

        $tab1.=<<<EOF
</td><td width='20'>&nbsp;</td><td>
EOF;


        // inArchive_butnotCheckedinDB
        $fields = "
 dj.filename,
 sp.specimen_ID,
 dj.inconsistency
";
        $sql = "
SELECT
 :fields
FROM
 herbar_pictures.djatoka_files dj
 -- LEFT JOIN  herbar_view.view_tbl_specimens sp ON (sp.filename=dj.filename)-- too slow!!!
 LEFT JOIN herbarinput.tbl_specimens sp ON (sp.filename=dj.filename)
 LEFT JOIN herbarinput.tbl_management_collections mc ON mc.collectionID=sp.collectionID
 LEFT JOIN herbarinput.tbl_img_definition img ON img.source_id_fk=mc.source_id
";
        if ($family) {
            $sql.="LEFT JOIN tbl_tax_species ts ON ts.taxonID = sp.taxonID
 LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
 LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID
";
        }
        $sql.="
WHERE
 dj.scan_id='{$scan_id}'
 and sp.specimen_ID is not null
 and sp.digital_image = 0
 {$where}
";
        if ($source_id) {
            $sql.= " AND mc.source_id = $source_id";
        }

        if ($family) {
            $sql .= " AND tf.family LIKE " . $db->quote($family . '%');
        }

        // count
        $dbst = $db->query(str_replace(':fields', ' COUNT(*)', $sql));
        $c[2] = $dbst->fetchColumn();

        $sql = str_replace(':fields', $fields, $sql) . "  order by dj.filename  {$limit}";
        $dbst = $db->query($sql);
        foreach ($dbst as $row) {
            $val = htmlspecialchars($row['filename']);
            $inc = '';
            if ($row['inconsistency'] != 0) {
                if ($row['inconsistency'] == 1) {
                    $inc = " (pic not in djatoka)";
                }
                else if ($row['inconsistency'] == 2) {
                    $inc = " (pic not in archive!!!)";
                }
            }
            if (!empty($row['specimen_ID'])) {
                $specLink = " href=\"javascript:editSpecimens('<{$row['specimen_ID']}>')\"";
            }
            else {
                $specLink = "";
            }
            $tab1.=<<<EOF
<a{$specLink}>{$row['filename']} {$inc}</a><br>
EOF;
        }


        $tab1.=<<<EOF
</td><td width='20'>&nbsp;</td><td>
EOF;



        if ($faulty) {
            // herbanumber_Fault
            $fields = "
 sp.specimen_ID,
 sp.herbNummer
";
            $sql = "
SELECT
 :fields
FROM
 herbarinput.tbl_specimens sp  -- herbar_view.view_tbl_specimens sp-- too slow!!!
 LEFT JOIN herbarinput.tbl_management_collections mc ON mc.collectionID=sp.collectionID
 LEFT JOIN herbarinput.tbl_img_definition img ON img.source_id_fk=mc.source_id
";

            if ($family) {
                $sql.="LEFT JOIN tbl_tax_species ts ON ts.taxonID = sp.taxonID
 LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
 LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID
";
            }

            $sql.="
WHERE
 img.imgserver_IP = " . $db->quote($serverIP) . "
 and sp.digital_image != 0
 and sp.filename LIKE 'error%'
";
            if ($source_id) {
                $sql.= " AND mc.source_id = '{$source_id}'";
            }
            if ($family) {
                $sql .= " AND tf.family LIKE " . $db->quote($family . '%');
            }
            // count
            $dbst = $db->query(str_replace(':fields', ' COUNT(*)', $sql));
            $c[3] = $dbst->fetchColumn();

            $sql = str_replace(':fields', $fields, $sql) . "  order by sp.filename {$limit}";
            //echo $sql;
            $dbst = $db->query($sql);
            foreach ($dbst as $row) {
                $val = htmlspecialchars($row['herbNummer']);
                if (!empty($row['specimen_ID'])) {
                    $specLink = " href=\"javascript:editSpecimens('<{$row['specimen_ID']}>')\"";
                }
                else {
                    $specLink = "";
                }
                $tab1.=<<<EOF
<a{$specLink}>{$val}</a><br>
EOF;
            }
        }
        else {
            $tab1.=<<<EOF
<a>Not processed.</a><br>
EOF;
        }


        $maxc = max($c[0], $c[1], $c[2], $c[3]);

        $tab1 = <<<EOF
<table>
<tr>
<th>{$c[0]} missing Pics</th><th></th>
<th>{$c[1]} missing database entries</th><th></th>
<th>{$c[2]} missing database checks</th><th></th>
<th>{$c[3]} faulty database entries</th><th></th>
</tr>
<tr>
 <td>
 {$tab1}
 </td>
</tr>
</table>
EOF;
        return array('html' => $tab1, 'maxc' => $maxc);
    }

    public function x_djatoka_consistency_check($params) {
        $db = $this->getdB();
        $serverIP = $params['serverIP'];
        $scan_id = $this->getLatestScanId($serverIP);

        $start = isset($params['page_index']) ? intval($params['page_index']) : 0;
        $limit = isset($params['limit']) ? intval($params['limit']) : 20;

        $start = $start * $limit;

        $limit = " LIMIT {$start},{$limit}";

        if ($scan_id == 0) {
            return "A Scan is already running. refresh in a few seconds.";
        }
        $c = array(0, 0);
        $tab1 = '';




        // Consistency: In Djatoka Not in Archive
        $fields = "
 dj.filename,
 sp.specimen_ID
";
        $sql = "
SELECT
 :fields
FROM
 herbar_pictures.djatoka_files dj
 -- LEFT JOIN  herbar_view.view_tbl_specimens sp ON (sp.filename=dj.filename)-- too slow!!!
 LEFT JOIN  herbarinput.tbl_specimens sp ON (sp.filename=dj.filename) 
WHERE
 dj.scan_id='{$scan_id}'
 and dj.inconsistency ='2'
";


        // count
        $dbst = $db->query(str_replace(':fields', ' COUNT(*)', $sql));
        $c[0] = $dbst->fetchColumn();

        $sql = str_replace(':fields', $fields, $sql) . " order by dj.filename {$limit} ";
        $dbst = $db->query($sql);


        foreach ($dbst as $row) {
            if (!empty($row['specimen_ID'])) {
                $specLink = " href=\"javascript:editSpecimens('<{$row['specimen_ID']}>')\"";
            }
            else {
                $specLink = "";
            }
            $tab1.=<<<EOF
<a{$specLink}>{$row['filename']}</a><br>
EOF;
        }

        $tab1.=<<<EOF
</td><td width='20'>&nbsp;</td><td>
EOF;
        // Consistency: in Archive NOT in Djatoka
        $fields = "
 dj.filename,
 sp.specimen_ID
";
        $sql = "
SELECT
 :fields
FROM
 herbar_pictures.djatoka_files dj
 -- LEFT JOIN  herbar_view.view_tbl_specimens sp ON (sp.filename=dj.filename) -- too slow!!!
 LEFT JOIN  herbarinput.tbl_specimens sp ON (sp.filename=dj.filename)
 
WHERE
 dj.scan_id='{$scan_id}'
 and dj.inconsistency ='1'
";
        // count
        $dbst = $db->query(str_replace(':fields', ' COUNT(*)', $sql));
        $c[1] = $dbst->fetchColumn();

        $sql = str_replace(':fields', $fields, $sql) . " order by dj.filename {$limit} ";
        $dbst = $db->query($sql);
        foreach ($dbst as $row) {
            if (!empty($row['specimen_ID'])) {
                $specLink = " href=\"javascript:editSpecimens('<{$row['specimen_ID']}>')\"";
            }
            else {
                $specLink = "";
            }
            $tab1.=<<<EOF
<a{$specLink}>{$row['filename']}</a><br>
EOF;
        }

        $maxc = max($c[0], $c[1]);

        $tab1 = <<<EOF
<table>
<tr>
<th>{$c[0]} In Djatoka, but not in Archive</th><th></th>
<th>{$c[1]} In Archive, but not in Djatoka</th><th></th>
</tr>
<tr>
 <td>
 $tab1
 </td>
</tr>
</table>
EOF;



        return array('html' => $tab1, 'maxc' => $maxc);

        return $tab1;
    }

    public function getLatestScanId($serverIP) {
        $db = $this->getdB();
        $serverIPd = $db->quote($serverIP);

        $dbst2 = $db->query("SELECT max(scan_id) FROM herbar_pictures.djatoka_scans WHERE finish IS NOT NULL AND errors is null and IP ={$serverIPd}");
        $scan_id = false;
        if (($row = $dbst2->fetchColumn()) > 0) {
            $scan_id = $row;
        }

        // If there are any jobs within 600s => wait for it.
        $dbst = $db->query("SELECT count(scan_id) FROM herbar_pictures.djatoka_scans WHERE TIME_TO_SEC(TIMEDIFF(NOW(), start)) < 600 AND finish IS NULL AND IP ={$serverIPd}");

        if ($dbst->fetchColumn() > 0) {
            $this->info = "A Scan is already running on '{$serverIP}', please refresh after a while.";
        }

        return $scan_id;
    }

    //scan_id 	thread_id 	IP 	start 	finish 	errors
    public function x_importDjatokaListIntoDB($params) {
        $warningText = "";
        $serverIP = $params['serverIP'];
        
        // Fetch reference to picture db
        $db_pictures = $this->getDbPictures();
        $serverIPd = $db_pictures->quote($serverIP);

        // all jobs older than 600s will be marked as error
        $dbst = $db_pictures->query("SELECT count(scan_id) FROM djatoka_scans WHERE TIME_TO_SEC(TIMEDIFF(NOW(), start)) > 600 AND finish IS NULL AND IP = {$serverIPd}");
        if ($dbst->fetchColumn() > 0) {
            $db_pictures->query("UPDATE djatoka_scans SET finish = NOW(), errors = 'script terminated, entry corrected' WHERE finish IS NULL AND IP = {$serverIPd}");
        }

        // If there are any jobs within 600s => wait for it.
        $dbst = $db_pictures->query("SELECT count(scan_id) FROM djatoka_scans WHERE TIME_TO_SEC(TIMEDIFF(NOW(), start)) < 600 AND finish IS NULL AND IP = {$serverIPd}");

        if ($dbst->fetchColumn() > 0) {
            throw new Exception("A Scan is already running on '{$serverIP}'. refresh in a few seconds.");
        }
        // Begin
        // mark the beginning
        $db_pictures->query("INSERT INTO djatoka_scans SET IP ={$serverIPd}, start = NOW()");
        $scanid = $db_pictures->lastInsertId();

        ignore_user_abort(true);
        set_time_limit(0);

        $service = &$this->getService($serverIP);

        $filesArchive = $service->listArchiveImages();
        $filesDjatoka = $service->listDjatokaImages();

        if ($filesArchive == -1 || $filesDjatoka == -1) {
            throw new Exception("Key not accepted {$serverIP}");
        }
        
        // check if the server has no archive
        if( count($filesArchive) == 0 && count($filesDjatoka) > 0 ) {
            $filesArchive = $filesDjatoka;
            $warningText = "<br />WARNING: No files in Archive, assuming non Archive installation!";
        }

        $inArchive_notinDjatoka = array_diff($filesArchive, $filesDjatoka);

        $inArchive_notinDjatoka = array_flip($inArchive_notinDjatoka);
        
        $x = 0;
        // inconsistency: 0=> no errors, 1: not in djatoka, 2 not in archive
        $sql = "INSERT INTO djatoka_files (scan_id,filename,inconsistency) VALUES ";
        foreach ($filesArchive as $filename) {
            $inconsistency = (isset($inArchive_notinDjatoka[$filename])) ? 1 : 0;
            $sql.="\n('{$scanid}'," . $db_pictures->quote($filename) . ",'{$inconsistency}'),";
            $x++;
        }

        $inDjatoko_notinArchive = array_diff($filesDjatoka, $filesArchive);
        foreach ($inDjatoko_notinArchive as $filename) {
            $sql.="\n('{$scanid}'," . $db_pictures->quote($filename) . ",'2'),";
            $x++;
        }

        $sql = substr($sql, 0, -1);
        if ($x > 0) {
            $db_pictures->query($sql);
        }

        $db_pictures->query("UPDATE djatoka_scans SET finish = NOW() WHERE scan_id={$scanid}");

        // view is much too slow for working...
        /*$db->query("
UPDATE
 herbarinput.tbl_specimens sp
 LEFT JOIN  herbarinput.tbl_management_collections mc ON mc.collectionID=sp.collectionID
SET
 sp.filename=(
  CASE
   WHEN sp.HerbNummer IS NULL THEN ''
   WHEN (NOT ( REPLACE(sp.HerbNummer, '-', '') REGEXP '^[0-9]{0,}(a{0,1}|b{0,1}|c{0,1})$' ) OR ( LOCATE('-',sp.HerbNummer)<>0 AND SUBSTRING_INDEX(sp.HerbNummer, '-', 2)<>sp.HerbNummer ) ) THEN 'error_fomat'
   WHEN LOCATE('-',sp.HerbNummer) THEN CONCAT(mc.coll_short_prj,'_',REPLACE(sp.HerbNummer,'-','')) 
   WHEN sp.collectionID=89 THEN IF( CHAR_LENGTH(sp.HerbNummer)>8,'error_JE', CONCAT(mc.coll_short_prj,'_',RIGHT(CONCAT('00000000',sp.HerbNummer),8)))
   WHEN mc.source_id=4 THEN IF( CHAR_LENGTH(sp.HerbNummer)>9,'error_W', CONCAT(mc.coll_short_prj,'_',RIGHT(CONCAT('000000000',sp.HerbNummer),9)))
   ELSE IF( CHAR_LENGTH(sp.HerbNummer)>7,'error_7', CONCAT(mc.coll_short_prj,'_',RIGHT(CONCAT('00000000',sp.HerbNummer),7)))
  END
 )
");

        set_time_limit(ini_get('max_execution_time'));*/

        return "List fetched from Server {$serverIP} and dumped into local Database.<br> The ScanId was: {$scanid}!" . $warningText;
    }
    
    /**
     * Force import of an entry
     * @param array $params
     * @return string 
     */
    function x_forceImport($params) {
        $serverIP = $params['serverIP'];
        $identifier = $params['identifier'];
        
        // Fetch reference to service
        $service = &$this->getService($serverIP);
        $retVal = $service->forceImport($identifier);
        
        // Just return the service response
        return $retVal;
    }
    
    /**
     * List all images stored on a djatoka server
     * @param array $params
     * @return string HTML of filelist
     */
    function x_listImages($params) {
        $serverIP = $params['serverIP'];
        $start = isset($params['page_index']) ? intval($params['page_index']) : 0;
        $limit = isset($params['limit']) ? intval($params['limit']) : 20;
        
        // Fetch latest scan
        $db_picture = $this->getDbPictures();
        $sth = $db_picture->prepare("SELECT SQL_CALC_FOUND_ROWS `filename` FROM `djatoka_files` WHERE `scan_id` = (SELECT `scan_id` FROM `djatoka_scans` WHERE `IP` = :IP ORDER BY `finish` DESC LIMIT 1) ORDER BY `filename` ASC LIMIT $start, $limit");
        $sth->execute(array( ':IP' => $serverIP ));
        $rows = $sth->fetchAll();
        
        // Prepare HTML header
        $retVal = "
            <table>
                <tr>
                    <th>Identifier</th>
                </tr>
            ";
        
        // add entry for each found filename
        foreach( $rows as $row ) {
            $retVal .= "
                <tr>
                    <td>" . $row['filename'] . "</td>
                </tr>
                ";
        }
        
        // html footer
        $retVal .= "
            </table>
            ";
        
        // Fetch found rows
        $sth = $db_picture->query("SELECT FOUND_ROWS() AS 'file_count'");
        $row = $sth->fetch();
        $file_count = $row['file_count'];
        
        // Return results to callee
        return array( 'html' => $retVal, 'maxc' => $file_count );
    }
}
