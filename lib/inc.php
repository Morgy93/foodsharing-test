<?php

use Foodsharing\DI;
use Foodsharing\Lib\Cache\Caching;
use Foodsharing\Lib\Db\ManualDb;
use Foodsharing\Lib\Func;
use Foodsharing\Lib\Session\S;
use Foodsharing\Lib\View\Utils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require_once 'config.inc.php';
S::init();
if (isset($g_page_cache)) {
	$cache = new Caching($g_page_cache);
	$cache->lookup();
}

require_once 'lang/DE/de.php';
require_once 'lib/minify/JSMin.php';

error_reporting(E_ALL);

if (isset($_GET['logout'])) {
	$_SESSION['client'] = array();
	unset($_SESSION['client']);
}

$content_left_width = 5;
$content_right_width = 6;

$request = Request::createFromGlobals();
$response = new Response('--');

$func = DI::$shared->get(Func::class);
$viewUtils = DI::$shared->get(Utils::class);

$g_template = 'default';
$g_data = $func->getPostData();

$db = DI::$shared->get(ManualDb::class);

$func->addHidden('<a id="' . $func->id('fancylink') . '" href="#fancy">&nbsp;</a>');
$func->addHidden('<div id="' . $func->id('fancy') . '"></div>');

$func->addHidden('<div id="u-profile"></div>');
$func->addHidden('<ul id="hidden-info"></ul>');
$func->addHidden('<ul id="hidden-error"></ul>');
$func->addHidden('<div id="comment">' . $viewUtils->v_form_textarea('Kommentar') . '<input type="hidden" id="comment-id" name="comment-id" value="0" /><input type="hidden" id="comment-name" name="comment-name" value="0" /></div>');
$func->addHidden('<div id="dialog-confirm" title="Wirklich l&ouml;schen?"><p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span><span id="dialog-confirm-msg"></span><input type="hidden" value="" id="dialog-confirm-url" /></p></div>');
$func->addHidden('<div id="uploadPhoto"><form method="post" enctype="multipart/form-data" target="upload" action="xhr.php?f=addPhoto"><input type="file" name="photo" onchange="uploadPhoto();" /> <input type="hidden" id="uploadPhoto-fs_id" name="fs_id" value="" /></form><div id="uploadPhoto-preview"></div><iframe name="upload" width="1" height="1" src=""></iframe></div>');
//addHidden('<audio id="xhr-chat-notify"><source src="img/notify.ogg" type="audio/ogg"><source src="img/notify.mp3" type="audio/mpeg"><source src="img/notify.wav" type="audio/wav"></audio>');

$func->addHidden('<div id="fs-profile"></div>');

$userData = [
    'id' => $func->fsId(),
    'may' => S::may(),
];

if (S::may()) {
    $userData['token'] = S::user('token');
}

if ($pos = S::get('blocation')) {
    $func->jsData['location'] = [
        'lat' => floatval($pos['lat']),
        'lon' => floatval($pos['lon']),
    ];
} else {
    $func->jsData['location'] = null;
}

$func->jsData['user'] = $userData;
$func->jsData['page'] = $func->getPage();

$func->addHidden('<div id="fs-profile-rate-comment">' . $viewUtils->v_form_textarea('fs-profile-rate-msg', array('desc' => '...')) . '</div>');
