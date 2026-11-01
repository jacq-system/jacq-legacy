<?PHP

namespace Jacq;

use Exception;
use Jacq\Services\Log;

class MapLines_editCommonName extends MapLines
{
    public int $pagination = 5;
    public int $limit;
    public int $block_limit;


    /**
     * Removes a map line between two elements identified by their IDs.
     *
     * @param array $params An associative array containing the following keys:
     *                      - 'leftID' (int|string): The ID of the first element.
     *                      - 'rightID' (int|string): The ID of the second element.
     * @return array An associative array with the key 'success' indicating the result of the operation.
     */
	function RemoveMapLine(array $params): array
    {
        try {
            $db = DbAccess::ConnectTo('INPUT');

            Log::tbl_name_names_equals($params['leftID'],$params['rightID'],2);
	    	$res = $db->query("DELETE FROM herbar_names.tbl_name_names_equals 
                               WHERE tbl_name_names_name_id = '" . $db->real_escape_string($params['leftID']) . "' 
                                AND tbl_name_names_name_id1='" . $db->real_escape_string($params['rightID']) . "' 
                               LIMIT 1");
        } catch (Exception $e) {
            $res = false;
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }

		return array('success' => $res);
	}


    /**
     * Loads map lines and retrieves search results based on various input parameters.
     * This method performs database queries to fetch information about names, common names, and transliterations.
     * It supports MDLD and MySQL-based search logic to refine the results.
     *
     * @param array $params An associative array of input parameters:
     *                      - 'cid' (string): The identifier for the target name.
     *                      - 'pageIndex' (int): The index of the page for pagination purposes.
     *                      - 'mdldSearch' (string): Optional MDLD-based search input.
     *                      - 'mysqlSearch' (string): Optional MySQL-based search input for name or transliteration.
     *
     * @return array An associative array containing:
     *               - 'cf' (int): The count of filtered results based on the search criteria.
     *               - 'ca' (int): The total count of available results without filters.
     *               - 'syns' (array): An array of search result entries, where each entry is an array with:
     *                                - Empty string (string): Placeholder.
     *                                - Zero (int): Placeholder.
     *                                - 'id' (string): The identifier of the name.
     *                                - 'name' (string): The common name along with transliteration (if available).
     */
	function LoadMapLines(array $params): array
    {
		$cid = intval($params['cid']);
//		$page=$params['pageIndex'];
//		$pbegin=$page*$this->pagination;

        try {
            $db = DbAccess::ConnectTo('INPUT');

		    $countAllSearch = " (    eq.tbl_name_names_name_id = {$cid}
                                  OR eq.tbl_name_names_name_id1 = {$cid}
                                ) ";

            //mdld => Service! auslagern.
            if (isset($params['mdldSearch']) && strlen($params['mdldSearch']) > 0) {
                // MDLD Search for commonnames.
                $this->limit = 4;
                $this->block_limit = 2;

                $uninomial = $params['mdldSearch'];
                $lenUninomial = mb_strlen(trim($uninomial));
                $lenlim = min($lenUninomial / 2, $this->limit);
                $uninomial = strtolower($uninomial);

//			    $sqlMDLD="
//SELECT
// com.common_id as 'i'
//FROM
// herbar_names.tbl_name_names nam
// LEFT JOIN herbar_names.tbl_name_commons com ON com.common_id = nam.name_id
// LEFT JOIN herbar_names.tbl_name_transliterations translit ON translit.transliteration_id = nam.transliteration_id
//WHERE
//(
//     mdld('{$uninomial}',com.common_name, {$this->block_limit}, {$this->limit}) <  LEAST(CHAR_LENGTH(com.common_name)/2,{$lenlim})
//  OR mdld('{$uninomial}',translit.name, {$this->block_limit}, {$this->limit}) <  LEAST(CHAR_LENGTH(translit.name)/2,{$lenlim})
//)
//LIMIT 1000
//";
                $config  = Settings::Load();
			    $service = InternMDLDService::Load($config->get('internMDLDService', 'url'), $config->get('internMDLDService', 'password'));

//				$res1 = $service->getSQLResults($sqlMDLD);  // unsafe, do not use
                $res1 = $service->getMDLDcommonnames($uninomial, $lenlim);
                $lookup = '';
                if (isset($res1['error'])) {
                    echo $res1['error'];
                } else {
                    if (count($res1) > 0) {
                        foreach ($res1 as $row) {
                            $lookup .= ",'{$row['i']}'";
                        }
                    }
                    $lookup = $db->real_escape_string(substr($lookup,1));
                }
                $whereSearch = "   ( com0.common_id IN ($lookup) AND eq.tbl_name_names_name_id1 = $cid)
                                OR ( com1.common_id IN ($lookup) AND eq.tbl_name_names_name_id = $cid) ";
            } else if (isset($params['mysqlSearch']) && strlen($params['mysqlSearch']) > 0) {
                $search = "'" . $db->real_escape_string("%" . $params['mysqlSearch']) . "%'";
                $whereSearch = " (     (   com0.common_name LIKE $search 
                                        OR translit0.name LIKE $search )
                                   AND eq.tbl_name_names_name_id1 = $cid
                                 )
                                 OR
                                 (
                                       (   com1.common_name LIKE $search 
                                        OR translit1.name LIKE $search )
                                   AND eq.tbl_name_names_name_id = $cid
                                 )
";
            } else {
                $whereSearch = $countAllSearch;
            }

