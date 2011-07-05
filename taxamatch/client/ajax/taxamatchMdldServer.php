<?php
require_once('../inc/jsonRPCClient.php');
require_once('../inc/variables.php');   // BP, 07.2010

/**
 * array of known functions
 * the key is the function name
 * the value is an array with first item beeing the number of neccessary parameters
 * and the second the number of possible parameters
 */
$knownFunctions = array(
	'showMatchJsonRPC' => array(1, 1),
	'dumpMatchJsonRPC' => array(1, 1)
);

$funcName=$_POST['function'];

if (isset($knownFunctions[$funcName])) {
	echo call_user_func_array($funcName, array($_POST) );
}


function showMatchJsonRPC($formData){
	global $options;		// BP, 07.2010

	if($formData['database']=='extern'){
		$formData['database']=$formData['database_extern'];
	}
	
	$debug=($formData['debug'])?$formData['debug']:0;
	
	$start = microtime(true);

	$searchtext = ucfirst(trim($formData['searchtext']));
	if (substr($searchtext, 0, 3) == chr(0xef) . chr(0xbb) . chr(0xbf)) $searchtext = substr($searchtext, 3);

	$useNearMatch = (!empty($formData['nearmatch'])) ? true : false;
	$showSynonyms = (!empty($formData['showSyn'])) ? true : false;		  // BP, 07.2010: synonyms?

	// BP, 07.2010: get IP-address of JSON-service from 'variables.php'
	//$service = new jsonRPCClient('http://131.130.131.9/taxamatch/json_rpc_taxamatchMdld.php');
	$url = $options['hostAddr'] . "json_rpc_taxamatchMdld.php";
	$service = new jsonRPCClient($url,$debug);

	try {
		$matchesNearMatch = array();
		$matches=array();
		
		if ($formData['database'] == 'vienna') {
		//getMatches('vienna',$searchtext,$showSynonyms,$useNearMatch)
			$matches = $service->getMatchesService('vienna',$searchtext,array('showSyn'=>$showSynonyms,'NearMatch'=>false));
			
			if ($useNearMatch) {
				$matchesNearMatch=$service->getMatchesService('vienna',$searchtext,array('showSyn'=>false,'NearMatch'=>true));
			}
			
		}else if ($formData['database'] == 'vienna_common') {
			
			$matches = $service->getMatchesService('vienna_common',$searchtext,array('showSyn'=>$showSynonyms,'NearMatch'=>false));
			
			if ($useNearMatch) {
				$matchesNearMatch=$service->getMatchesService('vienna_common',$searchtext,array('showSyn'=>false,'NearMatch'=>true));
			}
			
		} else if ($formData['database'] == 'col2010ac') {
			
			$matches = $service->getMatchesService('col2010ac',$searchtext,array('NearMatch'=>false));
			
			if ($useNearMatch) {
				$matchesNearMatch=$service->getMatchesService('col2010ac',$searchtext,array('NearMatch'=>true));
			}

		} else if ($formData['database'] == 'col2011ac') {
			
			$matches = $service->getMatchesService('col2011ac',$searchtext,array('NearMatch'=>false));
			
			if ($useNearMatch) {
				$matchesNearMatch=$service->getMatchesService('col2011ac',$searchtext,array('NearMatch'=>true));
			}
			

		} else if ($formData['database'] == 'fe') {
			
			$matches = $service->getMatchesService('fe',$searchtext,array('NearMatch'=>false));
			
			if ($useNearMatch) {
				$matchesNearMatch=$service->getMatchesService('fe',$searchtext,array('NearMatch'=>true));
			}
		
		} else if ($formData['database'] == 'fev2') {
			
			$matches = $service->getMatchesService('fev2',$searchtext,array('NearMatch'=>false));
			
			if ($useNearMatch) {
				$matchesNearMatch=$service->getMatchesService('fev2',$searchtext,array('NearMatch'=>true));
			}

		} else{
			
			$matches = $service->getMatchesService($formData['database'],$searchtext,array('showSyn'=>false,'NearMatch'=>false));
			
			if ($useNearMatch) {
				$matchesNearMatch=$service->getMatchesService($formData['database'],$searchtext,array('showSyn'=>false,'NearMatch'=>true));
			}
		}

		$stop = microtime(true);

		// BP, 07.2010: invoke function for generating formatted output-string
		$out = prettyPrintMatches(
			$matches,$start,$stop,
			$formData,$searchtext,$showSynonyms,
			$matchesNearMatch,$useNearMatch
		);


	}
	catch (Exception $e) {
		$out =  "Fehler " . nl2br($e);
	}

	return $out;
}

