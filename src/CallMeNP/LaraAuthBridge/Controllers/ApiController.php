<?php
namespace CallMeNP\LaraAuthBridge\Controllers;

use Exception;
use Illuminate\Routing\Controller;

class ApiController extends Controller {

	public function getLogin($appkey, $username, $password) {
		if ($appkey !== config('lara-auth-bridge.appkey')) {
			return response()->json(['code'=>'400','msg'=>"Invalid API Key","data"=>[]]);
		}
		if($this->_validateCredentials($username, $password)){
			return response()->json(['code'=>'200','msg'=>"success","data"=>[]]);
		}
		return response()->json(['code'=>'400','msg'=>"Invalid username or password","data"=>[]]);
	}  
    private function _validateCredentials($username, $password) {
		if (Auth::validate(['name' => $username, 'password' => $password ])) {
			//TODO: Return user account information like email
			return true;
		}
		return false;
    }  

}
