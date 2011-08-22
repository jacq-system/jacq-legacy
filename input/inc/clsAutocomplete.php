<?php
/**
 * Autocomplete methods singleton - handling all autocomplete methods
 *
 * A singleton to supply various autocomplete methods
 *
 * @author Johannes Schachner
 * @version 1.0
 * @package clsAutocomplete
 */
function diff($old, $new){
	foreach($old as $oindex => $ovalue){
		$nkeys = array_keys($new, $ovalue);
		foreach($nkeys as $nindex){
			$matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
				$matrix[$oindex - 1][$nindex - 1] + 1 : 1;
			if($matrix[$oindex][$nindex] > $maxlen){
				$maxlen = $matrix[$oindex][$nindex];
				$omax = $oindex + 1 - $maxlen;
				$nmax = $nindex + 1 - $maxlen;
			}
		}	
	}
	if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
	return array_merge(
		diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
		array_slice($new, $nmax, $maxlen),
		diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen))
	);
}

function htmlDiff($old, $new){
	$diff = diff(str_split($old),str_split($new));
	foreach($diff as $k){
		if(is_array($k))
			/*$ret .= (!empty($k['d'])?"<span class='del'>".implode(' ',$k['d'])."</span> ":'').
				(!empty($k['i'])?"<span class='ins'>".implode(' ',$k['i'])."</span> ":'');*/
			$ret .= (!empty($k['d'])?"<del>".implode('',$k['d'])."</del>":'').
				(!empty($k['i'])?"<ins>".implode('',$k['i'])."</ins>":'');
		else $ret .= $k . '';
	}
	
	return $ret;
}

function col2011sort_a($a,$b){
    if($a['distance']==$b['distance']) {
		if($a['ratio']==$b['ratio']){
			return strcmp($a['taxon'],$b['taxon']);
		}
		return($a['ratio']<$b['ratio'])?-1:1;		
    }
    return($a['distance']<$b['distance'])?-1:1;
}


error_reporting(E_ALL^E_NOTICE);
/**
 * Autocomplete methods singleton - handling all autocomplete methods
 * @package clsAutocomplete
 * @subpackage classes
 */
class clsAutocomplete
{
/********************\
|					|
|  static variables  |
|					|
\********************/

private static $instance = null;

/********************\
|					|
|  static functions  |
|					|
\********************/

/**
 * instances the class clsAutocomplete
 *
 * @return clsAutocomplete new instance of that class
 */
public static function Load()
{
	if (self::$instance == null) {
		self::$instance = new clsAutocomplete();
	}
	return self::$instance;
}

/*************\
|			 |
|  variables  |
|			 |
\*************/
			

/***************\
|			   |
|  constructor  |
|			   |
\***************/

protected function __construct () {}

/********************\
|					|
|  public functions  |
|					|
\********************/


/**
 * autocomplete a taxonomy author entry field
 *
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function taxAuthor ($value, $noExternals = false)
{
	$results = array();
	if ($value && strlen($value) > 1) {
		$pieces = explode(chr(194) . chr(183) . " [", $value);
		try {
			/* @var $db clsDbAccess */
			$db = clsDbAccess::Connect('INPUT');
			$sql = "SELECT author, authorID, Brummit_Powell_full
					FROM tbl_tax_authors
					WHERE (   author LIKE " . $db->quote ($pieces[0] . '%') . "
						   OR Brummit_Powell_full LIKE " . $db->quote ($pieces[0] . '%') . ")";
			if ($noExternals) $sql .= " AND external = 0";
			$sql .= " ORDER BY author";
			/* @var $dbst PDOStatement */
			$dbst = $db->query($sql);
			$rows = $dbst->fetchAll();
			if (count($rows) > 0) {
				foreach ($rows as $row) {
					$res = $row['author'];
					if ($row['Brummit_Powell_full']) $res .= chr(194) . chr(183) . " [" . replaceNewline($row['Brummit_Powell_full']) . "]";
					$results[] = array('id'	=> $row['authorID'],
									   'label' => $res . " <" . $row['authorID'] . ">",
									   'value' => $res . " <" . $row['authorID'] . ">",
									   'color' => '');
				}
			}
		}
		catch (Exception $e) {
			error_log($e->getMessage());
		}
	}

	return $results;
}


