<?php

if (!defined('IN_PHPBB')) {
    exit;
}

define('LARAVEL_URL', 'http://www.example.com');  // Url of Laravel project
define('BRIDGEBB_API_KEY', 'yoursecretapikey'); // Api key

// User properties from laravel as key and phpBB as value
define ('LARAVEL_CUSTOM_USER_DATA', serialize ([
    'email' => 'user_email',
    'dob' => 'user_birthday',
]));

require __DIR__.'/bridgebb/BridgeBBDBAL.php';
require __DIR__.'/bridgebb/BridgeBB.php';

// Login method
function login_bridgebb($username, $password)
{
    return Bridgebb::login($username, $password);
}

// If user auth on laravel side but not in phpBB try to auto login
function autologin_bridgebb()
{
    return Bridgebb::autologin();
}

// Validates the current session.
function validate_session_bridgebb($user_row)
{
    if ($user_row['username'] == 'Anonymous') return false;
    return Bridgebb::validateSession($user_row);
}

// Logout
function logout_bridgebb($user_row)
{
    Bridgebb::logOut($user_row);
}