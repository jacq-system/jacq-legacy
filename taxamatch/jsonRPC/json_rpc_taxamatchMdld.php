<?php
/**
 * Biological Namestring parser and comparison tool based on
 * TAXAMATCH developed by Tony Rees, November 2008 (Tony.Rees@csiro.au)
 *
 * establishes a class with the public function "getMatches" -
 * subsequently calculates the distance based on the MDLD algorithm implemented
 * as an UDF in a MYSQL environment.
 *
 * Input Namestrings seperated by LF are compared against a defined reference Nameslist
 * and results are provided in an array of matches.
 *
 * @author Johannes Schachner <joschach@ap4net.at>
 * @since 21.09.2009
 */

/**
 * this is a json RPC server established using the jsonrpcphp project
 * (http://jsonrpcphp.org/)
 *
 * it returns an array with all search results and any occurred errors
 * format of the array is as follows:
 * array (
 * 'error' => ''									   string containing all errors
 * 'result' => array (								 array of all results
 *   0 => array (									  first search
 *	 'searchtext' => '',							 given taxon to search for
 *	 'searchtextNearmatch' => '',					the changed taxon if nearmatch is on, empty otherwise
 *	 'rowsChecked' => 0,							 how many rows were checked to find the results
 *	 'type' => 'uni' | 'multi' | 'common'			uninomial (species empty), multi (species filled) or common name (distance and ration of results only in species array)
 *	 'out' => ''									 result in a formatted string (usually empty)
 *	 'database' => 'freud' | 'col' | 'faeu'		  used database (freud...virtual herbaria, col...catalog of life, faeu...fauna europaea)
 *	 'searchresult' => array (					   here come the results
 *	   0 => array (								  first genus we've found
 *		 'genus' => '',							  taxon of the genus (if found) or empty
 *		 'distance' => 0,							distance of the genus
 *		 'ratio' => 1,							   ratio of the genus (0...1)
 *		 'taxon' => '',							  full taxon of the genus (incl. family) or of the family, ...
 *		 'ID' => '',								 ID of the genus, family, ....
 *		 'species' => array (						which species have we found
 *		   0 => array (							  first found species
 *			 'name' => '',						   name of the species
 *			 'commonName' => '',					 common name of species (only present if getMatchesCommonNames was called)
 *			 'distance' => 0,						distance of the species
 *			 'ratio' => 1,						   ratio of the species (0...1)
 *			 'taxon' => '',						  full taxon of the species
 *			 'taxonID' => '',						ID of the species
 *			 'syn' => '',							taxon of the synonym (if any)
 *			 'synID' => 0,						   ID of the synonym (if any)
 *			 'synonyms' => array (				   all synonyms for the species (only filled if getMatchesWithSynonyms() is called!)
 *			   0 => array (
 *				 'equalsSign' => '=',				either '='	 or '$equiv;'
 *				 'name' => '',					   name of synonym
 *				 'status' => '',					 status (e.g. 'syn.' or 'nom. inval.')
 *				 'taxonID' => '',					taxonID of current synonym
 *				 'basID' => '',					  basID of current synonym
 *				 'synID' => '',					  synID of current synonym
 *				 'synonyms' => array (			   all synonyms of the current synonym (if any)
 *				   0 => array (					  
 *					 . . .						   same structure as 'synonyms' one level above
 *				   ),
 *			 ),
 *		   ),
 *		   1 => array (							  second species
 *		   .
 *		   .
 *		   .
 *		   ),
 *	   ),
 *	   1 => array (								  second genus
 *	   .
 *	   .
 *	   .
 *	   ),
 *	 ),
 *   ),
 *   1 => array (									  second search
 *   .
 *   .
 *   .
 *   ),
 * ),
 * )
 */
require_once('inc/jsonRPCServer.php');
require_once('inc/variables.php');


/**
 * function for automatic class loading
 *
 * @param string $class_name name of class and of file
 */
function __autoload ($class_name)
{
	if (preg_match('|^\w+$|', $class_name)) {
		$path = 'inc/' . basename($class_name) . '.php';
		if (file_exists($path)) {
			error_reporting(E_ALL);
			include($path);
		} else {
			die("The requested library $class_name could not be found.");
		}
	}
}

/**
 * taxamatchMdld service class
 *
 * @package taxamatchMdldService
 * @subpackage classes
 */