/**
 * autocomplete an author entry field without external entries (external=0)
 *
 * @param string $value text to search for
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function taxAuthorNoExternals ($value)
{
	return $this->taxAuthor($value, true);
}


/**
 * autocomplete a collector entry field
 *
 * @param string $value text to search for
 * @param bool[optional] $second if true use tbl_collector2 (default = false)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function collector ($value, $second = false)
{
	$results = array();
	if ($value && strlen($value) > 1) {
		$pieces = explode(" <", $value);
		try {
			/* @var $db clsDbAccess */
			$db = clsDbAccess::Connect('INPUT');
			if ($second) {
				$sql = "SELECT Sammler_2 AS Sammler, Sammler_2ID AS SammlerID
						FROM tbl_collector_2
						WHERE Sammler_2 LIKE " . $db->quote ($pieces[0] . '%') . "
						ORDER BY Sammler_2";
			} else {
				$sql = "SELECT Sammler, SammlerID
						FROM tbl_collector
						WHERE Sammler LIKE " . $db->quote ($pieces[0] . '%') . "
						ORDER BY Sammler";
			}
			/* @var $dbst PDOStatement */
			$dbst = $db->query($sql);
			$rows = $dbst->fetchAll();
			if (count($rows) > 0) {
				foreach ($rows as $row) {
					$results[] = array('id'	=> $row['SammlerID'],
									   'label' => $row['Sammler'] . " <" . $row['SammlerID'] . ">",
									   'value' => $row['Sammler'] . " <" . $row['SammlerID'] . ">",
									   'color' => '');
				}
			}
		}
		catch (Exception $e) {
			error_log($e->getMessage());
		}
	}

	return $results;
}


/**
 * autocomplete a second collector entry field (tbl_collector_2)
 *
 * @param string $value text to search for
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function collector2 ($value) {
	return $this->collector($value, true);
}

/**
 * autocomplete a person entry field
 * The various parts of a person field are identified and used (if present) as a search criteria
 *
 * @param string $value text to search for
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function person ($value)
{
	$results = array();
	if ($value && strlen($value) > 1) {
		$pieces = explode(", ", $value, 2);
		$p_familyname = $pieces[0];
		if (count($pieces) > 1) {
			$pieces = explode(" (", $pieces[1], 2);
			$p_firstname = $pieces[0];
			if (count($pieces) > 1) {
				$pieces = explode(" - ", $pieces[1], 2);
				$p_birthdate = $pieces[0];
				if (count($pieces) > 1) {
					$pieces = explode(")", $pieces[1], 2);
					$p_death = $pieces[0];
				} else {
					$p_death = '';
				}
			} else {
				$p_birthdate = $p_death = '';
			}
		} else {
			$p_firstname = $p_birthdate = $p_death = '';
		}

		try {
			/* @var $db clsDbAccess */
			$db = clsDbAccess::Connect('INPUT');
			$sql = "SELECT person_ID, p_familyname, p_firstname, p_birthdate, p_death
					FROM tbl_person
					WHERE p_familyname LIKE " . $db->quote ($p_familyname . '%');
			if ($p_firstname) $sql .= " AND p_firstname LIKE " . $db->quote ($p_firstname . '%');
			if ($p_birthdate) $sql .= " AND p_birthdate LIKE " . $db->quote ($p_birthdate . '%');
			if ($p_death)	 $sql .= " AND p_death LIKE " . $db->quote ($p_death . '%');
			$sql .= " ORDER BY p_familyname, p_firstname, p_birthdate, p_death";
			/* @var $dbst PDOStatement */
			$dbst = $db->query($sql);
			$rows = $dbst->fetchAll();
			if (count($rows) > 0) {
				foreach ($rows as $row) {
					$text = $row['p_familyname'] . ", " . $row['p_firstname'] . " (" . $row['p_birthdate'] . " - " . $row['p_death'] . ") <" . $row['person_ID'] . ">";
					$results[] = array('id'	=> $row['person_ID'],
									   'label' => $text,
									   'value' => $text,
									   'color' => '');
				}
			}
		}
		catch (Exception $e) {
			error_log($e->getMessage());
		}
	}

	return $results;
}


