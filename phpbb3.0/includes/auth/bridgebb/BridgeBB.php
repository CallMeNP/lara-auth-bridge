<?php

class BridgeBB
{
    public static function login($username, $password)
    {
        if (is_null($password)) {
            return self::_error(LOGIN_ERROR_PASSWORD, 'NO_PASSWORD_SUPPLIED');
        }
        if (is_null($username)) {
            return self::_error(LOGIN_ERROR_USERNAME, 'LOGIN_ERROR_USERNAME');
        }

        return self::_apiValidate($username, $password);
    }

    private static function _apiValidate($username, $password)
    {
        $request = file_get_contents(LARAVEL_URL.'/login/'.BRIDGEBB_API_KEY.'/'.$username.'/'.$password, 'r');
        $oResponse = json_decode($request, true);
        if ($oResponse['code'] === '200') {
            //TODO: Consume returned user account information like email
            return self::_handleAuthSuccess($username, $password);
        } else {
            return self::_error(LOGIN_ERROR_USERNAME, 'LOGIN_ERROR_USERNAME');
        }
    }

    private static function _handleAuthSuccess($username, $password)
    {
        $row = BridgeBBDBAL::getUserByUsername($username);
        // Does User exist?
        if ($row) {
            // User inactive
            if ($row['user_type'] == USER_INACTIVE || $row['user_type'] == USER_IGNORE) {
                return self::_error(LOGIN_ERROR_ACTIVE, 'ACTIVE_ERROR', $row);
            } else {
                return self::_success(LOGIN_SUCCESS, $row);
            }
        } else {
            // this is the user's first login so create an empty profile
            $newUser = self::createUserRow($username, sha1($password));

            return self::_success(LOGIN_SUCCESS_CREATE_PROFILE, $newUser);
        }
    }

    public static function createUserRow($username, $password)
    {
        global $user;
        // first retrieve default group id
        $row = BridgeBBDBAL::getDefaultGroupID();
        if (!$row) {
            trigger_error('NO_GROUP');
        }

        // generate user account data
        return array(
            'username' => $username,
            'user_password' => phpbb_hash($password),
            'user_email' => '', //TODO: Set this from the laravel users later
            'group_id' => (int) $row['group_id'],
            'user_type' => USER_NORMAL,
            'user_ip' => $user->ip,
        );
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