function dumpMatchJsonRPC($formData){
	global $options;	// BP, 07.2010
	
	if($formData['database']=='extern'){
		$formData['database']=$formData['database_extern'];
	}
	
	$searchtext = ucfirst(trim($formData['searchtext']));
	if (substr($searchtext, 0, 3) == chr(0xef) . chr(0xbb) . chr(0xbf)) $searchtext = substr($searchtext, 3);

	$showSynonyms = (!empty($formData['showSyn'])) ? true : false;		// BP, 08/2010: correction!

	// BP, 07.2010
	//$service = new jsonRPCClient('http://131.130.131.9/taxamatch/json_rpc_taxamatchMdld.php');
	$url = $options['hostAddr'] . "json_rpc_taxamatchMdld.php";
	$service = new jsonRPCClient($url,1);

	try {
		$matchesNearMatch = array();
		$matches=array();
		
		if ($formData['database'] == 'vienna') {
		//getMatches('vienna',$searchtext,$showSynonyms,$useNearMatch)
			$matches = $service->getMatchesService('vienna',$searchtext,array('showSyn'=>$showSynonyms,'NearMatch'=>false));
			
			if ($useNearMatch) {
				$matchesNearMatch=$service->getMatchesService('vienna',$searchtext,array('showSyn'=>false,'NearMatch'=>true));
			}
			
		}else if ($formData['database'] == 'vienna_common') {
			
			$matches = $service->getMatchesService('vienna_common',$searchtext,array('showSyn'=>$showSynonyms,'NearMatch'=>false));
			
			if ($useNearMatch) {
				$matchesNearMatch=$service->getMatchesService('vienna_common',$searchtext,array('showSyn'=>false,'NearMatch'=>true));
			}
			
		} else if ($formData['database'] == 'col2010ac') {
			
			$matches = $service->getMatchesService('col2010ac',$searchtext,array('NearMatch'=>false));
			
			if ($useNearMatch) {
				$matchesNearMatch=$service->getMatchesService('col2010ac',$searchtext,array('NearMatch'=>true));
			}

		} else if ($formData['database'] == 'col2011ac') {
			
			$matches = $service->getMatchesService('col2011ac',$searchtext,array('NearMatch'=>false));
			
			if ($useNearMatch) {
				$matchesNearMatch=$service->getMatchesService('col2011ac',$searchtext,array('NearMatch'=>true));
			}
			

		} else if ($formData['database'] == 'fe') {
			
			$matches = $service->getMatchesService('fe',$searchtext,array('NearMatch'=>false));
			
			if ($useNearMatch) {
				$matchesNearMatch=$service->getMatchesService('fe',$searchtext,array('NearMatch'=>true));
			}
		
		} else if ($formData['database'] == 'fev2') {
			
			$matches = $service->getMatchesService('fev2',$searchtext,array('NearMatch'=>false));
			
			if ($useNearMatch) {
				$matchesNearMatch=$service->getMatchesService('fev2',$searchtext,array('NearMatch'=>true));
			}

		} else{
			
			$matches = $service->getMatchesService($formData['database'],$searchtext,array('showSyn'=>false,'NearMatch'=>false));
			
			if ($useNearMatch) {
				$matchesNearMatch=$service->getMatchesService($formData['database'],$searchtext,array('showSyn'=>false,'NearMatch'=>true));
			}
		}

		$out = "<big><b>Dump or Results for search for '" . nl2br($searchtext) . "':</b></big><br>\n"
			 . "<pre>" . var_export($matches, true) . "\n" . var_export($matchesNearMatch, true) . "</pre>\n";
	}
	catch (Exception $e) {
		$out =  "Fehler " . nl2br($e);
	}

   return $out;
}