            $tables = " herbar_names.tbl_name_names_equals eq
                       LEFT JOIN herbar_names.tbl_name_names nam0 ON nam0.name_id = eq.tbl_name_names_name_id
                       LEFT JOIN herbar_names.tbl_name_commons com0 ON com0.common_id = nam0.name_id
                       LEFT JOIN herbar_names.tbl_name_transliterations translit0 ON translit0.transliteration_id = nam0.transliteration_id
                      
                       LEFT JOIN herbar_names.tbl_name_names nam1 ON nam1.name_id = eq.tbl_name_names_name_id1
                       LEFT JOIN herbar_names.tbl_name_commons com1 ON com1.common_id = nam1.name_id
                       LEFT JOIN herbar_names.tbl_name_transliterations translit1 ON translit1.transliteration_id = nam1.transliteration_id ";

		    $sqlSearch = "SELECT IF(eq.tbl_name_names_name_id = $cid, eq.tbl_name_names_name_id1, eq.tbl_name_names_name_id) as 'id',
                                 IF(eq.tbl_name_names_name_id = $cid, com1.common_name, com0.common_name) as 'name',
                                 IF(eq.tbl_name_names_name_id = $cid, translit1.name, translit0.name) as 'translit'

                          FROM {$tables}
                          WHERE {$whereSearch}
                          ORDER BY name, translit ";

		// get count of results
		    $sqlcountsearch = " SELECT COUNT(*) as 'c'
                                FROM {$tables}
                                WHERE $whereSearch ";

            // get count of results
            $sqlcountall = " SELECT COUNT(*) as 'c'
                             FROM {$tables}
                             WHERE $countAllSearch ";


            $resdb = $db->query($sqlcountsearch);
            if ($resdb) {
                $row = $resdb->fetch_assoc();
                if (isset($row['c'])) {
                    $c1 = $row['c'];
                }
            }

            $resdb = $db->query($sqlcountall);
            if($resdb){
                $row = $resdb->fetch_assoc();
                if(isset($row['c'])){
                    $c2 = $row['c'];
                }
            }

            $resdb=$db->query($sqlSearch);
            if($resdb){
                while($row = $resdb->fetch_assoc()){
                    $res[] = array('', 0, $row['id'], "{$row['name']}   ({$row['translit']})");
                }
            }
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }

		return array('cf' => $c1 ?? 0, 'ca' => $c2 ?? 0, 'syns' => $res ?? []);
	}


    /**
     * Saves the mapping of line connections and updates the database accordingly.
     * This method processes new map line data, checks for their existence in the database,
     * and either inserts the data or logs errors for unsuccessful operations.
     *
     * @param array $params An associative array of input parameters:
     *                      - 'cid' (string): The identifier for the target name to which connections are being mapped.
     *
     * @return array An associative array containing:
     *               - 'success' (int): Status of the operation (1 if successful, 0 otherwise).
     *               - 'error' (array): An array of unsuccessful mappings, with each entry containing:
     *                                   - Input data (mixed): Data related to the unsuccessful mapping.
     *                                   - 'id1' (string): The identifier of the first name.
     *                                   - 'id2' (string): The identifier of the second name.
     *                                   - Error description (string|int): Error message or code.
     *               - 'successx' (array): An array of successful mappings, with each*/
	function SaveMapLines(array $params): array
    {
		$new = $this->getMapLines($params,1,1);
        $notdone = array();
        $successx = array();

        try {
            $db = DbAccess::ConnectTo('INPUT');

            $id2 = intval($params['cid']);

            foreach ($new as $id1 => $x){
                if ($id1 == 'error'){
                    foreach ($x as $err){
                        $notdone[] = array($err[0], $err[1], $err[1], 'Not an ID.');
                    }
                    continue;
                }
                // If not in database yet, add it
                $result2 = $db->query("SELECT COUNT(*) AS 'c' 
                                       FROM herbar_names.tbl_name_names_equals 
                                       WHERE (   (tbl_name_names_name_id = '$id1' AND tbl_name_names_name_id1 = '$id2')
                                              OR (tbl_name_names_name_id = '$id2' AND tbl_name_names_name_id1 = '$id1')
                                             )
                                       LIMIT 1");
                if ($result2){
                    $row2 = $result2->fetch_array();
                    if ($row2['c'] == 0){
                        $result2 = $db->query("INSERT INTO herbar_names.tbl_name_names_equals SET
                                                tbl_name_names_name_id = '$id1', 
                                                tbl_name_names_name_id1 = '$id2' ");
                        if ($result2){
                            Log::tbl_name_names_equals($id1, $id2, 0);
                            $successx[] = array($x, $id1, $id2);
                        } else {
                            $notdone[] = array($x, $id1, $id2, $db->error);
                        }
                    } else {
                        $existed = (isset($row2['c']) && $row2['c'] > 0);
                        $notdone[] = array($x, $id1, $id2, $existed ? 1 : 'unknown');
                    }
                } else {
                    $notdone[] = array($x, $id1, $id2, $db->error);
                }
            }
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }

		if (count($notdone) > 0) {
			$res = array('success' => 0, 'error' => $notdone, 'successx' => $successx);
		} elseif (count($successx) > 0) {
			$res = array('success'=>1, 'successx' => $successx);
		} else {
			$res = array('success' => 0);
		}

		return $res;
	}

}