/**
 * autocomplete a citation entry field
 * If the searchstring has only one part only the author will be searched
 * If the searchstring consists of two parts the first one is used for author, the second one for year, title and periodical
 *
 * @param string $value text to search for
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function citation ($value)
{
	$results = array();
	if ($value && strlen($value) > 1) {
		$pieces = explode(" ", $value);
		$autor = $pieces[0];
		if (strlen($pieces[1]) > 2 || (strlen($pieces[1]) == 2 && substr($pieces[1], 1, 1) != '.')) {
			$second = $pieces[1];
		} else {
			$second = '';
		}
		try {
			/* @var $db clsDbAccess */
			$db = clsDbAccess::Connect('INPUT');
			$sql ="SELECT citationID
				   FROM tbl_lit l
					LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
					LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
					LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
				   WHERE (la.autor LIKE " . $db->quote ($autor . '%') . "
					   OR le.autor LIKE " . $db->quote ($autor . '%') . ")";
			if ($second) {
				$sql .= " AND (l.jahr LIKE " . $db->quote ($second . '%') . "
							OR l.titel LIKE " . $db->quote ($second . '%') . "
							OR lp.periodical LIKE " . $db->quote ($second . '%') . ")";
			}
			$sql .= " ORDER BY la.autor, jahr, lp.periodical, vol, part, pp";
			/* @var $dbst PDOStatement */
			$dbst = $db->query($sql);
			$rows = $dbst->fetchAll();
			if (count($rows) > 0) {
				$display = clsDisplay::Load();
				foreach ($rows as $row) {
					$results[] = array('id'	=> $row['citationID'],
									   'label' => $display->protolog($row['citationID'], true),
									   'value' => $display->protolog($row['citationID'], true),
									   'color' => '');
				}
			}
		}
		catch (Exception $e) {
			error_log($e->getMessage());
		}
	}

	return $results;
}


/**
 * autocomplete a periodical entry field
 *
 * @param string $value text to search for
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function periodical ($value)
{
	$results = array();
	if ($value && strlen($value) > 1) {
		$pieces = explode(" <", $value);
		try {
			/* @var $db clsDbAccess */
			$db = clsDbAccess::Connect('INPUT');
			/* @var $dbst PDOStatement */
			$dbst = $db->query("SELECT periodical, periodicalID
								FROM tbl_lit_periodicals
								WHERE periodical LIKE " . $db->quote ($pieces[0] . '%') . "
								 OR periodical_full LIKE " . $db->quote ('%' . $pieces[0] . '%') . "
								ORDER BY periodical");
			$rows = $dbst->fetchAll();
			if (count($rows) > 0) {
				foreach ($rows as $row) {
					$results[] = array('id'	=> $row['periodicalID'],
									   'label' => $row['periodical'] . " <" . $row['periodicalID'] . ">",
									   'value' => $row['periodical'] . " <" . $row['periodicalID'] . ">",
									   'color' => '');
				}
			}
		}
		catch (Exception $e) {
			error_log($e->getMessage());
		}
	}

	return $results;
}


/**
 * autocomplete a family entry field
 *
 * @param string $value text to search for
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function family ($value)
{
	$results = array();
	if ($value && strlen($value) > 1) {
		$pieces = explode(" ", $value);
		try {
			/* @var $db clsDbAccess */
			$db = clsDbAccess::Connect('INPUT');
			/* @var $dbst PDOStatement */
			$dbst = $db->query("SELECT family, familyID, category
								FROM tbl_tax_families tf
								 LEFT JOIN tbl_tax_systematic_categories tsc ON tsc.categoryID = tf.categoryID
								WHERE family LIKE " . $db->quote ($pieces[0] . '%') . "
								ORDER BY family");
			$rows = $dbst->fetchAll();
			if (count($rows) > 0) {
				foreach ($rows as $row) {
					$results[] = array('id'	=> $row['familyID'],
									   'label' => $row['family'] . " " . $row['category'] . " <" . $row['familyID'] . ">",
									   'value' => $row['family'] . " " . $row['category'] . " <" . $row['familyID'] . ">",
									   'color' => '');
				}
			}
		}
		catch (Exception $e) {
			error_log($e->getMessage());
		}
	}

	return $results;
}


