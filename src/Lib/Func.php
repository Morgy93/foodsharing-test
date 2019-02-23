<?php

namespace Foodsharing\Lib;

use Exception;
use Flourish\fFile;
use Flourish\fImage;
use Foodsharing\Lib\Db\Mem;
use Foodsharing\Lib\Mail\AsyncMail;
use Foodsharing\Modules\Core\DBConstants\Region\Type;
use Foodsharing\Modules\Core\InfluxMetrics;
use Foodsharing\Modules\EmailTemplateAdmin\EmailTemplateAdminGateway;
use Foodsharing\Modules\Region\RegionGateway;
use Foodsharing\Services\SanitizerService;

final class Func
{
	private $ids;
	private $sanitizerService;
	private $regionGateway;
	private $emailTemplateAdminGateway;

	public $jsData = [];

	/**
	 * @var \Twig\Environment
	 */
	private $twig;

	/**
	 * @var Session
	 */
	private $session;

	/**
	 * @var Mem
	 */
	private $mem;

	/**
	 * @var InfluxMetrics
	 */
	private $metrics;

	public function __construct(
		SanitizerService $sanitizerService,
		RegionGateway $regionGateway,
		EmailTemplateAdminGateway $emailTemplateAdminGateway,
		InfluxMetrics $metrics
	) {
		$this->sanitizerService = $sanitizerService;
		$this->regionGateway = $regionGateway;
		$this->emailTemplateAdminGateway = $emailTemplateAdminGateway;
		$this->metrics = $metrics;
		$this->ids = array();
	}

	/**
	 * @required
	 */
	public function setTwig(\Twig\Environment $twig)
	{
		$this->twig = $twig;
	}

	/**
	 * @required
	 */
	public function setSession(Session $session)
	{
		$this->session = $session;
	}

	/**
	 * @required
	 */
	public function setMem(Mem $mem)
	{
		$this->mem = $mem;
	}

	public function s($id)
	{
		global $g_lang;

		if (isset($g_lang[$id])) {
			return $g_lang[$id];
		}

		return $id;
	}

	public function sv($id, $var)
	{
		global $g_lang;
		if (is_array($var)) {
			$search = array();
			$replace = array();
			foreach ($var as $key => $value) {
				$search[] = '{' . $key . '}';
				$replace[] = $value;
			}

			return str_replace($search, $replace, $g_lang[$id]);
		}

		return str_replace('{var}', $var, $g_lang[$id]);
	}

	public function setEditData($data)
	{
		global $g_data;
		$g_data = $data;
	}

	public function getAction($a): bool
	{
		return isset($_GET['a']) && $_GET['a'] == $a;
	}

	public function pageLink($page, $id, $action = '')
	{
		if (!empty($action)) {
			$action = '&a=' . $action;
		}

		return array('href' => '/?page=' . $page . $action, 'name' => $this->s($id));
	}

	public function getActionId($a)
	{
		if (isset($_GET['a'], $_GET['id']) && $_GET['a'] == $a && (int)$_GET['id'] > 0) {
			return (int)$_GET['id'];
		}

		return false;
	}

	public function getMenu()
	{
		$regions = [];
		$stores = [];
		$workingGroups = [];
		if (isset($_SESSION['client']['bezirke']) && is_array($_SESSION['client']['bezirke'])) {
			foreach ($_SESSION['client']['bezirke'] as $region) {
				$region = array_merge($region, ['isBot' => $this->session->isAdminFor($region['id'])]);
				if ($region['type'] == Type::WORKING_GROUP) {
					$workingGroups[] = $region;
				} else {
					$regions[] = $region;
				}
			}
		}
		if (isset($_SESSION['client']['betriebe']) && is_array($_SESSION['client']['betriebe'])) {
			$stores = $_SESSION['client']['betriebe'];
		}

		$loggedIn = $this->session->may();

		return $this->getMenuFn(
			$loggedIn,
			$regions,
			$this->session->may('fs'),
			$this->session->isOrgaTeam(),
			$this->session->mayEditBlog(),
			$this->session->mayEditQuiz(),
			$this->session->mayHandleReports(),
			$stores,
			$workingGroups,
			$this->session->get('mailbox'),
			(int)$this->session->id(),
			$loggedIn ? $this->img() : '',
			$this->session->may('bieb')
		);
	}

