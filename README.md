Allows phpBB3.0(not yet 3.1) to use the Laravel5.\*(for 4.\* see [r-a-stone's work](https://github.com/r-a-stone/Laravel-Auth-Bridge)) Auth driver to create/authenticate accounts.

[![Latest Stable Version](https://poser.pugx.org/callmenp/lara-auth-bridge/v/stable)](https://packagist.org/packages/callmenp/lara-auth-bridge) [![Total Downloads](https://poser.pugx.org/callmenp/lara-auth-bridge/downloads)](https://packagist.org/packages/callmenp/lara-auth-bridge) [![License](https://poser.pugx.org/callmenp/lara-auth-bridge/license)](https://packagist.org/packages/callmenp/lara-auth-bridge)

### Installation
#### Laravel
##### run composer
``` php
composer require callmenp/lara-auth-bridge
```
##### add service provider
Register the Service Provider by adding it to your project's providers array in app.php
``` php
'providers' => array(
    'CallMeNP\LaraAuthBridge\LaraAuthBridgeServiceProvider',
);
```
##### edit config
Change configs config/lara-auth-bridge.php
``` php
// Create a secret app key in 
'appkey' => 'yoursecretapikey'

// Update the column names used for the Laravel Auth driver 
'username_column' => 'user_login',
'password_column' => 'user_password'

// Set true if you use multiAuth, false if default Laravel Auth
'client_auth' => false
```

#### phpBB3.0
##### copy files 
Copy all files in the phpBB3.0 directory to your phpBB install
##### edit config
Edit the file located at {PHPBB-ROOT}/includes/auth/auth_bridgebb.php
``` php
define('LARAVEL_URL', 'http://www.example.com'); //your laravel application's url
define('BRIDGEBB_API_KEY', "yoursecretapikey"); //the same key you created earlier
define ('LARAVEL_CUSTOM_USER_DATA', serialize ([
    'email' => 'user_email',
    'dob' => 'user_birthday',
])); // Update the columns you want to come from Laravel user to phpBB user
```
###### setting
Login to the phpBB admin panel and set bridgebb as the authentication module
