<?php

// Natural ID
class natID {

    public $map;
    public $natID = array();
    public $token = ':';

    function __construct($map = array())
    {
		$this->map = array_flip($map);
	}

	function setNatID($natID)
    {
		$this->natID = array();
		foreach($natID as $id => $val){
			if (isset($this->map[$id])){
				$this->natID[$id] = $val;
			}
		}
	}

	function setNatIDFromString($natID)
    {
		$order = array_flip($this->map);
		$natIDparts = explode($this->token, $natID);
		$this->natID = array();
		foreach ($order as $ord => $id){
			$this->natID[$id] = $natIDparts[$ord];
		}
	}

    function toString($natID = array())
    {
    	$order = array_flip($this->map);
    	$str='';
        if (count($natID) == 0) {
            $natID = $this->natID;
        }
    	foreach ($order as $id){
    		if (isset($natID[$id])){
                $str .= $this->token . $natID[$id];
            } else {
                $str .= $this->token;
            }
    	}
    	return substr($str, 1);
    }

	function checkID($natID = array())
    {
		$order = array_flip($this->map);
        if (count($natID) == 0) {
            $natID = $this->natID;
        }
		if (count($natID) == 0) {
            return false;
        }
		foreach ($order as $id){
			if ($natID[$id] == '') {
				return false;
			}
		}
		return true;
	}

	function getWhere($natID = array(), $alias = '')
    {
		$where = '';
        if (count($natID) == 0) {
            $natID = $this->natID;
        }
		if (count($natID) == 0) {
            return false;
        }
		foreach($natID as $col=>$val){
			$where .= " AND {$alias}{$col} = '{$val}'";
		}
		return substr($where, 4);
	}

	function getIDFields($natID = array(), $alias = '')
    {
		$insert = '';
        if (count($natID) == 0) {
            $natID = $this->natID;
        }
		foreach($natID as $col => $val){
			$insert .= " {$alias}{$col} = '{$val}',";
		}

		return substr($insert, 0, -1);
	}
}