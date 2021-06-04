<?PHP

// Natural ID
class natID{
	var $natID;
	var $map;
	var $token=':';
	function __construct($map=array()){
		$this->map=array_flip($map);
	}
	
	function setNatID($natID){
		$this->natID=array();
		foreach($natID as $id=>$val){
			if(isset($this->map[$id])){
				$this->natID[$id]=$val;
			}
		}
	}
	function setNatIDFromString($natID){
		$order=array_flip($this->map);
		$natID=explode($this->token,$natID);
		$this->natID=array();
		foreach($order as $ord=>$id){
			$this->natID[$id]=$natID[$ord];
		}
	}
	function toString($natID=array()){
		$order=array_flip($this->map);
		$str='';
		$natID=(count($natID)>0)?$natID:$this->natID;
		foreach($order as $id){
			if(isset($natID[$id])){
				$str.=$this->token.$natID[$id];
			}else{
				$str.=$this->token;
			}
		}
		return substr($str,1);
	}
	
	function checkID($natID=array()){
		$order=array_flip($this->map);
		$natID=(count($natID)>0)?$natID:$this->natID;
		foreach($order as $ord=>$id){
			if($natID[$id]==''){
				return false;
			}
		}
		return true;
	}
	
	function getWhere($natID=array(),$alias=''){
		$where='';
		$natID=(count($natID)>0)?$natID:$this->natID;
		if(count($natID)==0)return false;
		foreach($natID as $col=>$val){
			$where.=" and {$alias}{$col}='{$val}'";
		}
		$where=substr($where,4);
		return $where;
	}
	
	function getIDFields($natID=array(),$alias=''){
		$insert='';
		$natID=(count($natID)>0)?$natID:$this->natID;
		foreach($natID as $col=>$val){
			$insert.=" {$alias}{$col}='{$val}',";
		}

		$insert=substr($insert,0,-1);
		return $insert;
	}	
}
?>