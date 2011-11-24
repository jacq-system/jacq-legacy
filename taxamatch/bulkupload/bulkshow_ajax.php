<?PHP

session_name('herbarium_wu_taxamatch');
session_start();

include('inc/variables.php');
include('inc/connect.php');
/*
if (empty($_SESSION['uid'])) die();


$row = mysql_fetch_array($result);
$jobID = $row['jobID'];
*/


$showResults=new showResults();
$methodName = (isset($_POST['method'])) ? $_POST['method'] : "";

$ret=array();
if(method_exists($showResults, $methodName)){
	try{
		$params=$_POST['params'];
		$ret=array(
			'res'=>$showResults->$methodName($params)
		);
		if(($a=$showResults->getInfo())){
			$ret['info']=$a;
		}

	}catch (Exception $e){
		$ret=array(
			'error'=>$e->getMessage()
		);
	}
}else{
	$ret=array(
		'error'=>"Metod: '{$methodName}' doesn't exist.",
	);
}

$ob = ob_get_clean();
if(strlen($ob)>0){
	$ret['ob']=$ob;
}
echo json_encode($ret);



class showResults{
	private $info=false;
	
	public function getInfo(){
		return $this->info;
	}
	public function x_showResult($params){

		$jobID=isset($params['jobID'])?intval($params['jobID']):0;
		
		$start=isset($params['page_index'])?intval($params['page_index']):0;
		$limit=isset($params['limit'])?intval($params['limit']):20;
		$displayOnlyParts=isset($params['displayOnlyParts'])?intval($params['displayOnlyParts']):20;
		$start=$start*$limit;
		
		$end=$start+$limit;
		
		//$result = db_query("SELECT * FROM tbljobs WHERE jobID = '{jobID}' AND uid = '" . $_SESSION['uid'] . "'");
		$result = db_query("SELECT * FROM tbljobs WHERE jobID = '{$jobID}' ");
		if (mysql_num_rows($result) == 0){
				return array('html'=>'Not allowed','maxc'=>0);
		}

		
		$result = db_query("SELECT COUNT(*) as 'c' FROM tblqueries WHERE jobID = '$jobID'");

		$row = mysql_fetch_array($result);
		$maxc=$row['c'];
		
		$out = "";
		$correct = 0;
		$nr = 1;
		$result = db_query("SELECT * FROM tblqueries WHERE jobID = '$jobID' ORDER BY lineNr limit {$start},{$end}");
		while ($row = mysql_fetch_array($result)) {
			$lnr= $row['lineNr'];
			$matches = @unserialize($row['result']);
			if ($matches) {
				foreach ($matches['result'] as $match) {
					$out2 = '';
					$found = 0;
					$line = 0;
					$blocked = 0;
					foreach ($match['searchresult'] as $key => $row) {
						if (isset($match['type']) && $match['type'] == 'uni') {
							if ($found > 0) {
								$out2 .= "<tr valign='baseline'>";
							}
							$out2 .= '<td>&nbsp;&nbsp;<b>' . $row['taxon'] . ' <' . $row['ID'] . '></b></td>'
								   . '<td>&nbsp;' . $row['distance'] . '&nbsp;</td>'
								   . '<td align="right">&nbsp;' . number_format($row['ratio'] * 100, 1) . "%</td></tr>\n";
							$found++;
							$line++;
						} else {
							foreach ($row['species'] as $key2 => $row2) {
								$showSynonyms = (!empty($row2['synonyms'])) ? true : false;		  // BP, 07.2010: synonyms?
								if ($displayOnlyParts && $row2['distance'] == 0) $blocked++;
								if ($found > 0) {
									$out2 .= "<tr valign='baseline'>";
								}
								// BP: 07.2010: add synonyms to output
								$out2 .= '<td>&nbsp;&nbsp;<b>' . $row2['taxon'] . ' <' . $row2['taxonID'] . '></b>'
									   . (($showSynonyms) ? ($this->prettyPrintSynonyms($row2['synonyms'])) : (""))	 // BP, 07.2010: synonyms!
									   . '</td>'
									   . '<td>&nbsp;' . $row2['distance'] . '&nbsp;</td>'
									   . '<td align="right">&nbsp;' . number_format($row2['ratio'] * 100, 1) . "%</td></tr>\n";
								if ($row2['syn']) {
									$out2 .= "<tr><td>&nbsp;&nbsp;&rarr;&nbsp;" . $row2['syn'] . " <" . $row2['synID'] . "></td><td colspan='2'></td></tr>\n";
									$line++;
								}
								$found++;
								$line++;
							}
						}
					}
					if (!$found) {
						$out2 = "<td colspan='3'>nothing found</td></tr>\n";
						$line++;
					}
					if (!$found || $found != $blocked) {
						$nr++;
						$out .= "<tr valign='baseline'>"
							  . "<td rowspan='$line' align='right'>" .$lnr . "</td>"
							  . "<td rowspan='$line'>"
							  . "&nbsp;&nbsp;<big><b>" . $match['searchtext'] . "</b></big>&nbsp;&nbsp;<br>\n"
							  . "&nbsp;&nbsp;$found match" . (($found > 1) ? 'es' : '') . " found&nbsp;&nbsp;<br>\n"
							  . "&nbsp;&nbsp;" . $match['rowsChecked'] . " rows checked&nbsp;&nbsp;"
							  . "</td>"
							  . $out2;
					} else {
						$correct++;
					}
				}
			}
		}

		$ret= "
Searched {$maxc} items for matching results.<br><br>		
<table rules='all' border='1'>\n"
		   . "<tr><th></th><th>&nbsp;search for&nbsp;</th><th>result</th><th>Dist.</th><th>Ratio</th></tr>\n"
		   . $out;
		if ($correct > 0) $ret.= "<tr><td colspan='5'>&nbsp;&nbsp;$correct queries had a full hit</td></tr>\n";
		$ret.=  "</table>\n";
		
		return array('html'=>$ret,'maxc'=>$maxc);
	}
	

	// BP, 07.2010: return a pretty print of the synonyms belonging to a species
	private function prettyPrintSynonyms($synonyms20) {
		$synString = "";
		for ($counter20 = 0; $counter20 < count($synonyms20); $counter20++) {
			$synString .= $this->prettyPrintSynonymEntry($synonyms20[$counter20]);
			$synonyms40 = $synonyms20[$counter20]['synonyms'];
			for ($counter40 = 0; $counter40 < count($synonyms40); $counter40++) {
				$synString .= $this->prettyPrintSynonymEntry($synonyms40[$counter40],2);
			}
		}
		return $synString;
	}

	// BP, 07.07.2010: return a pretty print of one synonym
	private function prettyPrintSynonymEntry($synonym,$indent=1) {
		$startOfLine = "<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		for ($i=1; $i < $indent; $i++)
			$startOfLine .= "&nbsp;&nbsp;";
		$synString = $startOfLine ;
		$synString .= $synonym['equalsSign'];
		$synString .= "&nbsp;&nbsp;";
		$synString .= $synonym['status'];
		$synString .= $startOfLine . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		$synString .= $synonym['name'];
		return $synString;
	}
}
