<?PHP

class MapLines{
	// 
	/*function __construct(){
	
	}*/
	
	function execFunction($function, $params){

		if(method_exists($this,$function)){
			#try {
				doQuotes($params,1);
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

	function getMapLines($p,$emptyRightIsZero=false, $onlyRightCollumn=false){
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
}
?>