/**
 * autocomplete a genus entry field
 *
 * @param string $value text to search for
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function genus ($value)
{
	$results = array();
	if ($value && strlen($value)>1) {
		$pieces = explode(" ",$value);
		try {
			/* @var $db clsDbAccess */
			$db = clsDbAccess::Connect('INPUT');
			/* @var $dbst PDOStatement */
			$dbst = $db->query("SELECT tg.genus, tg.genID, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, ta.author, tf.family, tsc.category
								FROM tbl_tax_genera tg
								 LEFT JOIN tbl_tax_authors ta ON ta.authorID = tg.authorID
								 LEFT JOIN tbl_tax_families tf ON tg.familyID = tf.familyID
								 LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID = tsc.categoryID
								WHERE genus LIKE " . $db->quote ($pieces[0] . '%') . "
								ORDER BY tg.genus");
			$rows = $dbst->fetchAll();
			if (count($rows) > 0) {
				foreach ($rows as $row) {
					$text = $row['genus'] . " " . $row['author'] . " " . $row['family'] . " "
						  . $row['category'] . " " . $row['DallaTorreIDs'] . $row['DallaTorreZusatzIDs']
						  . " <" . $row['genID'] . ">";
					$results[] = array('id'	=> $row['genID'],
									   'label' => $text,
									   'value' => $text,
									   'color' => '');
				}
				foreach ($results as $k => $v) {
					$results[$k]['label'] = preg_replace("/ [\s]+/"," ",$v['label']);
					$results[$k]['value'] = preg_replace("/ [\s]+/"," ",$v['value']);
				}
			}
		}
		catch (Exception $e) {
			error_log($e->getMessage());
		}
	}

	return $results;
}


/**
 * autocomplete an epithet entry field
 *
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function epithet ($value, $noExternals = false)
{
	$results = array();
	if ($value && strlen($value)>1) {
		try {
			/* @var $db clsDbAccess */
			$db = clsDbAccess::Connect('INPUT');
			$sql = "SELECT epithet, epithetID
					FROM tbl_tax_epithets
					WHERE epithet LIKE " . $db->quote ($value . '%');
			if ($noExternals) $sql .= " AND external = 0";
			$sql .= " ORDER BY epithet";
			/* @var $dbst PDOStatement */
			$dbst = $db->query($sql);
			$rows = $dbst->fetchAll();
			if (count($rows) > 0) {
				foreach ($rows as $row) {
					$results[] = array('id'	=> $row['epithetID'],
									   'label' => $row['epithet'] . " <" . $row['epithetID'] . ">",
									   'value' => $row['epithet'] . " <" . $row['epithetID'] . ">",
									   'color' => '');
				}
			}
		}
		catch (Exception $e) {
			error_log($e->getMessage());
		}
	}

	return $results;
}


/**
 * autocomplete an epithet entry field without external entries (external=0)
 *
 * @param string $value text to search for
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function epithetNoExternals ($value)
{
	return $this->epithet($value, true);
}


/**
 * autocomplete a taxon entry field
 * If the searchstring has only one part before the separator only taxa with empty species are presented.
 * If the searchstring consists of two parts the first one is used for genus, the second one for species
 *
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @param bool[optional] $withDT adds the DallaTorre information (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function taxon ($value, $noExternals = false, $withDT = false)
{
	$results = array();
	if ($value && strlen($value) > 1) {
		$pieces = explode(chr(194) . chr(183), $value);
		$pieces = explode(" ",$pieces[0]);
		try {
			/* @var $db clsDbAccess */
			$db = clsDbAccess::Connect('INPUT');
			$sql = "SELECT taxonID, ts.external
					FROM tbl_tax_species ts
					 LEFT JOIN tbl_tax_epithets te0 ON te0.epithetID = ts.speciesID
					 LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
					 LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
					 LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
					 LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
					 LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
					 LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
					WHERE tg.genus LIKE " . $db->quote ($pieces[0] . '%');
			if ($noExternals) $sql .= " AND ts.external = 0";
			if (!empty($pieces[1])) {
				$sql .= " AND te0.epithet LIKE " . $db->quote ($pieces[1] . '%');
			} else {
				$sql .= " AND te0.epithet IS NULL";
			}
			$sql .= " ORDER BY tg.genus, te0.epithet, te1.epithet, te2.epithet, te3.epithet, te4.epithet, te5.epithet";
			/* @var $dbst PDOStatement */
			$dbst = $db->query($sql);
			$rows = $dbst->fetchAll();
			if (count($rows) > 0) {
				$display = clsDisplay::Load();
				foreach ($rows as $row) {
					$results[] = array('id'	=> $row['taxonID'],
									   'label' => $display->taxon($row['taxonID'], true, $withDT, true),
									   'value' => $display->taxon($row['taxonID'], true, $withDT, true),
									   'color' => ($row['external']) ? 'red' : '');
				}
				foreach ($results as $k => $v) {   // eliminate multiple whitespaces within the result
					$results[$k]['label'] = preg_replace("/ [\s]+/"," ",$v['label']);
					$results[$k]['value'] = preg_replace("/ [\s]+/"," ",$v['value']);
				}
			}
		}
		catch (Exception $e) {
			error_log($e->getMessage());
		}
	}

	return $results;
}


