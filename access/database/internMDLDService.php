<?php
require_once('inc/jsonRPCServerCustom.php');
require_once('../inc/connect.php');


error_reporting(E_ALL);
class internMDLDService {

    // unsafe, do not use
//	function getSQLResults($sql){
//		$sc=false;
//		$res=array();
//		if(!is_array($sql)){
//			$sql=array($sql);
//			$sc=true;
//		}
//		foreach($sql as $k=>$v){
//			$resdb=dbi_query($v);
//			if($resdb){
//				while($row=mysqli_fetch_assoc($resdb)){
//					$res[$k][]=$row;
//				}
//			}
//		}
//		if($sc && isset($res[0]) ){
//			return $res[0];
//		}
//		return $res;
//	}

    function getMDLDcommonnames($uninomial, $lenlim)
    {
        global $dbLink;

        // MDLD Search for commonnames.
        $limit = 4;
        $block_limit = 2;
        $res=array();
        $uninomial = $dbLink->real_escape_string($uninomial);
        $lenlim = intval($lenlim);

        $sql="SELECT com.common_id as 'i'
              FROM
               herbar_names.tbl_name_names nam
               LEFT JOIN herbar_names.tbl_name_commons com ON com.common_id = nam.name_id
               LEFT JOIN herbar_names.tbl_name_transliterations translit ON translit.transliteration_id = nam.transliteration_id
              WHERE
              (
                   mdld('$uninomial', com.common_name, $block_limit, $limit) <  LEAST(CHAR_LENGTH(com.common_name) / 2, $lenlim)
                OR mdld('$uninomial', translit.name, $block_limit, $limit) <  LEAST(CHAR_LENGTH(translit.name) / 2, $lenlim)
              )
              LIMIT 1000";
        $resdb = dbi_query($sql);
        if($resdb){
            while ($row = mysqli_fetch_assoc($resdb)){
                $res[] = $row;
            }
        }
        return $res;
    }


	function check_checkScrutiny($author, $year)
    {
        global $dbLink;

		if(($valid=jsonRPCServerCustom::checkSecuredRequest())!==true)return $valid;

		$parts_auth=preg_split ('/-|\s|,|&|;|(\.[\w]+)/',$author,20,PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

        $authorFiltered = $dbLink->real_escape_string($author);
		$len=mb_strlen($authorFiltered,"UTF-8");
		$checks="";
		$checks.="IF($len <= CHAR_LENGTH(a.autor)+1 and $len >= CHAR_LENGTH(a.autor)-1 ,2,0) as check_a_1_2, \n";
		$checks.="IF(a.autor='{$authorFiltered}',2,0) as check_a_2_2,\n";
		$checks.="IF( mdld('{$authorFiltered}',a.autor, 3, 4)<4,2,0) as check_a_3_2,\n";

		$where="";
		$where1="";
		$where2="";

		$x=0;
		foreach($parts_auth as $apart){
            $apartFiltered = $dbLink->real_escape_string($apart);
			if(strpos($apart,".")===false && strlen($apart)>4){

				$where1.=" and a.autor LIKE '%{$apartFiltered}%'";
				$checks.="IF(INSTR(a.autor,'{$apartFiltered}' )>0 ,1,0) as check_a_4{$x}_1,\n";

			}else{

				$where2.=" or  a.autor LIKE '%{$apartFiltered}%'";
				$checks.="IF(INSTR(a.autor,'{$apartFiltered}' )>0 ,1,0) as check_a_5{$x}_1,\n";
			}
			$x++;
		}

		$where=" mdld('{$authorFiltered}',a.autor, 3, 4)<4 or ( 1=1 {$where1} and ( 1=0 {$where} {$where2} )) ";


		$years="";

		$parts_year=preg_split ('/-|\s|,|&|;|\./',$year,20,PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

		$x=0;
		foreach($parts_year as $ypart){
            $ypartFiltered = $dbLink->real_escape_string($ypart);
			$years.=" and lit.jahr like '%{$ypartFiltered}%'";
			$checks.="IF(INSTR(lit.jahr,'{$ypartFiltered}' )>0 ,5,0) as  check_l_1{$x}_5,\n";
			$x++;
		}

		$query="
SELECT

a.autorID,
a.autor,
{$checks}
autorsystbot,
lit.jahr,
lit.citationID,
CONCAT(lit.titel,', ',lit.suptitel,', ',period.periodical) as 'litinfo'


FROM
tbl_lit_authors a
CROSS JOIN tbl_lit lit ON (lit.autorID = a.autorID)
LEFT JOIN  tbl_lit_periodicals period on  period.periodicalID=lit.periodicalID
WHERE

{$where}
limit 1000
";
		$res=array();
		$resdb=dbi_query($query);
		if($resdb){
			while($row=mysqli_fetch_assoc($resdb)){
				$res[]=$row;
			}
		}

		return $res;
	}




}

// log the request
//if (@mysql_connect($options['log']['dbhost'], $options['log']['dbuser'], $options['log']['dbpass']) && @mysql_select_db($options['log']['dbname'])) {
//	@mysql_query("SET character set utf8");
//	@mysql_query("INSERT INTO tblrpclog SET
//				   http_header = '" . mysql_real_escape_string(var_export(apache_request_headers(), true)) . "',
//				   http_post_data = '" . mysql_real_escape_string(file_get_contents('php://input')) . "',
//				   remote_host = '" . mysql_real_escape_string($_SERVER['REMOTE_ADDR']) . "'");
//	@mysql_close();
//}


/**
 * implementation of the json rpc functionality
 */
$service = new internMDLDService();
$ret = jsonRPCServerCustom::handle($service,$_OPTIONS['internMDLDService']['password']);
if (!$ret) {
	echo "no request\n"
	   . "REQUEST_METHOD should be 'POST' but was: '" . $_SERVER['REQUEST_METHOD'] . "'\n"
	   . "CONTENT_TYPE should be 'application/json' but was: '" . $_SERVER['CONTENT_TYPE'] . "'";
}
