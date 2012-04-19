<?PHP
session_start();
require_once('../inc/connect.php');
require_once('../inc/cssf.php');
require_once('../inc/log_functions.php');
require_once('../inc/mapLines.php');
no_magic();

foreach($_GET as $k=>$v){
	$_POST[$k]=$v;
}
$pagination=5;
function d($val){
	return $val;
}

function taxon1($row,$t=0){
	if($row['taxonID'.$t]==0)return '0 ';
	$withSeperator=false;
	$ret = $row['genus'.$t];
	if ($row['epithet0'.$t]) $ret .= " "          .$row['epithet0'.$t] . (($withSeperator) ? chr(194) . chr(183) : "") . " " . $row['author0'.$t];
	if ($row['epithet1'.$t]) $ret .= " subsp. "   .$row['epithet1'.$t] . " " . $row['author1'.$t];
	if ($row['epithet2'.$t]) $ret .= " var. "     .$row['epithet2'.$t] . " " . $row['author2'.$t];
	if ($row['epithet3'.$t]) $ret .= " subvar. "  .$row['epithet3'.$t] . " " . $row['author3'.$t];
	if ($row['epithet4'.$t]) $ret .= " forma "    .$row['epithet4'.$t] . " " . $row['author4'.$t];
	if ($row['epithet5'.$t]) $ret .= " subforma " .$row['epithet5'.$t] . " " . $row['author5'.$t];

	$ret .= " <" . $row['taxonID'.$t] . ">";

	return $ret;
}


