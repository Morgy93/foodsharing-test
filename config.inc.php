<?php


// TODO: sanitize env name
// TODO: maybe have a default env?
// TODO: check if there is not already a concept of app environment elsewhere

$FS_ENV = getenv('FS_ENV');
$env_filename = 'config.inc.' . $FS_ENV . '.php';

if (file_exists($env_filename)) {
	require_once $env_filename;
} else {
	die('no config found for env [' . $FS_ENV . ']');
}
if (!defined('SOCK_URL')) {
	define('SOCK_URL', 'http://127.0.0.1:1338/');
}

date_default_timezone_set("Europe/Berlin");

/*
 * Configure Raven (sentry.io client) for remote error reporting
 */

if (defined('SENTRY_URL')) {
	$client = new Raven_Client(SENTRY_URL);
	$client->install();
	$client->tags_context(array('FS_ENV' => $FS_ENV));
}
