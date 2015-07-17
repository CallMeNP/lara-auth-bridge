<?php

namespace CallMeNP\LaraAuthBridge\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class ApiController extends Controller
{
    public function getLogin($appkey = '', $username = '', $password = '')
    {
        if ($appkey !== config('lara-auth-bridge.appkey')) {
            return response()->json(['code' => '400', 'msg' => 'Invalid API Key', 'data' => []]);
        }
        if ($this->_validateCredentials($username, $password)) {
            return response()->json(['code' => '200', 'msg' => 'success', 'data' => []]);
        }

        return response()->json(['code' => '400', 'msg' => 'Invalid username or password', 'data' => []]);
    }
    private function _validateCredentials($username, $password)
    {
		if (strstr($username, '@')) {
            $field = [config('lara-auth-bridge.user_model.email_column')=>$username, 'password' => $password];
        } elseif (preg_match("/1[34578]{1}\d{9}$/", $username)) {
            $field = [config('lara-auth-bridge.user_model.phone_column')=>$username, 'password' => $password];
        } else {
            $field = [config('lara-auth-bridge.user_model.name_column')=>$username, 'password' => $password];
        }
        if (Auth::validate($field)) {
            //TODO: Return user account information like email
            return true;
        }

        return false;
    }
}
