<?php

require_once('jsonRPCServer.php');


class jsonRPCServerCustom extends jsonRPCServer{
	private static $password=false;
	private static $key=false;
	
	private function checkKey($key,$password){
		$salt=substr($key,0,5);
		$key=substr($key,5);
		
		for($i=-5;$i<=5;$i++){
			$date=date('d.m.Y H:i',mktime(date("H"),date("i")+$i,0,date("n"), date("j"),date("Y")));
			$keycalc=md5($salt.md5($password).$date);
			
			if($keycalc==$key){
				return true;
			}
		}
		return false;
	}
	
	public static function checkSecuredRequest(){
		if(!self::$password || self::$password==''){
			return true;
		}
		
		if(self::checkKey(self::$key,self::$password)===true){
				return true;
		}

		return array(
			'error' => 'Key needed for this action and false provided.'
		);
	}
	
	/**
	 * This function handle a request binding it to a given object
	 *
	 * @param object $object
	 * @return boolean
	 */
	public static function handle($object,$password=false){
		$request=json_decode(file_get_contents('php://input'),true);
		self::$key=$request['params'][count($request['params'])-1];
		self::$password=$password;
		
		return parent::handle($object);
	}
}