/**
 * autocomplete a taxon entry field without external entries (external=0)
 * If the searchstring has only one part before the separator only taxa with empty species are presented.
 * If the searchstring consists of two parts the first one is used for genus, the second one for species
 *
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function taxonNoExternals ($value)
{
	return $this->taxon($value, true);
}



/**
 * autocomplete a taxon entry field and include the DallaTorre information
 * If the searchstring has only one part before the separator only taxa with empty species are presented.
 * If the searchstring consists of two parts the first one is used for genus, the second one for species
 *
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function taxonWithDT ($value)
{
	return $this->taxon($value, false, true);
}

/**
 * autocomplete a taxon entry field with hybrid at the end of the list
 * If the searchstring has only one part before the separator only taxa with empty species are presented.
 * If the searchstring consists of two parts the first one is used for genus, the second one for species
 *
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function taxonWithHybrids ($value, $noExternals = false)
{
	$results = array();
	$start = microtime(true);

//$fp = fsockopen('127.0.0.1','1556',$errno,$errstr,30);

$fp = fsockopen('belisk.com','1556',$errno,$errstr,30);

if(!$fp){
	echo "ERROR: $errstr ($errno)";
}else {
	fputs($fp,"{$value}\n");
	$r='';
	while (!feof($fp)) {
		$r.=fgets($fp, 4096);
	}
	fclose($fp);
}
$stop = microtime(true);
$tr=number_format(($stop - $start), 2)."s";
$s="";
$t=explode("\n",$r);
$searchresult=array();
foreach($t as $t1){
	$t2=explode("	",$t1);
	if($t2[1]!=''){
		
		//echo "{$t2[3]}=round( 1 - {$t2[2]} / max(strlen({$value}),{$t2[0]}),3)*100;";
		$searchresult[]=array('genus'=>$t2[0], 'id'=>$t2[1],'distance'=>$t2[2],'ratio'=>$t2[3],'inline'=>$t2[4]);
	}
}

	usort($searchresult,'col2011sort_a');
	
	foreach($searchresult as $t2){
	
			$label="<div style='width:200px;float:left'>".htmlDiff($value,$t2['genus'])." {$tr}</div><div style='width:200px;float:left'>-{$t2['genus']} &lt;{$t2['id']}&gt;-</div><div style='float:left'>({$t2['ratio']}%, {$t2['distance']})</div> &nbsp;";
			if($t2['inline']=='1'){
				$label=str_replace($value,"<b>{$value}</b>",$label);
			}
			$results[] = array(
				'id'	=> $t2['id'],
				'label' => $label ,
				'value' => $t2['genus'],
				 'color' => $color
			);
	}
	
	return $results;
	
	
	
	
	
	
	
	$results = array();
	if ($value && strlen($value) > 1) {
		$pieces = explode(chr(194) . chr(183), $value);
		$pieces = explode(" ",$pieces[0]);
		try {
			$display = clsDisplay::Load();
			/* @var $db clsDbAccess */
			$db = clsDbAccess::Connect('INPUT');

			$sql = "SELECT taxonID, ts.synID, ts.external
					FROM tbl_tax_species ts
					 LEFT JOIN tbl_tax_epithets te0 ON te0.epithetID = ts.speciesID
					 LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
					 LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
					 LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
					 LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
					 LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
					 LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
					WHERE tg.genus LIKE " . $db->quote ($pieces[0] . '%');
			if ($noExternals) $sql .= " AND ts.external = 0";
			if (!empty($pieces[1])) {
				$sql .= " AND te0.epithet LIKE " . $db->quote ($pieces[1] . '%');
			} else {
				$sql .= " AND te0.epithet IS NULL";
			}
			$sql .= " ORDER BY tg.genus, te0.epithet, te1.epithet, te2.epithet, te3.epithet, te4.epithet, te5.epithet";
			/* @var $dbst PDOStatement */
			$dbst = $db->query($sql);
			$rows = $dbst->fetchAll();
			if (count($rows) > 0) {
				foreach ($rows as $row) {
					if ($row['synID']) {
						$color = 'red';
					} elseif ($row['external']) {
						$color = 'blue';
					} else {
						$color = '';
					}
					$results[] = array('id'	=> $row['taxonID'],
									   'label' => $display->taxon($row['taxonID'], true, false, true),
									   'value' => $display->taxon($row['taxonID'], true, false, true),
									   'color' => $color);
				}
			}

			$sql = "SELECT ts.taxonID, ts.synID
					FROM (tbl_tax_species ts, tbl_tax_hybrids th)
					 LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
					 LEFT JOIN tbl_tax_species tsp1 ON tsp1.taxonID = th.parent_1_ID
					 LEFT JOIN tbl_tax_epithets tep1 ON tep1.epithetID = tsp1.speciesID
					 LEFT JOIN tbl_tax_genera tgp1 ON tgp1.genID = tsp1.genID
					 LEFT JOIN tbl_tax_species tsp2 ON tsp2.taxonID = th.parent_2_ID
					 LEFT JOIN tbl_tax_epithets tep2 ON tep2.epithetID = tsp2.speciesID
					 LEFT JOIN tbl_tax_genera tgp2 ON tgp2.genID = tsp2.genID
					 LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
					 LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
					 LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
					 LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
					 LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
					 LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
					WHERE th.taxon_ID_fk = ts.taxonID
					 AND (tg.genus LIKE " . $db->quote ($pieces[0] . '%') . "
					  OR tgp1.genus LIKE " . $db->quote ($pieces[0] . '%') . "
					  OR tgp2.genus LIKE " . $db->quote ($pieces[0] . '%') . ")\n";
			if ($noExternals) $sql .= " AND ts.external = 0\n";
			if (!empty($pieces[1])) {
				$sql .= " AND (tep1.epithet LIKE " . $db->quote ($pieces[1] . '%') . "
						   OR tep2.epithet LIKE " . $db->quote ($pieces[1] . '%') . ")\n";
			}
			$sql .= "ORDER BY tg.genus, tep1.epithet, tgp2.genus, tep2.epithet";
			/* @var $dbst PDOStatement */
			$dbst = $db->query($sql);
			$rows = $dbst->fetchAll();
			if (count($rows) > 0) {
				foreach ($rows as $row) {
					if ($row['synID']) {
						$color = 'red';
					} elseif ($row['external']) {
						$color = 'blue';
					} else {
						$color = '';
					}
					$results[] = array('id'	=> $row['taxonID'],
									   'label' => $display->taxonWithHybrids($row['taxonID'], true, true),
									   'value' => $display->taxonWithHybrids($row['taxonID'], true, true),
									   'color' => $color);
				}
			}

			foreach ($results as $k => $v) {   // eliminate multiple whitespaces within the result
				$results[$k]['label'] = preg_replace("/ [\s]+/"," ",$v['label']);
				$results[$k]['value'] = preg_replace("/ [\s]+/"," ",$v['value']);
			}
		}
		catch (Exception $e) {
			error_log($e->getMessage());
		}
	}

	return $results;
}

