<?php

class BridgeBB
{
    public static function login($username, $password)
    {
        if (self::validateSession(['username'=>$username])) {
            return self::_success(LOGIN_SUCCESS, self::autologin());
        }

        if (is_null($password)) {
            return self::_error(LOGIN_ERROR_PASSWORD, 'NO_PASSWORD_SUPPLIED');
        }
        if (is_null($username)) {
            return self::_error(LOGIN_ERROR_USERNAME, 'LOGIN_ERROR_USERNAME');
        }

        return self::_apiValidate($username, $password);
    }

    public static function autologin()
    {
        try {
            $request = self::_makeApiRequest([],'GET');
            $oResponse = json_decode($request, true);

            if (isset($oResponse['data']['username']) && isset($oResponse['code'])) {
                if ($oResponse['code'] === '200' && $oResponse['data']['username']) {
                    $row = BridgeBBDBAL::getUserByUsername($oResponse['data']['username']);
                    return ($row)?$row:[];
                }
            }
            return [];
        } catch (Exception $e) {
            return [];
        }
    }

    public static function validateSession($user_row)
    {
        try {
            $request = self::_makeApiRequest([],'GET');
            $oResponse = json_decode($request, true);

            if (isset($oResponse['data']['username']) && isset($oResponse['code'])) {
                if ($oResponse['code'] === '200' && $oResponse['data']['username']) {
                    return ($user_row['username'] == $oResponse['data']['username'])?true:false;
                }
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function logOut($user_row)
    {
        try {
            if (self::validateSession($user_row)) {
                self::_makeApiRequest([],'DELETE');
            }
        } catch (Exception $e) {
        }
    }

    private static function _makeApiRequest($data,$method) {
        $ch = curl_init();
        $cooks = '';
        foreach ($_COOKIE as $k=>$v) {
            $cooks .= $k.'='.$v.';';
        }

        $curlConfig = [
            CURLOPT_URL            => LARAVEL_URL.'/auth-bridge/login',
            CURLOPT_COOKIESESSION  => true,
            CURLOPT_COOKIE         => $cooks,
            CURLINFO_HEADER_OUT    => true,
            CURLOPT_HEADERFUNCTION => 'curlResponseHeaderCallback',
            CURLOPT_RETURNTRANSFER => true
        ];

        if ($method == 'POST') {
            $curlConfig[CURLOPT_POST] = true;
            $curlConfig[CURLOPT_POSTFIELDS] = $data;
        } elseif ($method == 'DELETE') {
            $curlConfig[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        }

        curl_setopt_array($ch, $curlConfig);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    private static function _apiValidate($username, $password)
    {
        try {
            $postdata = http_build_query(
                array(
                    'appkey' => BRIDGEBB_API_KEY,
                    'username' => $username,
                    'password' => $password
                )
            );
            $request = self::_makeApiRequest($postdata,'POST');

            $oResponse = json_decode($request, true);
            if ($oResponse['code'] === '200') {
                return self::_handleAuthSuccess($username, $password, $oResponse['data']);
            } else {
                return self::_error(LOGIN_ERROR_USERNAME, 'LOGIN_ERROR_USERNAME');
            }
        } catch (Exception $e) {
            return self::_error(LOGIN_ERROR_EXTERNAL_AUTH, $e->getMessage());
        }
    }

    private static function _handleAuthSuccess($username, $password, $user_laravel)
    {
        $row = BridgeBBDBAL::getUserByUsername($username);
        // Does User exist?
        if ($row) {
            // User inactive
            if ($row['user_type'] == USER_INACTIVE || $row['user_type'] == USER_IGNORE) {
                return self::_error(LOGIN_ERROR_ACTIVE, 'ACTIVE_ERROR', $row);
            } else {
                // Session hack
                header("Location: http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                die();
//                return self::_success(LOGIN_SUCCESS, $row);
            }
        } else {
            // this is the user's first login so create an empty profile
            user_add(self::createUserRow($username, sha1($password), $user_laravel));
            // Session hack
            header("Location: http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            die();
//            return self::_success(LOGIN_SUCCESS_CREATE_PROFILE, $newUser);
        }
    }

    public static function createUserRow($username, $password, $user_laravel)
    {
        global $user;
        // first retrieve default group id
        $row = BridgeBBDBAL::getDefaultGroupID();
        if (!$row) {
            trigger_error('NO_GROUP');
        }

        // generate user account data
        $userRow = array(
            'username' => $username,
            'user_password' => phpbb_hash($password),
            'group_id' => (int) $row['group_id'],
            'user_type' => USER_NORMAL,
            'user_ip' => $user->ip,
        );

        if (LARAVEL_CUSTOM_USER_DATA && $laravel_fields = unserialize(LARAVEL_CUSTOM_USER_DATA)) {
            foreach ($laravel_fields as $key => $value) {
                if (isset($user_laravel[$key])) {
                    $userRow[$value] = $user_laravel[$key];
                }
            }
        }
        return $userRow;
    }

    private static function _error($status, $message, $row = array('user_id' => ANONYMOUS))
    {
        return array(
            'status' => $status,
            'error_msg' => $message,
            'user_row' => $row,
        );
    }

    private static function _success($status, $row)
    {
        return array(
            'status' => $status,
            'error_msg' => false,
            'user_row' => $row,
        );
    }
}

function curlResponseHeaderCallback($ch, $headerLine) {
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headerLine, $matches);
    foreach($matches[1] as $item) {
        parse_str($item, $cookie);
        setcookie(key($cookie), $cookie[key($cookie)], time() + 86400, "/");
    }
    return strlen($headerLine); // Needed by curl
}