// BP, 07.2010: pretty output of $matches.
function prettyPrintMatches($matches,$start,$stop,
							$formData,$searchtext,$showSyns,
							$matchesNearMatch=array(),$useNearMatch=false) {
							
	if (!empty($matches['error'])) {
		$out = $matches['error'];
	} elseif (!empty($matchesNearMatch['error'])) {
		$out = $matchesNearMatch['error'];
	} else {
		$out = "";
		$indexMatch = 0;
		while ($indexMatch < count($matches['result'])) {
		//foreach ($matches['result'] as $result) {
			$countResults = count($matches['result'][$indexMatch]['searchresult']);
			$countResultsNearMatch = ($useNearMatch) ? count($matchesNearMatch['result'][$indexMatch]['searchresult']) : 0;
			$out2 = '';
			$found = 0;
			$foundNearMatch = 0;
			$line = 0;
			$indexResult = 0;
			$columnLeft = $columnRight = array();
			while ($indexResult < $countResults || $indexResult < $countResultsNearMatch) {
			//foreach ($result['searchresult'] as $key => $row) {
				if ($matches['result'][$indexMatch]['type'] == 'uni') {
					if ($indexResult < $countResults) {
						$row = $matches['result'][$indexMatch]['searchresult'][$indexResult];
						$out2Left = '<td>&nbsp;&nbsp;<b>' . $row['taxon'] . ' <' . $row['ID'] . '></b></td>'
								  . '<td>&nbsp;' . $row['distance'] . '&nbsp;</td>'
								  . '<td align="right">&nbsp;' . number_format($row['ratio'] * 100, 1) . "%</td>";
						$found++;
					} else {
						$out2Left = "<td></td><td></td><td></td>";
					}
					if ($useNearMatch) {
						if ($indexResult < $countResultsNearMatch) {
							$row = $matchesNearMatch['result'][$indexMatch]['searchresult'][$indexResult];
							$out2Right = '<td>&nbsp;&nbsp;<b>' . $row['taxon'] . ' <' . $row['ID'] . '></b></td>'
									   . '<td>&nbsp;' . $row['distance'] . '&nbsp;</td>'
									   . '<td align="right">&nbsp;' . number_format($row['ratio'] * 100, 1) . "%</td>";
							$foundNearMatch++;
						} else {
							$out2Right = "<td></td><td></td><td></td>";
						}
					} else {
						$out2Right = '';
					}
					if ($line == 0) {
						$out2Firstline = array($out2Left, $out2Right);
					} else {
						$out2 .= "<tr valign='baseline'>" . $out2Left . $out2Right . "</tr>\n";
					}
					$line++;
				} else {
					if ($indexResult < $countResults) {
						$row = $matches['result'][$indexMatch]['searchresult'][$indexResult];
						foreach ($row['species'] as $key2 => $row2) {
							$commonName   = (!empty($row2['commonName'])) ? '&nbsp;&nbsp;<b>' . $row2['commonName'] . '</b><br>' : '';
							$columnLeft[] = "<td>$commonName&nbsp;&nbsp;<b>" . $row2['taxon'] . ' <' . $row2['taxonID'] . '></b>'
										  . (($row2['syn']) ? "<br>&nbsp;&nbsp;&rarr;&nbsp;" . $row2['syn'] . " <" . $row2['synID'] . ">" : "")
										  . (($showSyns) ? prettyPrintSynonyms($row2['synonyms']) : "")
										  . '</td>'
										  . '<td>&nbsp;' . $row2['distance'] . '&nbsp;</td>'
										  . '<td align="right">&nbsp;' . number_format($row2['ratio'] * 100, 1) . "%</td>";
							$found++;
						}
					}
					if ($indexResult < $countResultsNearMatch) {
						$row = $matchesNearMatch['result'][$indexMatch]['searchresult'][$indexResult];
						foreach ($row['species'] as $key2 => $row2) {
							$commonName	= (!empty($row2['commonName'])) ? '&nbsp;&nbsp;<b>' . $row2['commonName'] . '</b><br>' : '';
							$columnRight[] = "<td>$commonName&nbsp;&nbsp;<b>" . $row2['taxon'] . ' <' . $row2['taxonID'] . '></b>'
										   . (($row2['syn']) ? "<br>&nbsp;&nbsp;&rarr;&nbsp;" . $row2['syn'] . " <" . $row2['synID'] . ">" : "")
										   . '</td>'
										   . '<td>&nbsp;' . $row2['distance'] . '&nbsp;</td>'
										   . '<td align="right">&nbsp;' . number_format($row2['ratio'] * 100, 1) . "%</td>";
							$foundNearMatch++;
						}
					}
				}
				$indexResult++;
			}
			if ($columnLeft || $columnRight) {
				$out2Firstline = array((!empty($columnLeft[0]))  ? $columnLeft[0]  : "<td></td><td></td><td></td>",
									   (!empty($columnRight[0])) ? $columnRight[0] : "<td></td><td></td><td></td>");
				$line = 1;
				while ($line < count($columnLeft) || $line < count($columnRight)) {
					if ($line > 0) {
						$out2 .= "<tr valign='baseline'>";
					}
					if ($line < count($columnLeft)) {
						$out2 .= $columnLeft[$line];
					} else {
						$out2 .= "<td></td><td></td><td></td>";
					}
					if ($useNearMatch) {
						if ($line < count($columnRight)) {
							$out2 .= $columnRight[$line];
						} else {
							$out2 .= "<td></td><td></td><td></td>";
						}
					}
					$out2 .= "</tr>\n";
					$line++;
				}
			}
			if (!$found && !$foundNearMatch) {
				$out2Firstline = array("<td colspan='3'>nothing found</td>", "<td colspan='3'>nothing found</td>");
				$line++;
			}
			$out .= "<tr valign='baseline'>"
				  . "<td rowspan='$line'>"
				  . "&nbsp;&nbsp;<big><b>" . $matches['result'][$indexMatch]['searchtext'] . "</b></big>&nbsp;&nbsp;<br>\n"
				  . "&nbsp;&nbsp;$found match" . (($found > 1) ? 'es' : '') . " found&nbsp;&nbsp;<br>\n"
				  . "&nbsp;&nbsp;" . $matches['result'][$indexMatch]['rowsChecked'] . " rows checked&nbsp;&nbsp;"
				  . "</td>"
				  . $out2Firstline[0];
			if ($useNearMatch) {
				  $out .= "<td rowspan='$line'>"
						. "&nbsp;&nbsp;<big><b>" . $matchesNearMatch['result'][$indexMatch]['searchtextNearmatch'] . "</b></big>&nbsp;&nbsp;<br>\n"
						. "&nbsp;&nbsp;$foundNearMatch match" . (($foundNearMatch > 1) ? 'es' : '') . " found&nbsp;&nbsp;<br>\n"
						. "&nbsp;&nbsp;" . $matchesNearMatch['result'][$indexMatch]['rowsChecked'] . " rows checked&nbsp;&nbsp;"
						. "</td>"
						. $out2Firstline[1];
			}
			$out .= "</tr>\n"
				  . $out2;
			$indexMatch++;
		}
		$out = "<a href='taxamatchExport.php?search=" . urlencode($searchtext)
			 . "&db=" . $formData['database']
			 . "&showSyn=" . $showSyns
			 . "' target='_blank'>export csv</a><br>\n"
			 . "<big>" . number_format(($stop - $start), 2) . " seconds needed</big><br>\n"
			 . "<table rules='all' border='1'>\n"
			 . "<tr><th>&nbsp;search for&nbsp;</th><th>result</th><th>Dist.</th><th>Ratio</th>"
			 . (($useNearMatch) ? "<th>&nbsp;search for&nbsp;</th><th>result near match</th><th>Dist.n.m.</th><th>Ratio n.m.</th>" : "")
			 . "</tr>\n"
			 . $out
			 . "</table>\n";
	}
	return $out;
}

// BP, 07.2010: return a pretty print of the synonyms belonging to a species
function prettyPrintSynonyms($synonyms20) {
	$synString = "";
	for ($counter20 = 0; $counter20 < count($synonyms20); $counter20++) {
		$synString .= prettyPrintSynonymEntry($synonyms20[$counter20]);
		$synonyms40 = $synonyms20[$counter20]['synonyms'];
		for ($counter40 = 0; $counter40 < count($synonyms40); $counter40++) {
			$synString .= prettyPrintSynonymEntry($synonyms40[$counter40],2);
		}
	}
	return $synString;
}

// BP, 07.2010: return a pretty print of one synonym
function prettyPrintSynonymEntry($synonym,$indent=1) {
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