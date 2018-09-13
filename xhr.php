<?php

use Foodsharing\DI;
use Foodsharing\Lib\Cache\Caching;
use Foodsharing\Lib\Db\Mem;
use Foodsharing\Lib\Session;
use Foodsharing\Lib\Xhr\XhrMethods;

require __DIR__ . '/includes/setup.php';

require_once 'config.inc.php';

/* @var $session \Foodsharing\Lib\Session */
$session = DI::$shared->get(Session::class);
$session->initIfCookieExists();

if (isset($g_page_cache)) {
	$cache = new Caching($g_page_cache, $session);
	$cache->lookup();
}

require_once 'lang/DE/de.php';

$action = $_GET['f'];

Mem::updateActivity($session->id());
if (isset($_GET['f'])) {
	/* @var $xhr XhrMethods */
	$xhr = DI::$shared->get(XhrMethods::class);
	$func = 'xhr_' . $action;
	if (method_exists($xhr, $func)) {
		/*
		 * check for page caching
		*/
		if (isset($cache) && $cache->shouldCache()) {
			ob_start();
			echo $xhr->$func($_GET);
			$page = ob_get_contents();
			$cache->cache($page);

			ob_end_clean();

			echo $page;
		} else {
			echo $xhr->$func($_GET);
		}
	}
}