// todo: check ab hier...
if(isset($_POST['function']) ){
	$p_citationID=extractID($_POST['sel']);
	
	// RemoveTaxSynLines
	if($_POST['function']=='RemoveMapLine'){
		$citid=$p_citationID;
		$taxonID=$_POST['leftID'];
		$acc_taxon_ID=$_POST['rightID'];
		$res=0;
		
		$sql2 = "SELECT tax_syn_ID FROM herbarinput.tbl_tax_synonymy WHERE source_citationID={$citid} and source='literature' and taxonID ='{$taxonID}' and acc_taxon_ID='{$acc_taxon_ID}' LIMIT 1";
		$result2 = db_query($sql2);
		if($result2 && $row2 = mysql_fetch_array($result2)){
			logTbl_tax_synonymy($row2['tax_syn_ID'],2);
			$sql3 = "DELETE FROM herbarinput.tbl_tax_synonymy WHERE tax_syn_ID='{$row2['tax_syn_ID']}' LIMIT 1";
			$res3 = db_query($sql3);
			if($res3){
				$res=1;			
			}
		}
		
		$res=array('success'=>$res);
		
		$res=json_encode($res);
		
		echo $res;
		exit;
	
	// Load/Search TaxSyns
	}else if($_POST['function']=='LoadMapLines'){
		
		$where="";
		$mdldSearch=isset($_POST['search'])?$_POST['search']:'';
		$page=$_POST['pageIndex'];
		$pbegin=$page*$pagination;

		$where2="";
		$where1="
 sy.source_citationID=$p_citationID
 
";
		// Switch Search
		// species...
		if(isset($_POST['speciesSearch']) && strlen($_POST['speciesSearch'])>0){
			$spec="'".mysql_escape_string($_POST['speciesSearch']) . "%'";
			$gen="'".mysql_escape_string($_POST['genusSearch']) . "%'";
			
			$where2="
AND (
    te01.epithet LIKE {$spec}
 OR te11.epithet LIKE {$spec}
 OR te21.epithet LIKE {$spec}
 OR te31.epithet LIKE {$spec}
 OR te41.epithet LIKE {$spec}
 OR te51.epithet LIKE {$spec}

 OR te02.epithet LIKE {$spec}
 OR te12.epithet LIKE {$spec}
 OR te22.epithet LIKE {$spec}
 OR te32.epithet LIKE {$spec}
 OR te42.epithet LIKE {$spec}
 OR te52.epithet LIKE {$spec}
)
AND(
    tg1.genus LIKE {$gen}
 or tg2.genus LIKE {$gen}
)
";
		// genus...
		}else if(isset($_POST['genusSearch']) && strlen($_POST['genusSearch'])>0){
			$gen=mysql_escape_string($_POST['genusSearch']) . '%';
			
			$where2="
AND(
 (			
    ts1.speciesID IS NULL
  AND ts1.subspeciesID IS NULL AND ts1.subspecies_authorID IS NULL
  AND ts1.varietyID IS NULL AND ts1.variety_authorID IS NULL
  AND ts1.subvarietyID IS NULL AND ts1.subvariety_authorID IS NULL
  AND ts1.formaID IS NULL AND ts1.forma_authorID IS NULL
  AND ts1.subformaID IS NULL AND ts1.subforma_authorID IS NULL
  AND tg1.genus LIKE '{$gen}'
 )OR(
  ts2.speciesID IS NULL
  AND ts2.subspeciesID IS NULL AND ts2.subspecies_authorID IS NULL
  AND ts2.varietyID IS NULL AND ts2.variety_authorID IS NULL
  AND ts2.subvarietyID IS NULL AND ts2.subvariety_authorID IS NULL
  AND ts2.formaID IS NULL AND ts2.forma_authorID IS NULL
  AND ts2.subformaID IS NULL AND ts2.subforma_authorID IS NULL
  AND tg2.genus LIKE '{$gen}'
 )
)
";
		//mdld
		}else if(isset($_POST['mdldSearch']) && strlen($_POST['mdldSearch'])>0){
			$service = new jsonRPCClient($_OPTIONS['serviceTaxamatch']);
			try {
				$matches = $service->getMatchesService('vienna',$_POST['mdldSearch'],array('showSyn'=>false,'NearMatch'=>false));

				$m=$matches['result'][0]['searchresult'];
				$ids=array();
				$lookup='';
				foreach($matches['result'][0]['searchresult'] as $genus){
					$lookup.=",'{$genus['ID']}'";
					if(count($genus['species'])>0){
						foreach($genus['species'] as $species){
							$ids[]=$species['taxonID'];
						}
					}
				}
				$lookup=substr($lookup,1);
				$sql="
SELECT
 taxonID
FROM
 tbl_tax_species
WHERE
 speciesID IS NULL
  AND subspeciesID IS NULL AND subspecies_authorID IS NULL
 AND varietyID IS NULL AND variety_authorID IS NULL
 AND subvarietyID IS NULL AND subvariety_authorID IS NULL
 AND formaID IS NULL AND forma_authorID IS NULL
 AND subformaID IS NULL AND subforma_authorID IS NULL
 AND genID in ({$lookup})
";
				$result = db_query($sql);
				if(	$result){
					while($row=mysql_fetch_array($result)){
						$ids[]=$row['taxonID'];
					}
					$ids="'".implode("','",$ids)."'";
				}
				$where2="
AND(
    sy.taxonID in({$ids})
 or sy.acc_taxon_ID in ({$ids})
)
";
			}catch (Exception $e) {
				$out =  "Fehler " . nl2br($e);
			}
		
		}

		// Join Tables		
		$sqlbottom="
FROM
 tbl_tax_synonymy sy
 
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
		$sql="
SELECT 
 sy.taxonID as 'taxonID1',
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
WHERE
{$where1}
{$where2}
ORDER BY
 tg1.genus, tg2.genus,sy.tax_syn_ID
LIMIT
 {$pbegin},{$pagination}
";
//echo $sql;exit;
		
		// get Ids
 		$ac_taxinit=array();
		if($result = db_query($sql)) {
			while ($row = mysql_fetch_array($result)) {
				 // Make taxons (much faster than loading it via display::taxon because fields are already there)
				$t1=taxon1($row,1);
				$t2=taxon1($row,2);
				
				$ac_taxinit[]=array($row['taxonID1'],$t1,$row['taxonID2'],$t2);
			}
		}
		
		// get count of results
		$sqlcountsearch="
SELECT 
 COUNT(*) as 'c'
{$sqlbottom}
WHERE
{$where1}
{$where2}
 ";
		$row1['c']=0;
		$row2['c']=0;
		if($result = db_query($sqlcountsearch)){
			$row1 = mysql_fetch_array($result);
		}
		
		// get all counts..
		$sqlcountall="
SELECT 
 COUNT(*) as 'c'
FROM
tbl_tax_synonymy sy
WHERE
{$where1}
 ";
		if($result = db_query($sqlcountall)){
			$row2 = mysql_fetch_array($result);
		}
		
		// return it.
		$res=array('cf'=>$row1['c'],'ca'=>$row2['c'],'syns'=>$ac_taxinit);
		
		
		$res=json_encode($res);
		
		echo $res;
		exit;
	
	// Save new pairs...
	}else if($_POST['function']=='SaveMapLines'){


		
		$uid=d($_SESSION['uid']);
		$citid=$p_citationID;
		
		$new=getMapLines($_POST,1);
		
		// todo: review...
		$sql="
INSERT INTO  herbarinput.tbl_tax_synonymy
(taxonID,acc_taxon_ID,ref_date,preferred_taxonomy,annotations,locked,source,source_citationID,source_person_ID,source_serviceID,source_specimenID,userID)
VALUES 
";	
		$val="";
		$notdone=array();
		foreach($new as $taxonID=>$obj){
			foreach($obj as $acctaxonID=>$nop){
				// If not in database yet, add it
				$row2=array();
				$sql2 = "SELECT COUNT(*) as 'c' FROM herbarinput.tbl_tax_synonymy WHERE source_citationID={$citid} and source='literature' and taxonID ='{$taxonID}' and acc_taxon_ID='{$acctaxonID}' LIMIT 1";
				$result2 = db_query($sql2);
				if($result2 && $row2 = mysql_fetch_array($result2)){
					if($row2['c']==0){
						$sql2 = $sql." ('{$taxonID}','{$acctaxonID}',null,'0','','1','literature',{$citid},null,null,null,'{$uid}') ";
						$result2 = db_query($sql2);
						if($result2){
							$tax_syn_ID=mysql_insert_id();
							logTbl_tax_synonymy($tax_syn_ID,0);
							continue;
						}
					}
				}
				$existed=(isset($row2['c']) && $row2['c']>0);
				$notdone[]=array($taxonID,$acctaxonID,$existed);
			}
		}
		if(count($notdone)>0){
			$res=array('success'=>0, 'error'=>$notdone);
		}else{
			$res=array('success'=>1);
		}
		$res=json_encode($res);
		
		echo $res;
		exit;
	}
}