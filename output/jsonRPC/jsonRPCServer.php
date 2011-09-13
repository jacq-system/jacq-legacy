<?php
/*
					COPYRIGHT

Copyright 2007 Sergio Vaccaro <sergio@inservibile.org>

This file is part of JSON-RPC PHP.

JSON-RPC PHP is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

JSON-RPC PHP is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with JSON-RPC PHP; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * This class build a json-RPC Server 1.0
 * http://json-rpc.org/wiki/specification
 *
 * @author sergio <jsonrpcphp@inservibile.org>
 */
class jsonRPCServer{
	
	private function checkKey($key){
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
	
	/**
	 * This function handle a request binding it to a given object
	 *
	 * @param object $object
	 * @return boolean
	 */
	public static function handle($object,$password=''){
		
		// checks if a JSON-RCP request has been received
		if($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] != 'application/json'){
			// This is not a JSON-RPC request
			return false;
		}

		// reads the input data
		$request=json_decode(file_get_contents('php://input'),true);

		// executes the task on local object
		try{
			$valid=false;
			
			if($password!=''){
				if(($valid=$this->checkKey($request['key']))===false){
					$response=array(
						'id' => $request['id'],
						'result' => NULL,
						'error' => 'Key needed for this action and false provided.'
					);
				}
				return $valid;
		
			}
			if($valid || $password==''){
				
				/*$params=array();
				foreach($request['params'] as $k=>$v){
					if(is_scalar($v)){
						$params[$k]=base64_decode($v);
					}else{
						$params[$k]=$v;
					}
				}*/
				$params=$request['params'];
				if($result=@call_user_func_array(array($object,$request['method']),$params)){
					$response=array(
						'id' => $request['id'],
						'result' => $result,
						'error' => NULL
					);
				}else{
					$response=array(
						'id' => $request['id'],
						'result' => NULL,
						'error' => 'unknown method or incorrect parameters'
					);
				}
			}
		}catch(Exception $e){
			$response=array(
				'id' => $request['id'],
				'result' => NULL,
				'error' => $e->getMessage()
			);
		}

		// output the response
		if(!empty($request['id'])){ // notifications don't want response
			header('content-type: text/javascript');
			echo json_encode($response);
		}

		// finish
		return true;
	}
}