	private function getMenuFn(
		bool $loggedIn, array $regions, bool $hasFsRole,
		bool $isOrgaTeam, bool $mayEditBlog, bool $mayEditQuiz, bool $mayHandleReports,
		array $stores, array $workingGroups,
		$sessionMailbox, int $fsId, string $image, bool $mayAddStore)
	{
		$params = array_merge([
			'loggedIn' => $loggedIn,
			'fsId' => $fsId,
			'image' => $image,
			'mailbox' => $sessionMailbox,
			'hasFsRole' => $hasFsRole,
			'isOrgaTeam' => $isOrgaTeam,
			'may' => [
				'editBlog' => $mayEditBlog,
				'editQuiz' => $mayEditQuiz,
				'handleReports' => $mayHandleReports,
				'addStore' => $mayAddStore
			],
			'stores' => array_values($stores),
			'regions' => $regions,
			'workingGroups' => $workingGroups
		]);

		return $this->twig->render('partials/vue-wrapper.twig', [
			'id' => 'vue-topbar',
			'component' => 'topbar',
			'props' => $params
		]);
	}

	public function preZero($i)
	{
		if ($i < 10) {
			return '0' . $i;
		}

		return $i;
	}

	public function autolink($str, $attributes = array())
	{
		$attributes['target'] = '_blank';
		$attrs = '';
		foreach ($attributes as $attribute => $value) {
			$attrs .= " {$attribute}=\"{$value}\"";
		}
		$str = ' ' . $str;
		$str = preg_replace(
			'`([^"=\'>])(((http|https|ftp)://|www.)[^\s<]+[^\s<\.)])`i',
			'$1<a href="$2"' . $attrs . '>$2</a>',
			$str
		);
		$str = substr($str, 1);
		$str = preg_replace('`href=\"www`', 'href="http://www', $str);
		// fügt http:// hinzu, wenn nicht vorhanden
		return $str;
	}

	private function emailBodyTpl($message, $email = false, $token = false)
	{
		$unsubscribe = '
	<tr>
		<td height="20" valign="top" style="background-color:#FAF7E5">
			<div style="text-align:center;padding-top:10px;font-size:11px;font-family:Arial;padding:15px;color:#594129;">
				Willst Du diese Art von Benachrichtigungen nicht mehr bekommen? Du kannst unter <a style="color:#F36933" href="' . BASE_URL . '/?page=settings&sub=info" target="_blank">Benachrichtigungen</a> einstellen, welche Mails Du erhältst. 
			</div>
		</td>
	</tr>';

		if ($email !== false && $token !== false) {
			$unsubscribe = '
		<tr>
			<td height="20" valign="top" style="background-color:#FAF7E5">
				<div style="text-align:center;padding-top:10px;font-size:11px;font-family:Arial;padding:15px;color:#594129;">
					Möchtest Du keinen Newsletter mehr erhalten? <a style="color:#F36933" href="https://foodsharing.de/?page=login&sub=unsubscribe&t=' . $token . '&e=' . $email . '" target="_blank">Klicke hier zum Abbestellen!</a> Du kannst unter <a style="color:#F36933" href="https://foodsharing.de/?page=settings&sub=info" target="_blank">Benachrichtigungen</a> einstellen, welche Mails Du erhältst.
				</div>
<p style="font-size:11px;"><strong>Impressum</strong><br />
Angaben gemäß § 5 TMG:<br />
<br />foodsharing e.<span style="white-space:nowrap">&thinsp;</span>V.<br/>
Marsiliusstr. 36<br />
50937 Köln<br />
Vertreten durch:<br /><br />
Frank Bowinkelmann<br />
Kontakt:<br />E-Mail: info@foodsharing.de<br />
Registereintrag:<br /><br />Eintragung im Vereinsregister<br />
Registergericht: Amtsgericht Köln<br />
Registernummer: VR 17439<br />
Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV:<br />
<br />Frank Bowinkelmann<br /></p>
			</td>
		</tr>';
		}

		$message = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $message);

