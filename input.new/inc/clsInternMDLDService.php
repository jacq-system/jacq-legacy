<?php

require_once('jsonRPCClient.php');

class clsInternMDLDService extends jsonRPCClient{
	private static $instance = null;
	private static $password=false;

	public static function Load($url,$password='',$debug=false){
		if (self::$instance == null) {
			self::$password=$password;
			self::$instance = new clsInternMDLDService($url,$debug);
		}
		return self::$instance;
	}
	
	/**
	 * generates the salted hash key
	 *
	 * @return string hashed key
	 */
	private function makeKey($password){
		$salt=substr(uniqid(mt_rand(),true),0,5);
		$key=$salt.md5($salt.md5($password).date('d.m.Y H:i'));
		return $key;
	}

	/**
	 * Performs a jsonRCP request and gets the results as an array
	 *
	 * @param string $method
	 * @param array $params
	 * @return array
	 */
	public function __call($method,$params){
		if(self::$password!=''){
			$params[]=$this->makeKey(self::$password);
		}
		
		return parent::__call($method,$params);
	}
}