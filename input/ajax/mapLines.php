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

	function getMapLines($p,$allowEmptyRight=false, $valonlyzero=0){
		$new=array();
		foreach($_POST as $k=>$v){
			if(preg_match('/acmap_l_(\d+)Index/', $k, $matches)==1){
				$x=$matches[1];
				$leftID=$_POST['acmap_l_'.$x.'Index'];
				$rightID=$_POST['acmap_r_'.$x.'Index'];
				
				$leftVal=$_POST['ajax_acmap_l_'.$x];
				$rightVal=$_POST['ajax_acmap_r_'.$x];
				
				if(is_numeric($leftID) && $rightID=='' && $allowEmptyRight){
					$new[ $leftID ][ 0 ]=$x;
					
				}else if(is_numeric($leftID) && is_numeric($rightID)){		
					$new[ $leftID ][ $rightID ]=$x;
				}else if(is_numeric($leftID) && is_numeric($rightVal)){		
					if(!$valonlyzero || $rightVal=='0')$new[ $leftID ][ $rightVal ]=$x;
					
				}else if(is_numeric($leftVal) && is_numeric($rightID)){
					if(!$valonlyzero || $leftVal=='0')$new[ $leftVal ][ $rightID ]=$x;
					
				}else if(is_numeric($leftVal) && is_numeric($rightVal)){
					if(!$valonlyzero || ($leftVal=='0' && $rightVal=='0'))$new[ $leftVal ][ $rightVal ]=$x;
				}
				
			}
		}
		return $new;
	}
}
?>