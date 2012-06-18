<?php

//$_POST=$_GET;
ob_start();  // intercept all output


require_once('../inc/jsonRPCClient.php');
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
            'error' => $e->getMessage() . '<br />' . var_export($e->getTrace(), true)
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
            $this->service = new jsonRPCClient('http://' . $this->getServerUrl($serverIP) . '/jacq-servlet/ImageServer');
        }
        return $this->service;
    }

    public function getServerKey($serverIP) {
        $db = $this->getdB();
        $dbst = $db->query("SELECT `key`, `is_djatoka` FROM `tbl_img_definition` WHERE `imgserver_IP` = " . $db->quote($serverIP) );
        $row = $dbst->fetch();

        if (!$row) {
            throw new Exception("No valid IP: {$serverIP}");
        }
        if ($row['is_djatoka'] == 0) {
            throw new Exception("No Djatoka Server Configured for  IP: {$serverIP}");
        }
        $this->sharedkey = $row['key'];
        if ($this->sharedkey == '') {
            throw new Exception("No shared KeyConfigured for  IP {$serverIP}");
        }
    }
    
    /**
     * Return the complete server URL for a given IP
     * @param string $serverIP IP / Address of server
     * @return string URL of server
     * @throws Exception 
     */
    public function getServerUrl($serverIP) {
        $db = $this->getdB();
        $dbst = $db->query("SELECT `imgserver_IP`, `img_service_directory` FROM `tbl_img_definition` WHERE `imgserver_IP` = " . $db->quote($serverIP) );
        $row = $dbst->fetch();
        
        if( !$row ) {
            throw new Exception("No valid IP: {$serverIP}");
        }
        
        // Return complete URL
        return $row['imgserver_IP'] . $row['img_service_directory'];
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

        $dbst2 = $db->query("SELECT  finish FROM herbar_pictures.djatoka_scans WHERE finish IS NOT NULL AND errors is null and IP ={$serverIPd} LIMIT 1");
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

        $result = $service->importImages($serverIP, $this->sharedkey);

        if ($result == 1) {
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
            $result.="<a href=\"javascript:processItem()\">[". $logmsg['logtime'] . "] [" . $logmsg['identifier'] . "] " . $logmsg['message'] . "</a><br>";
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
        $ImportThreads = $service->listImportThreads($timestamp);

        $maxc = count($ImportThreads);

        $result = "";
        $x = 0;
        foreach ($ImportThreads as $threadid => $timestamp) {
            $x++;
            if ($x <= $start)
                continue;
            $d = date('d.m.Y H:i', $timestamp);
            $result.="<a href=\"javascript:loadImportLog('{$threadid}','{$d}')\">{$threadid},{$d}</a><br>";
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
        $where = "";
        if ($source_id) {
            $dbst = $db->query( "SELECT `coll_short_prj` FROM `tbl_management_collections` WHERE `source_id` = " . $db->quote($source_id) . " GROUP BY `coll_short_prj`" );
            $rows = $dbst->fetchAll();
            
            $where = " AND ( dj.`filename` IS NULL";
            foreach( $rows as $row ) {
                $where .= " OR dj.`filename` LIKE '" . $row['coll_short_prj'] . "_%'";
            }
            $where .= ")";
        }

        $fields = "
 dj.filename,
 dj.inconsistency,
 sp.specimen_ID		
		";

        $sql = "
SELECT
 :fields
FROM
 herbar_pictures.djatoka_files dj
  -- LEFT JOIN  herbar_view.view_tbl_specimens sp ON (sp.filename=dj.filename)-- too slow!!!
 LEFT JOIN herbarinput.tbl_specimens sp ON (sp.filename=dj.filename)
WHERE
 dj.scan_id='{$scan_id}'
 and sp.specimen_ID is null
{$where}
";
        // count
        $dbst = $db->query(str_replace(':fields', ' COUNT(*)', $sql));
        $c[1] = $dbst->fetchColumn();

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

        return "List fetched from Server {$serverIP} and dumped into local Database.<br> The ScanId was: {$scanid}";
    }

}
