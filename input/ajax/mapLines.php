<?PHP

use Jacq\DbAccess;

class MapLines{
	// 
	/*function __construct(){
	
	}*/
	
	public function execFunction($function, $params){

		if(method_exists($this,$function)){
			#try {
				$this->doQuotes($params);
				$res=call_user_func_array(array($this,$function),array($params));
			#}catch (Exception $e) {
			#	$out =  "Fehler " . nl2br($e);
			#}
			
		}else{
			$res=array('sucess'=>false);
		}
		$res=json_encode($res);
		
		echo $res;
		exit;

	}

	public function getMapLines($p,$emptyRightIsZero=false, $onlyRightCollumn=false){
		$new=array();
		foreach($_POST as $k=>$v){
			if(preg_match('/acmap_r_(\d+)Index/', $k, $matches)==1){
				$x=$matches[1];
				$leftID=isset($_POST['acmap_l_'.$x.'Index'])?$_POST['acmap_l_'.$x.'Index']:'';
				$rightID=isset($_POST['acmap_r_'.$x.'Index'])?$_POST['acmap_r_'.$x.'Index']:'';
				
				$leftVal=isset($_POST['ajax_acmap_l_'.$x])?$_POST['ajax_acmap_l_'.$x]:'';
				$rightVal=isset($_POST['ajax_acmap_r_'.$x])?$_POST['ajax_acmap_r_'.$x]:'';
				
				if($onlyRightCollumn){
					if(is_numeric($rightID)){
						$new[ $rightID ]=$x;
						continue;
					}
				}else{
					if(is_numeric($leftID) && $rightID=='' && $emptyRightIsZero){
						$new[ $leftID ][ 0 ]=$x;
						continue;
						
					}else if(is_numeric($leftID) && is_numeric($rightID)){		
						$new[ $leftID ][ $rightID ]=$x;
						continue;
					}
				}
				if(($leftID=='' || $leftID=='0') && ($rightID=='' || $rightID=='0'))continue;
				$new['error'][]=array($x,$leftID,$rightID);
			}
		}
		return $new;
	}

	// from Post to escaped mysql
	private function doQuotes(&$obj): void
    {
		try {
			$dbLink = DbAccess::ConnectTo('INPUT');
		} catch (Exception $e) {
			error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
		}

		if (!is_array($obj)) {
			$obj = array($obj);
		}
		foreach ($obj as &$val) {
			if (is_array($val)) {
				$this->doQuotes($val);
			} else if (is_scalar($val)) {
				$val = $dbLink->real_escape_string(htmlspecialchars_decode($val));
			}
		}
	}

}
