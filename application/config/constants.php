<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');



/*

|--------------------------------------------------------------------------

| File and Directory Modes

|--------------------------------------------------------------------------

|

| These prefs are used when checking and setting modes when working

| with the file system.  The defaults are fine on servers with proper

| security, but you may wish (or even need) to change the values in

| certain environments (Apache running a separate process for each

| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should

| always be used to set the mode correctly.

|

*/

define('FILE_READ_MODE', 0644);

define('FILE_WRITE_MODE', 0666);

define('DIR_READ_MODE', 0755);

define('DIR_WRITE_MODE', 0777);



/*

|--------------------------------------------------------------------------

| File Stream Modes

|--------------------------------------------------------------------------

|

| These modes are used when working with fopen()/popen()

|

*/



define('FOPEN_READ',							'rb');

define('FOPEN_READ_WRITE',						'r+b');

define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care

define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care

define('FOPEN_WRITE_CREATE',					'ab');

define('FOPEN_READ_WRITE_CREATE',				'a+b');

define('FOPEN_WRITE_CREATE_STRICT',				'xb');

define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');





// Database Configuration

//define("DB_HOST", 'mysql1410.ixwebhosting.com');

//define("DB_USER", 'A995502_recipe');

//define("DB_PASSWORD", 'Arecipe123');

//define("DB_DATABASE", 'A995502_recipe');


//// client server

define("DB_HOST", 'localhost');
define("DB_USER", 'inspect@e3buildi');
define("DB_PASSWORD", 'SPfsutwEhYz2Xc');
define("DB_DATABASE", 'insptest');
/*
/// votive server
define("DB_HOST", 'localhost');
define("DB_USER", 'votivela_techtoy');
define("DB_PASSWORD", 'techtoyoullc@123');
define("DB_DATABASE", 'votivela_techtoyoullc');
*/

define("DB_PREFIX", 'ins_');



// SMTP Configuration

define('SMTP_HOST2', 'secure.emailsrvr.com');

define('SMTP_HOST', 'smtp.emailsrvr.com');

define('SMTP_USER', 'inspect@e3bldg.com');

define('SMTP_PASSWORD', 'enek8YR6yJkdSdQT');

define('SMTP_PORT', 587);

define('SMTP_FROM', "inspect@e3bldg.com");

define('SMTP_NAME', 'TESTING E3 Inspections');





//define('SMTP_HOST', 'mail.idragonit.com');

//define('SMTP_USER', 'cjh@idragonit.com');

//define('SMTP_PASSWORD', 'aifakftys');

//define('SMTP_PORT', 587);

//define('SMTP_FROM', "cjh@idragonit.com");

//define('SMTP_NAME', 'Inspection');



define('APP_TITLE', 'E3 Building Sciences Inspections Management Portal');

define('LOGO_PATH', 'https://inspdev.e3bldg.com/resource/upload/logo.png');



/* End of file constants.php */

/* Location: ./application/config/constants.php */



define('GOOGLE_MAP_KEY','AIzaSyAszaZueBap03EVZlgXCwC98VSrZix7alA');



