based on https://github.com/r-a-stone/Laravel-Auth-Bridge

rewrite controller in laravel5.0's way, and api response in my team spec.

but don't know how to merge back...

so, change the namespace for submit to packagist.org

thanks for r-a-stone's work, I'll make some pull requests later.

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
'username-column' => 'user_login',
'password-column' => 'user_password'
```

#### phpBB3.0
##### copy files 
Copy all files in the phpBB3.0 directory to your phpBB install
##### edit config
Edit the file located at {PHPBB-ROOT}/includes/auth/auth_bridgebb.php
``` php
define('LARAVEL_URL', 'http://www.example.com/auth-bridge'); //your laravel application's url
define('BRIDGEBB_API_KEY', "yoursecretapikey"); //the same key you created earlier
```
###### setting
Login to the phpBB admin panel and set bridgebb as the authentication module
