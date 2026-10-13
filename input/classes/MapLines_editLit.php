<?PHP

namespace Jacq;

use Exception;

class MapLines_editLit extends MapLines
{
	public int $pagination = 10;

    /**
     * Removes a map line by deleting an entry from the tax synonymy table, provided that the
     * entry is not being used in the classification table.
     *
     * @param array $params An associative array of parameters, including:
     *                      - 'citationID': int The ID of the source citation.
     *                      - 'leftID': int The taxon ID.
     *                      - 'rightID': int The accepted taxon ID. Use 0 if no accepted taxon ID is specified.
     *
     * @return array An array containing:
     *               - 'success': int Status of the operation (0 = fail, 1 = success, 2 = classification dependency).
     *               - 'text': string Additional information or error messages.
     */
    public function RemoveMapLine(array $params): array
    {
        $citid = intval($params['citationID']);
        $taxonID = intval($params['leftID']);
        $acc_taxon_ID = intval($params['rightID']);
        $res = 0;
        $msg = "";

        try {
            $db = DbAccess::ConnectTo('INPUT');

            $sql2 = "SELECT `tax_syn_ID` 
                     FROM `herbarinput`.`tbl_tax_synonymy` 
                     WHERE `source_citationID` = $citid 
                       AND `source` = 'literature' 
                       AND `taxonID` = $taxonID";
            // if no accepted taxon ID is given, we have to check for NULL value
            if ($acc_taxon_ID == 0) {
                $sql2 .= " AND `acc_taxon_ID` IS NULL";
            }
            // otherwise search for the accepted taxon
            else {
                $sql2 .= " AND `acc_taxon_ID` = $acc_taxon_ID";
            }

            // check if we find a fitting entry
            $result2 = $db->query($sql2);
            if ($result2 && $row2 = $result2->fetch_array()) {
                $tax_syn_ID = $row2['tax_syn_ID'];

                // check if synonymy entry is still used in classification
                $classification_res = $db->query("SELECT `classification_id` 
                                                  FROM `herbarinput`.`tbl_tax_classification` 
                                                  WHERE `tax_syn_ID` = $tax_syn_ID");
                if( $classification_res && $classification_res->num_rows > 0 ) {
                    $res = 2;
                    $msg = "Delete from Classification first!";
                }
                // if not, delete entry
                else {
                    Log::tbl_tax_synonymy($tax_syn_ID, 2);
                    $res3 = $db->query("DELETE FROM `herbarinput`.`tbl_tax_synonymy` WHERE `tax_syn_ID` = $tax_syn_ID");
                    if ($res3) {
                        $res = 1;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }

        // Return success status
        return array('success' => $res, 'text' => $msg);
    }


    /**
     * Loads map lines data based on provided search parameters, including citation ID, species search, genus search,
     * and other filters. This method constructs and executes queries to retrieve the required data from the database.
     *
     * @param array $params An associative array of parameters used to filter the map lines. Keys include:
     *                      - citationID: The citation ID used as a primary filter.
     *                      - pageIndex: The index of the pagination page.
     *                      - speciesSearch: A string containing the species search term, optionally combined
     *                        with genus information.
     *                      - genusSearch: A string containing the genus search term.
     *                      - mdldSearch: A string containing a taxonomic search term processed through a
     *                        matching service.
     * @return array The result set of map lines data retrieved from the database, structured based on the query inputs.
     */
    public function LoadMapLines(array $params): array
    {
		$citid  = intval($params['citationID']);
		$pbegin = intval($params['pageIndex']) * $this->pagination;

        try {
            $db = DbAccess::ConnectTo('INPUT');

	    	$where2 = "";

            // species (and optionally genus)
            if (isset($params['speciesSearch']) && strlen($params['speciesSearch']) > 0) {
                $pieces = explode(chr(194) . chr(183), trim($this->removeID($params['speciesSearch'])));
                $v = explode(" ", $pieces[0]);
                if (strlen($params['genusSearch']) > 0 && !isset($v[1])) {
                    $spec = $db->real_escape_string($pieces[0]);
                    $pieces = explode(chr(194) . chr(183), trim($this->removeID($params['genusSearch'])));
                    $gen = $db->real_escape_string($pieces[0]);
                } else {
                    $spec = $db->real_escape_string($v[1]);
                    $gen  = $db->real_escape_string($v[0]);
                }

                $where2 = " AND (   te01.epithet LIKE '$spec%'
                                 OR te11.epithet LIKE '$spec%'
                                 OR te21.epithet LIKE '$spec%'
                                 OR te31.epithet LIKE '$spec%'
                                 OR te41.epithet LIKE '$spec%'
                                 OR te51.epithet LIKE '$spec%'
                              
                                 OR te02.epithet LIKE '$spec%'
                                 OR te12.epithet LIKE '$spec%'
                                 OR te22.epithet LIKE '$spec%'
                                 OR te32.epithet LIKE '$spec%'
                                 OR te42.epithet LIKE '$spec%'
                                 OR te52.epithet LIKE '$spec%'
                                )
                            AND (   tg1.genus LIKE '$gen%'
                                 OR tg2.genus LIKE '$gen%'
                                ) ";
            // genus only
            } elseif (isset($params['genusSearch']) && strlen($params['genusSearch']) > 0) {
                $pieces = explode(chr(194) . chr(183), trim($this->removeID($params['genusSearch'])));
                $gen = $db->real_escape_string($pieces[0]);
                $where2 = " AND (
                                  (     ts1.speciesID IS NULL
                                    AND ts1.subspeciesID IS NULL AND ts1.subspecies_authorID IS NULL
                                    AND ts1.varietyID IS NULL AND ts1.variety_authorID IS NULL
                                    AND ts1.subvarietyID IS NULL AND ts1.subvariety_authorID IS NULL
                                    AND ts1.formaID IS NULL AND ts1.forma_authorID IS NULL
                                    AND ts1.subformaID IS NULL AND ts1.subforma_authorID IS NULL
                                    AND tg1.genus LIKE '$gen%'
                                  ) OR 
                                  (     ts2.speciesID IS NULL
                                    AND ts2.subspeciesID IS NULL AND ts2.subspecies_authorID IS NULL
                                    AND ts2.varietyID IS NULL AND ts2.variety_authorID IS NULL
                                    AND ts2.subvarietyID IS NULL AND ts2.subvariety_authorID IS NULL
                                    AND ts2.formaID IS NULL AND ts2.forma_authorID IS NULL
                                    AND ts2.subformaID IS NULL AND ts2.subforma_authorID IS NULL
                                    AND tg2.genus LIKE '$gen%'
                                  )
                                ) ";
            // mdld
            } elseif (isset($params['mdldSearch']) && strlen($params['mdldSearch']) > 0) {
                $params['mdldSearch'] = strtolower(trim($this->removeID($params['mdldSearch'])));

                $config  = Settings::Load();
                $service = new JsonRPCClient_v1($config->get('serviceTaxamatch'));

                $matches = $service->getMatchesService('vienna', $params['mdldSearch'], array('showSyn' => false, 'NearMatch' => false));

                $ids = array();
                $lookup = '';
                foreach ($matches['result'][0]['searchresult'] as $genus) {
                    $lookup .= ",'{$genus['ID']}'";
                    if (count($genus['species']) > 0) {
                        foreach ($genus['species'] as $species) {
                            $ids[] = intval($species['taxonID']);
                        }
                    }
                }
                $lookup = substr($lookup, 1);
                $genLookup = (!empty($lookup)) ? " AND genID in ($lookup)" : '';
                $result = $db->query("SELECT taxonID
                                      FROM tbl_tax_species
                                      WHERE speciesID IS NULL
                                       AND subspeciesID IS NULL AND subspecies_authorID IS NULL
                                       AND varietyID IS NULL AND variety_authorID IS NULL
                                       AND subvarietyID IS NULL AND subvariety_authorID IS NULL
                                       AND formaID IS NULL AND forma_authorID IS NULL
                                       AND subformaID IS NULL AND subforma_authorID IS NULL
                                       $genLookup");
                if ($result) {
                    while ($row = $result->fetch_array()) {
                        $ids[] = $row['taxonID'];
                    }
                }
                $ids = implode(",", $ids);
                $where2 = " AND (   sy.taxonID IN ($ids)
                                 OR sy.acc_taxon_ID IN ($ids)
                                ) ";
            }

            // Join Tables
            $sqlbottom = " FROM tbl_tax_synonymy sy
                             LEFT JOIN tbl_tax_species ts1 ON ts1.taxonID = sy.taxonID
                             LEFT JOIN tbl_tax_species ts2 ON ts2.taxonID = sy.acc_taxon_ID
                            
                             LEFT JOIN tbl_tax_genera tg1 ON tg1.genID = ts1.genID
                             LEFT JOIN tbl_tax_authors ta01 ON ta01.authorID = ts1.authorID
                             LEFT JOIN tbl_tax_authors ta11 ON ta11.authorID = ts1.subspecies_authorID
                             LEFT JOIN tbl_tax_authors ta21 ON ta21.authorID = ts1.variety_authorID
                             LEFT JOIN tbl_tax_authors ta31 ON ta31.authorID = ts1.subvariety_authorID
                             LEFT JOIN tbl_tax_authors ta41 ON ta41.authorID = ts1.forma_authorID
                             LEFT JOIN tbl_tax_authors ta51 ON ta51.authorID = ts1.subforma_authorID
                             LEFT JOIN tbl_tax_epithets te01 ON te01.epithetID = ts1.speciesID
                             LEFT JOIN tbl_tax_epithets te11 ON te11.epithetID = ts1.subspeciesID
                             LEFT JOIN tbl_tax_epithets te21 ON te21.epithetID = ts1.varietyID
                             LEFT JOIN tbl_tax_epithets te31 ON te31.epithetID = ts1.subvarietyID
                             LEFT JOIN tbl_tax_epithets te41 ON te41.epithetID = ts1.formaID
                             LEFT JOIN tbl_tax_epithets te51 ON te51.epithetID = ts1.subformaID
                            
                             LEFT JOIN tbl_tax_genera tg2 ON tg2.genID = ts2.genID
                             LEFT JOIN tbl_tax_authors ta02 ON ta02.authorID = ts2.authorID
                             LEFT JOIN tbl_tax_authors ta12 ON ta12.authorID = ts2.subspecies_authorID
                             LEFT JOIN tbl_tax_authors ta22 ON ta22.authorID = ts2.variety_authorID
                             LEFT JOIN tbl_tax_authors ta32 ON ta32.authorID = ts2.subvariety_authorID
                             LEFT JOIN tbl_tax_authors ta42 ON ta42.authorID = ts2.forma_authorID
                             LEFT JOIN tbl_tax_authors ta52 ON ta52.authorID = ts2.subforma_authorID
                             LEFT JOIN tbl_tax_epithets te02 ON te02.epithetID = ts2.speciesID
                             LEFT JOIN tbl_tax_epithets te12 ON te12.epithetID = ts2.subspeciesID
                             LEFT JOIN tbl_tax_epithets te22 ON te22.epithetID = ts2.varietyID
                             LEFT JOIN tbl_tax_epithets te32 ON te32.epithetID = ts2.subvarietyID
                             LEFT JOIN tbl_tax_epithets te42 ON te42.epithetID = ts2.formaID
                             LEFT JOIN tbl_tax_epithets te52 ON te52.epithetID = ts2.subformaID
                         ";

            // Query Fields...
            $sql = " SELECT sy.taxonID as 'taxonID1',
                         sy.acc_taxon_ID  as 'taxonID2',
                        
                         tg1.genus as 'genus1',
                         ta01.author as 'author01',
                         ta11.author as 'author11',
                         ta21.author as 'author21',
                         ta31.author as 'author31',
                         ta41.author as 'author41',
                         ta51.author as 'author51',
                         te01.epithet as 'epithet01',
                         te11.epithet as 'epithet11',
                         te21.epithet as 'epithet21',
                         te31.epithet as 'epithet31',
                         te41.epithet as 'epithet41',
                         te51.epithet as 'epithet51',
                        
                         tg2.genus as 'genus2',
                         ta01.author as 'author01',
                         ta02.author as 'author02',
                         ta12.author as 'author12',
                         ta22.author as 'author22',
                         ta32.author as 'author32',
                         ta42.author as 'author42',
                         ta52.author as 'author52',
                         te01.epithet as 'epithet01',
                         te02.epithet as 'epithet02',
                         te12.epithet as 'epithet12',
                         te22.epithet as 'epithet22',
                         te32.epithet as 'epithet32',
                         te42.epithet as 'epithet42',
                         te52.epithet as 'epithet52'
                        
                         {$sqlbottom}
                         WHERE sy.source_citationID = $citid
                           {$where2}
                         ORDER BY
                           tg1.genus, te01.epithet, te11.epithet, te21.epithet, te31.epithet, te41.epithet, te51.epithet,
                           tg2.genus, te02.epithet, te12.epithet, te22.epithet, te32.epithet, te42.epithet, te52.epithet, sy.tax_syn_ID
                         LIMIT $pbegin, $this->pagination ";

            // get Ids
            $ac_taxinit = array();
            if ($result = $db->query($sql)) {
                while ($row = $result->fetch_array()) {
                     // Make taxons (much faster than loading it via display::taxon because fields are already there)
                    $ac_taxinit[] = array($row['taxonID1'], $this->taxon($row,1), $row['taxonID2'], $this->taxon($row,2));
                }
            }

            // get count of results
            $result = $db->query("SELECT COUNT(*) as 'c'
                                  {$sqlbottom}
                                  WHERE sy.source_citationID = $citid
                                   $where2");
            if ($result) {
                $row1 = $result->fetch_array();
            }

            // get all counts...
            $result = $db->query(" SELECT COUNT(*) as 'c'
                                  FROM tbl_tax_synonymy sy
                                  WHERE sy.source_citationID = $citid");
            if ($result) {
                $row2 = $result->fetch_array();
            }
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }

		// return it.
		return array('cf' => $row1['c'] ?? 0, 'ca' => $row2['c'] ?? 0, 'syns' => $ac_taxinit ?? []);
	}


    /**
     * Saves map lines data based on the provided parameters. This method processes the map lines, verifies or inserts
     * them into the database, and logs successes and failures.
     *
     * @param array $params An associative array of parameters used during the map line saving process. Keys include:
     *                      - citationID: The citation ID used to associate the map lines with a specific source.
     *                      - Other keys may be used internally for processing map line data.
     * @return array An associative array containing the status of the save operation. Keys include:
     *               - success: A flag indicating the overall success of the operation (1 for success, 0 for failure).
     *               - error: An array of failed save attempts containing details about the errors encountered.
     *               - successx: An array of successfully saved map lines with their corresponding details.
     */
	public function SaveMapLines(array $params): array
    {
		$citid = intval($params['citationID']);
		$uid   = $_SESSION['uid'];

		$new = $this->getMapLines($params,1,0);

        $notdone = array();
        $successx = array();
        try {
            $db = DbAccess::ConnectTo('INPUT');

            // todo: review...
            if (count($new) > 0) {
                foreach($new as $taxonID => $obj) {
                    if ($taxonID == 'error') {
                        foreach($obj as $err){
                            $notdone[] = array($err[0], $err[1], $err[1], 'Not an ID.');
                        }
                        continue;
                    }
                    if (count($obj) == 0) {
                        continue;
                    }
                    foreach ($obj as $acctaxonID => $x) {
                        $taxonIDfilt = intval($taxonID);
                        $acctaxonIDfilt = intval($acctaxonID);
                        // If not in database yet, add it
                        $result2 = $db->query("SELECT COUNT(*) AS 'c' 
                                               FROM herbarinput.tbl_tax_synonymy 
                                               WHERE source_citationID = $citid 
                                                 AND source = 'literature' 
                                                 AND taxonID = $taxonIDfilt 
                                                 AND IFNULL(acc_taxon_ID, 0) = $acctaxonIDfilt 
                                               LIMIT 1");
                        if ($result2) {
                            $row2 = $result2->fetch_array();
                            if ($row2['c'] == 0){
                                $result2 = $db->query("INSERT INTO herbarinput.tbl_tax_synonymy SET
                                                         taxonID            = $taxonIDfilt,
                                                         acc_taxon_ID       = " . (($acctaxonIDfilt == 0) ? 'NULL' : "$acctaxonIDfilt") . ",
                                                         ref_date           = null,
                                                         preferred_taxonomy = 0,
                                                         annotations        = '',
                                                         locked             = 1,
                                                         source             = 'literature',
                                                         source_citationID  = $citid,
                                                         source_person_ID   = NULL,
                                                         source_serviceID   = NULL,
                                                         source_specimenID  = NULL,
                                                         userID             = '$uid'");
                                if ($result2) {
                                    $tax_syn_ID = $db->insert_id;
                                    Log::tbl_tax_synonymy($tax_syn_ID,0);
                                    $successx[] = array($x,$taxonID,$acctaxonID);
                                } else {
                                    $notdone[] = array($x, $taxonID, $acctaxonID, $db->error);
                                }
                            } else {
                                $existed = (isset($row2['c']) && $row2['c'] > 0);
                                $notdone[] = array($x, $taxonID, $acctaxonID, $existed ? 1 : 'unknown');
                            }
                        } else {
                            $notdone[] = array($x, $taxonID, $acctaxonID, $db->error);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }

        if(count($notdone)>0){
			$res=array('success'=>0, 'error'=>$notdone,'successx'=>$successx);
		}else if(count($successx)>0){
			$res=array('success'=>1,'successx'=>$successx);
		}else{
			$res=array('success'=>0);
		}
		return $res;
	}


    /**
     * remove the ID from a string if present and returns the remaining part. ID must be enclosed in "<>" brackets and be positioned at the end
     *
     * @param string $item string to parse
     * @return string string with removed ID (if any)
     */
    private function removeID (string $item): string
    {
        $pos = strrpos($item, ' <');
        if ($pos !== false) {
            return substr($item, 0, $pos);
        } else {
            return $item;
        }
    }


    /**
     * Constructs a taxon name string based on the provided data array and used tab.
     *
     * @param array $row An associative array containing taxonomic data. The keys must follow
     *                   the pattern 'genus', 'epithetX', 'authorX', and 'taxonIDX' where X
     *                   is a numeric suffix representing different hierarchical levels.
     * @param int $t Specifies the left (t=1) or right (t=2) tab, defaults to nothing (t=0))
     *
     * @return string The formatted taxon name string, including genus, epithets, authors,
     *                and the associated taxon ID, or '0 ' if the taxon ID is unknown.
     */
    private function taxon(array $row, int $t = 0): string
    {
        if ($row['taxonID' . $t] == 0) {
            return '0 ';
        }
        $ret = $row['genus' . $t];
        if ($row['epithet0' . $t]) $ret .= " "          . $row['epithet0' . $t] . " " . $row['author0' . $t];
        if ($row['epithet1' . $t]) $ret .= " subsp. "   . $row['epithet1' . $t] . " " . $row['author1' . $t];
        if ($row['epithet2' . $t]) $ret .= " var. "     . $row['epithet2' . $t] . " " . $row['author2' . $t];
        if ($row['epithet3' . $t]) $ret .= " subvar. "  . $row['epithet3' . $t] . " " . $row['author3' . $t];
        if ($row['epithet4' . $t]) $ret .= " forma "    . $row['epithet4' . $t] . " " . $row['author4' . $t];
        if ($row['epithet5' . $t]) $ret .= " subforma " . $row['epithet5' . $t] . " " . $row['author5' . $t];

        $ret .= " <" . $row['taxonID'.$t] . ">";

        return $ret;
    }

}