/**
 * autocomplete a taxon entry field without external entries (external=0)
 * If the searchstring has only one part before the separator only taxa with empty species are presented.
 * If the searchstring consists of two parts the first one is used for genus, the second one for species
 *
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function taxonWithHybridsNoExternals ($value)
{
	return $this->taxonWithHybrids($value, true);
}

/**
 * autocomplete a series entry field
 *
 * @param string $value text to search for
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function series ($value)
{
	$results = array();
	if ($value && strlen($value) > 1) {
		$pieces = explode(" <", $value);
		try {
			/* @var $db clsDbAccess */
			$db = clsDbAccess::Connect('INPUT');
			$sql = "SELECT series, seriesID
					FROM tbl_specimens_series
					WHERE series LIKE " . $db->quote ( '%' . $pieces[0] . '%') . "
					ORDER BY series";
			/* @var $dbst PDOStatement */
			$dbst = $db->query($sql);
			$rows = $dbst->fetchAll();
			if (count($rows) > 0) {
				foreach ($rows as $row) {
					$results[] = array(
                                            'id' => $row['seriesID'],
                                            'label' => $row['series'] . " <" . $row['seriesID'] . ">",
                                            'value' => $row['series'] . " <" . $row['seriesID'] . ">",
                                            'color' => '');
				}
			}
		}
		catch (Exception $e) {
			error_log($e->getMessage());
		}
	}

	return $results;
}


/***********************\
|					   |
|  protected functions  |
|					   |
\***********************/

/*********************\
|					 |
|  private functions  |
|					 |
\*********************/

private function __clone () {}


}