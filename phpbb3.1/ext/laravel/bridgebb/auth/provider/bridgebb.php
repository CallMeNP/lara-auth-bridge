<?php

namespace {
    if (!defined('IN_PHPBB')) {
        exit;
    }

    define('LARAVEL_URL', 'http://www.example.com');
    define('BRIDGEBB_API_KEY', 'yoursecretapikey'); // Api key

    // User properties from laravel as key and phpBB as value
    define ('LARAVEL_CUSTOM_USER_DATA', serialize ([
        'email' => 'user_email',
        'dob' => 'user_birthday',
    ]));

    function curlResponseHeaderCallback($ch, $headerLine) {
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headerLine, $matches);
        foreach($matches[1] as $item) {
            parse_str($item, $cookie);
            setcookie(key($cookie), $cookie[key($cookie)], time() + 86400, "/");
        }
        return strlen($headerLine); // Needed by curl
    }
}

namespace laravel\bridgebb\auth\provider {
    class bridgebb extends \phpbb\auth\provider\base
    {

        public function __construct(\phpbb\db\driver\driver_interface $db)
        {
            $this->db = $db;
        }

        // Login method
        public function login($username, $password)
        {
            if (self::validate_session(['username'=>$username]) && $this->_getUserByUsername($username)) {
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

        // If user auth on laravel side but not in phpBB try to auto login
        public function autologin()
        {
            try {
                $request = self::_makeApiRequest([],'GET');
                $oResponse = json_decode($request, true);

                if (isset($oResponse['data']['username']) && isset($oResponse['code'])) {
                    if ($oResponse['code'] === '200' && $oResponse['data']['username']) {
                        $row = $this->_getUserByUsername($oResponse['data']['username']);
                        return ($row)?$row:[];
                    }
                }
                return [];
            } catch (Exception $e) {
                return [];
            }
        }

        // Validates the current session.
        public function validate_session($user_row)
        {
            if ($user_row['username'] == 'Anonymous') return false;
            try {
                $request = self::_makeApiRequest([],'GET');
                $oResponse = json_decode($request, true);

                if (isset($oResponse['data']['username']) && isset($oResponse['code'])) {
                    if ($oResponse['code'] === '200' && $oResponse['data']['username']) {
                        return (mb_strtolower($user_row['username']) == mb_strtolower($oResponse['data']['username']))?true:false;
                    }
                }

                return false;
            } catch (Exception $e) {
                return false;
            }
        }

        // Logout
        public function logout($user_row, $new_session)
        {
            try {
                if (self::validate_session($user_row)) {
                    self::_makeApiRequest([],'DELETE');
                }
            } catch (Exception $e) {
            }
        }

        private function _makeApiRequest($data,$method) {
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

        private function _apiValidate($username, $password)
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

        private function _handleAuthSuccess($username, $password, $user_laravel)
        {
            if ($row = $this->_getUserByUsername($username)) {
                // User inactive
                if ($row['user_type'] == USER_INACTIVE || $row['user_type'] == USER_IGNORE) {
                    return self::_error(LOGIN_ERROR_ACTIVE, 'ACTIVE_ERROR', $row);
                } else {
                    // Session hack
                    header("Location: http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                    die();
                    //return self::_success(LOGIN_SUCCESS, $row);
                }
            } else {
                // this is the user's first login so create an empty profile
                user_add(self::_createUserRow($username, sha1($password), $user_laravel));
                // Session hack
                header("Location: http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                die();
                //return self::_success(LOGIN_SUCCESS_CREATE_PROFILE, $newUser);
            }
        }

        private function _createUserRow($username, $password, $user_laravel)
        {
            global $user;
            // first retrieve default group id
            $row = $this->_getDefaultGroupID();
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

        private function _error($status, $message, $row = array('user_id' => ANONYMOUS))
        {
            return array(
                'status' => $status,
                'error_msg' => $message,
                'user_row' => $row,
            );
        }

        private function _success($status, $row)
        {
            return array(
                'status' => $status,
                'error_msg' => false,
                'user_row' => $row,
            );
        }

        private function _getUserByUsername($username)
        {
            global $db;
            $username = mb_strtolower($username);
            $sql = 'SELECT *
                FROM '.USERS_TABLE."
                WHERE LOWER(username) = '".$db->sql_escape($username)."'";
            $result = $db->sql_query($sql);
            $row = $db->sql_fetchrow($result);
            $db->sql_freeresult($result);
            return $row;
        }

        private function _getDefaultGroupID()
        {
            global $db;
            $sql = 'SELECT group_id
            FROM '.GROUPS_TABLE."
            WHERE group_name = '".$db->sql_escape('REGISTERED')."'
                AND group_type = ".GROUP_SPECIAL;
            $result = $db->sql_query($sql);
            $row = $db->sql_fetchrow($result);
            $db->sql_freeresult($result);

            return $row;
        }
    }
}