class taxamatchMdldService {

	var $registeredDataBases=array(
		'vienna'=>array(
			'name'=>'Virtual Herbarium Vienna','params'=>array('NearMatch'=>true,'showSy'=>'true')
		),
		'vienna_common'=>array(
			'name'=>'Virtual Herbarium Vienna common names','params'=>array('NearMatch'=>true,'showSy'=>'true')
		),
		'col2010ac'=>array(
			'name'=>'Catalogue of Life 2010','params'=>array('NearMatch'=>true,'showSy'=>'false')
		),
		'col2011ac'=>array(
			'name'=>'Catalogue of Life 2011','params'=>array('NearMatch'=>true,'showSy'=>'false')
		),
		'fe'=>array(
			'name'=>'Fauna Europea','params'=>array('NearMatch'=>true,'showSy'=>'false')
		),
		'fev2'=>array(
			'name'=>'Fauna Europea v2','params'=>array('NearMatch'=>true,'showSy'=>'false')
		),
	);
	
/*******************\
|                   |
|  public functions |
|                   |
\*******************/

/**
 * get all databases with options
 *
 * @param 
 * @return array result of all searches
 */
public function getDatabases(){
	return $this->registeredDataBases;
}


/**
 * get all possible matches against one or more chosend databases
 *
 * @param String $searchtext taxon string(s) to search for
 * @param bool[optional] $withNearMatch use near_match if true (default is false)
 * @param string[optional] $herbarium use this database (freud, col or faeu or all, default is freud)
 * @return array result of all searches
 */
public function getMatchesService($database='', $searchitem='', $params=array()){


	

	if(!isset($params['NearMatch'])){
		$params['NearMatch']=false;
	}
	if(!isset($params['showSy'])){
		$params['showSy']=false;
	}
	
	if($database=='vienna'){
		
		if($params['showSy']==true){
			
			return $this->getMatchesWithSynonyms($searchitem, $params['NearMatch']);

		}else{
			return $this->getMatchesFreud($searchitem, $params['NearMatch']);
		}
		
	}else if($database=='vienna_common'){
		
		if($params['showSy']==true){
			return $this->getMatchesCommonNamesWithSynonyms($searchitem, $params['NearMatch']);
		}else{
			return $this->getMatchesCommonNames($searchitem, $params['NearMatch']);
		}

	}else if($database=='col2010ac'){
		
		return $this->getMatchesCol($searchitem,$params['NearMatch']);
	
	}else if($database=='col2011ac'){
		
		return $this->getMatchesCol2011($searchitem,$params['NearMatch']);
	
	}else if($database=='fe'){
		return $this->getMatchesFaunaEuropaea($searchitem,$params['NearMatch']);
	
	}else if($database=='fev2'){
	
		return $this->getMatchesFaunaEuropaeav2($searchitem,$params['NearMatch']);
	}else{
		return $this->getMatchesMulti($searchitem,$params['NearMatch'],$database);
	}
	
	return 'd';
}


/**
 * get all possible matches against one or more chosend databases
 *
 * @param String $searchtext taxon string(s) to search for
 * @param bool[optional] $withNearMatch use near_match if true (default is false)
 * @param string[optional] $herbarium use this database (freud, col or faeu or all, default is freud)
 * @return array result of all searches
 */
public function getMatchesMulti($searchtext, $withNearMatch = false, $herbarium = 'freud'){
	if ($herbarium == 'all') $herbarium = 'freud,col,faeu';
	$herbs = explode(',', $herbarium);
	// split the input at newlines into several queries
	$searchItems = preg_split("[\n|\r]", $searchtext, -1, PREG_SPLIT_NO_EMPTY);
	// base definition of the return array
	$matches = array('error'  => '',
					 'result' => array());
	foreach ($searchItems as $searchItem) {
		foreach ($herbs as $herb) {
			switch (trim($herb)) {
				case 'freud': $match = $this->getMatchesFreud($searchItem, $withNearMatch);		 break;
				case 'col':   $match = $this->getMatchesCol($searchItem, $withNearMatch);		   break;
				case 'faeu':  $match = $this->getMatchesFaunaEuropaea($searchItem, $withNearMatch); break;
			}
			foreach ($match['result'] as $result) {  // just for safety, should be just one
				$matches['result'][] = $result;
			}
			$matches['error'] = $match['error'];
			if ($matches['error']) return $matches;  // something went wrong -> abort everything and return
		}
	}
	return $matches;
}


/**
 * get all possible matches against the virtual herbaria
 *
 * @param String $searchtext taxon string(s) to search for
 * @param bool[optional] $withNearMatch use near_match if true
 * @return array result of all searches
 */
public function getMatchesFreud ($searchtext, $withNearMatch = false)
{
	$herbarium = new cls_herbarium_freud();
	return $herbarium->getMatches($searchtext, $withNearMatch);
}


/**
 * get all possible matches against the catalogue of life
 *
 * @param String $searchtext taxon string(s) to search for
 * @param bool[optional] $withNearMatch use near_match if true
 * @return array result of all searches
 */
public function getMatchesCol ($searchtext, $withNearMatch = false)
{
	$herbarium = new cls_herbarium_col();
	return $herbarium->getMatches($searchtext, $withNearMatch);
}

/**
 * get all possible matches against the catalogue of life
 *
 * @param String $searchtext taxon string(s) to search for
 * @param bool[optional] $withNearMatch use near_match if true
 * @return array result of all searches
 */
public function getMatchesCol2011 ($searchtext, $withNearMatch = false)
{
	$herbarium = new cls_herbarium_col2011();
	return $herbarium->getMatches($searchtext, $withNearMatch);
}


/**
 * get all possible matches against the fauna europaea
 *
 * @param String $searchtext taxon string(s) to search for
 * @param bool[optional] $withNearMatch use near_match if true
 * @return array result of all searches
 */
public function getMatchesFaunaEuropaea ($searchtext, $withNearMatch = false)
{
	$herbarium = new cls_herbarium_faeu();
	return $herbarium->getMatches($searchtext, $withNearMatch);
}

/**
 * get all possible matches against the fauna europaeav2
 *
 * @param String $searchtext taxon string(s) to search for
 * @param bool[optional] $withNearMatch use near_match if true
 * @return array result of all searches
 */
public function getMatchesFaunaEuropaeav2 ($searchtext, $withNearMatch = false)
{
	
	$herbarium = new cls_herbarium_faeuv2();
	return $herbarium->getMatches($searchtext, $withNearMatch);
}


// BP, 07.2010
/**
 * get all possible matches plus the synonyms
 *
 * @param String $searchtext taxon string(s) to search for
 * @return array result of all searches including synonyms
 */
public function getMatchesWithSynonyms ($searchtext, $withNearMatch = false)
{
	$herbarium = new cls_herbarium_freud();
	return $herbarium->getMatchesWithSynonyms($searchtext, $withNearMatch);
}


public function getMatchesCommonNames ($searchtext, $withNearMatch = false)
{
	$herbarium = new cls_herbarium_freud();
	return $herbarium->getMatchesCommonNames($searchtext, $withNearMatch);
}


public function getMatchesCommonNamesWithSynonyms ($searchtext, $withNearMatch = false)
{
	$herbarium = new cls_herbarium_freud();
	return $herbarium->getMatchesCommonNamesWithSynonyms($searchtext, $withNearMatch);
}






/********************\
|					|
|  private functions |
|					|
\********************/

} // class taxamatchMdldService


// log the request
if (@mysql_connect($options['log']['dbhost'], $options['log']['dbuser'], $options['log']['dbpass']) && @mysql_select_db($options['log']['dbname'])) {
	@mysql_query("SET character set utf8");
	@mysql_query("INSERT INTO tblrpclog SET
				   http_header = '" . mysql_real_escape_string(var_export(apache_request_headers(), true)) . "',
				   http_post_data = '" . mysql_real_escape_string(file_get_contents('php://input')) . "',
				   remote_host = '" . mysql_real_escape_string($_SERVER['REMOTE_ADDR']) . "'");
	@mysql_close();
}

/**
 * implementation of the json rpc functionality
 */
$service = new taxamatchMdldService();
$ret = jsonRPCServer::handle($service);
if (!$ret) {
	echo "no request\n"
	   . "REQUEST_METHOD should be 'POST' but was: '" . $_SERVER['REQUEST_METHOD'] . "'\n"
	   . "CONTENT_TYPE should be 'application/json' but was: '" . $_SERVER['CONTENT_TYPE'] . "'";
}