		$search = array('<a', '<td', '<li');
		$replace = array('<a style="color:#F36933"', '<td style="font-size:13px;font-family:Arial;color:#31210C;"', '<li style="margin-bottom:11px"');

		return '<html><head><style type="text/css">a{text-decoration:none;}a:hover{text-decoration:underline;}a.button{display:inline-block;padding:6px 16px;border:1px solid #FFFFFF;background-color:#4A3520;color:#FFFFFF !important;font-weight:bold;border-radius:8px;}a.button:hover{border:1px solid #4A3520;background-color:#ffffff;color:#4A3520 !important;text-decoration:none !important;}.border{padding:10px;border-top:1px solid #4A3520;border-bottom:1px solid #4A3520;background-color:#FFFFFF;}</style></head>
	<body style="margin:0;padding:0;">
		<div style="background-color:#F1E7C9;border:1px solid #628043;border-top:0px;padding:2%;padding-top:0;margin-top:0px;">

<table width="100%" style="margin-bottom:10px;margin-top:-2px;">
<tr>
				<td valign="top" height="30" style="background-color:#4A3520">
					<div style="padding:5px;font-size:13px;font-family:Arial;color:#FAF7E5;overflow:hidden;" align="left">
						<a style="display:block;color:#FAF7E5;text-decoration:none;" href="https://foodsharing.de/" target="_blank">
							<span style="margin-left:10px;font-size:20px;font-family:Arial Black, Arial;font-weight:bold;color:#FAF7E5;letter-spacing:-1px;">food</span><span style="margin-right:10px;font-size:20px;font-family:Arial Black, Arial;font-weight:bold;color:#4D971E;letter-spacing:-1px">sharing</span><span style="color:#F36933">.</span>de
						</a>
					</div>
				</td></tr>
</table>
			<table height="100%" width="100%">
				<tr>
				<td valign="top" style="background-color:#FAF7E5">
					<div style="padding:5px;font-size:13px;font-family:Arial;padding:15px;color:#31210C;">
						' . str_replace($search, $replace, $message) . '
					</div>
				</td>
				</tr>
				' . $unsubscribe . '
			</table>
		</div>
	</body>
</html>';
	}

	public function tplMail($tpl_id, $to, $var = array(), $from_email = false)
	{
		$mail = new AsyncMail($this->mem);

		if ($from_email !== false && $this->validEmail($from_email)) {
			$mail->setFrom($from_email);
		} else {
			$mail->setFrom(DEFAULT_EMAIL, DEFAULT_EMAIL_NAME);
		}

		$message = $this->emailTemplateAdminGateway->getOne_message_tpl($tpl_id);

		$search = array();
		$replace = array();
		foreach ($var as $key => $v) {
			$search[] = '{' . strtoupper($key) . '}';
			$replace[] = $v;
		}

		$message['body'] = str_replace($search, $replace, $message['body']);

		$message['subject'] = str_replace($search, $replace, $message['subject']);
		if (!$message['subject']) {
			$message['subject'] = 'foodsharing-Mail';
		}

		$mail->setSubject($this->sanitizerService->htmlToPlain($message['subject']));
		$htmlBody = $this->emailBodyTpl($message['body']);
		$mail->setHTMLBody($htmlBody);

		// playintext body
		$plainBody = $this->sanitizerService->htmlToPlain($htmlBody);
		$mail->setBody($plainBody);

		if (is_iterable($to)) {
			foreach ($to as $recipient) {
				$mail->addRecipient($recipient);
			}
		} else {
			$mail->addRecipient($to);
		}
		$mail->send();
		$this->metrics->addPoint('outgoing_email', ['template' => $tpl_id], ['count' => 1]);
	}

	public function img($file = false, $size = 'mini', $format = 'q', $altimg = false)
	{
		if ($file === false) {
			$file = $_SESSION['client']['photo'];
		}

		// prevent path traversal
		$file = preg_replace('/%/', '', $file);
		$file = preg_replace('/\.+/', '.', $file);

		if (!empty($file) && file_exists('images/' . $file)) {
			if (!file_exists('images/' . $size . '_' . $format . '_' . $file)) {
				$this->resizeImg('images/' . $file, $size, $format);
			}

			return '/images/' . $size . '_' . $format . '_' . $file;
		}

		if ($altimg === false) {
			return '/img/' . $size . '_' . $format . '_avatar.png';
		}

		return $altimg;
	}

	public function isMob(): bool
	{
		return isset($_SESSION['mob']) && $_SESSION['mob'] == 1;
	}

	public function id($name)
	{
		$id = $this->makeId($name, $this->ids);

		$this->ids[$id] = true;

		return $id;
	}

	public function getPostData()
	{
		if (isset($_POST)) {
			return $_POST;
		}

		return array();
	}

	public function getValue($id)
	{
		global $g_data;

		if (isset($g_data[$id])) {
			return $g_data[$id];
		}

		return '';
	}

	public function goPage($page = false)
	{
		if (!$page) {
			$page = $this->getPage();
			if (isset($_GET['bid'])) {
				$page .= '&bid=' . (int)$_GET['bid'];
			}
		}
		$this->go('/?page=' . $page);
	}

	public function go($url)
	{
		header('Location: ' . $url);
		exit();
	}

	public function getPage()
	{
		$page = $this->getGet('page');
		if (!$page) {
			$page = 'index';
		}

		return $page;
	}

	private function getSubPage()
	{
		$sub_page = $this->getGet('sub');
		if (!$sub_page) {
			$sub_page = 'index';
		}

		return $sub_page;
	}

	public function getGetId($name)
	{
		if (isset($_GET[$name]) && (int)$_GET[$name] > 0) {
			return (int)$_GET[$name];
		}

		return false;
	}

	public function getGet($name)
	{
		if (isset($_GET[$name])) {
			return $_GET[$name];
		}

		return false;
	}

	public function makeId($text, $ids = false)
	{
		$text = strtolower($text);
		str_replace(
			array('ä', 'ö', 'ü', 'ß', ' '),
			array('ae', 'oe', 'ue', 'ss', '_'),
			$text
		);
		$out = preg_replace('/[^a-z0-9_]/', '', $text);

		if ($ids !== false && isset($ids[$out])) {
			$id = $out;
			$i = 0;
			while (isset($ids[$id])) {
				++$i;
				$id = $out . '-' . $i;
			}
			$out = $id;
		}

		return $out;
	}

	public function submitted(): bool
	{
		return isset($_POST) && !empty($_POST);
	}

	public function info($msg, $title = false)
	{
		$t = '';
		if ($title !== false) {
			$t = '<strong>' . $title . '</strong> ';
		}
		$_SESSION['msg']['info'][] = $msg;
	}

	public function error($msg, $title = false)
	{
		$t = '';
		if ($title !== false) {
			$t = '<strong>' . $title . '</strong> ';
		}
		$_SESSION['msg']['error'][] = $t . $msg;
	}

	private function getTranslations()
	{
		global $g_lang;

		return $g_lang;
	}

	/**
	 * This is used to set window.serverData on in the frontend.
	 */
	public function getServerData()
	{
		$user = $this->session->get('user');

		$userData = [
			'id' => $this->session->id(),
			'firstname' => $user['name'],
			'lastname' => $user['nachname'],
			'may' => $this->session->may(),
			'verified' => $this->session->isVerified(),
			'avatar' => [
				'mini' => $this->img($user['photo'], 'mini'),
				'50' => $this->img($user['photo'], '50'),
				'130' => $this->img($user['photo'], '130')
			]
		];

		if ($this->session->may()) {
			$userData['token'] = $this->session->user('token');
		}

		$location = null;

		if ($pos = $this->session->get('blocation')) {
			$location = [
				'lat' => (float)$pos['lat'],
				'lon' => (float)$pos['lon'],
			];
		}

		$ravenConfig = null;

		if (defined('RAVEN_JAVASCRIPT_CONFIG')) {
			$ravenConfig = RAVEN_JAVASCRIPT_CONFIG;
		}

		return array_merge($this->jsData, [
			'user' => $userData,
			'page' => $this->getPage(),
			'subPage' => $this->getSubPage(),
			'location' => $location,
			'ravenConfig' => $ravenConfig,
			'translations' => $this->getTranslations(),
			'isDev' => getenv('FS_ENV') === 'dev'
		]);
	}

	public function validEmail($email)
	{
		if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return true;
		}

		return false;
	}

	public function libmail($bezirk, $email, $subject, $message, $attach = false, $token = false)
	{
		if ($bezirk === false) {
			$bezirk = array(
				'email' => DEFAULT_EMAIL,
				'email_name' => DEFAULT_EMAIL_NAME
			);
		} elseif (!is_array($bezirk)) {
			$bezirk = array(
				'email' => $bezirk,
				'email_name' => $bezirk
			);
		} else {
			if (!$this->validEmail($bezirk['email'])) {
				$bezirk['email'] = EMAIL_PUBLIC;
			}
			if (empty($bezirk['email_name'])) {
				$bezirk['email_name'] = EMAIL_PUBLIC_NAME;
			}
		}

		if (!$this->validEmail($email)) {
			return false;
		}

		$mail = new AsyncMail($this->mem);
		$mail->setFrom($bezirk['email'], $bezirk['email_name']);
		$mail->addRecipient($email);
		if (!$subject) {
			$subject = 'foodsharing-Mail';
		}
		$mail->setSubject($subject);
		$htmlBody = $this->emailBodyTpl($message, $email, $token);
		$mail->setHTMLBody($htmlBody);

		$plainBody = $this->sanitizerService->htmlToPlain($htmlBody);
		$mail->setBody($plainBody);

		if ($attach !== false) {
			foreach ($attach as $a) {
				$mail->addAttachment(new fFile($a['path']), $a['name']);
			}
		}

		$mail->send();
	}

	public function getBezirk()
	{
		return $this->regionGateway->getBezirk($this->session->getCurrentBezirkId());
	}

	public function genderWord($gender, $m, $w, $other)
	{
		$out = $other;
		if ($gender == 1) {
			$out = $m;
		} elseif ($gender == 2) {
			$out = $w;
		}

		return $out;
	}

	private function resizeImg($img, $width, $format)
	{
		// prevent path traversal
		$img = preg_replace('/%/', '', $img);
		$img = preg_replace('/\.+/', '.', $img);
		if (file_exists($img)) {
			$opt = 'auto';
			if ($format == 'q') {
				$opt = 'crop';
			}

			try {
				$newimg = str_replace('/', '/' . $width . '_' . $format . '_', $img);
				copy($img, $newimg);
				$img = new fImage($newimg);

				if ($opt == 'crop') {
					$img->cropToRatio(1, 1);
					$img->resize($width, $width);
				} else {
					$img->resize($width, 0);
				}

				$img->saveChanges();

				return true;
			} catch (Exception $e) {
			}
		}

		return false;
	}

	public function goSelf()
	{
		$this->go($this->getSelf());
	}

	public function getSelf()
	{
		return $_SERVER['REQUEST_URI'];
	}

	public function unsetAll($array, $fields)
	{
		$out = array();
		foreach ($fields as $f) {
			if (isset($array[$f])) {
				$out[$f] = $array[$f];
			}
		}

		return $out;
	}

	public function avatar($foodsaver, $size = 'mini', $altimg = false)
	{
		/*
		 * temporary for quiz
		 */
		$bg = '';
		if (isset($foodsaver['quiz_rolle'])) {
			switch ($foodsaver['quiz_rolle']) {
				case 1:
					$bg = 'box-sizing:border-box;border:3px solid #4A3520;';
					break;
				case 2:
					$bg = 'box-sizing:border-box;border:3px solid #599022;';
					break;
				case 3:
					$bg = 'box-sizing:border-box;border:3px solid #FFBB00;';
					break;
				case 4:
					$bg = 'box-sizing:border-box;border:3px solid #FF4800;';
					break;
				default:
					break;
			}
		}

		return '<span style="' . $bg . 'background-image:url(' . $this->img($foodsaver['photo'], $size, 'q', $altimg) . ');" class="avatar size-' . $size . ' sleepmode-' . $foodsaver['sleep_status'] . '"><i>' . $foodsaver['name'] . '</i></span>';
	}

	public function goLogin()
	{
		$this->go('/?page=login&ref=' . urlencode($_SERVER['REQUEST_URI']));
	}
}
