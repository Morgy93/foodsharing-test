<?php

namespace Foodsharing\Utility;

use Foodsharing\Lib\Session;
use Foodsharing\Modules\Core\DBConstants\Foodsaver\Role;
use Foodsharing\Modules\Core\DBConstants\Region\RegionIDs;
use Foodsharing\Modules\Core\DBConstants\Unit\UnitType;
use Foodsharing\Modules\Region\RegionGateway;
use Foodsharing\Permissions\BlogPermissions;
use Foodsharing\Permissions\ContentPermissions;
use Foodsharing\Permissions\MailboxPermissions;
use Foodsharing\Permissions\NewsletterEmailPermissions;
use Foodsharing\Permissions\ProfilePermissions;
use Foodsharing\Permissions\QuizPermissions;
use Foodsharing\Permissions\RegionPermissions;
use Foodsharing\Permissions\ReportPermissions;
use Foodsharing\Permissions\StorePermissions;
use Foodsharing\Permissions\WorkGroupPermissions;
use JetBrains\PhpStorm\Deprecated;
use Twig\Environment;

final class PageHelper
{
    private string $add_css = '';
    private string $content_main = '';
    private string $content_right = '';
    private string $content_left = '';
    private string $content_bottom = '';
    private string $content_top = '';
    private string $content_overtop = '';
    private string $head = '';
    private string $hidden = '';
    private string $js_func = '';
    private string $js = '';
    private array $bread = [];
    private array $title = ['foodsharing'];
    private array $webpackScripts = [];
    private array $webpackStylesheets = [];

    private int $content_left_width = 6;
    private int $content_right_width = 6;

    private array $extraJsServerData = [];

    public function __construct(
        private readonly Session $session,
        private readonly Sanitizer $sanitizerService,
        private readonly Environment $twig,
        private readonly RouteHelper $routeHelper,
        private readonly MailboxPermissions $mailboxPermissions,
        private readonly QuizPermissions $quizPermissions,
        private readonly ReportPermissions $reportPermissions,
        private readonly StorePermissions $storePermissions,
        private readonly ContentPermissions $contentPermissions,
        private readonly BlogPermissions $blogPermissions,
        private readonly RegionPermissions $regionPermissions,
        private readonly NewsletterEmailPermissions $newsletterEmailPermissions,
        private readonly WorkGroupPermissions $workGroupPermissions,
        private readonly ProfilePermissions $profilePermissions,
        private readonly RegionGateway $regionGateway
    ) {
    }

    public function generateAndGetGlobalViewData(): array
    {
        $this->addMessages();
        $mainWidth = 24;

        $contentLeft = $this->getContent(CNT_LEFT);
        $contentRight = $this->getContent(CNT_RIGHT);

        if (!empty($contentLeft)) {
            $mainWidth -= $this->content_left_width;
        }

        if (!empty($contentRight)) {
            $mainWidth -= $this->content_right_width;
        }

        $bodyClasses = [];

        if ($this->session->mayRole()) {
            $bodyClasses[] = 'loggedin';
        }

        if ($this->session->mayRole(Role::FOODSAVER)) {
            $bodyClasses[] = 'fs';
        }

        if ($this->routeHelper->isUsingLegacyController()) {
            $page = $this->routeHelper->getPage();
        } else {
            $page = $this->routeHelper->getSymfonyRoute();
        }

        $bodyClasses[] = 'page-' . $page;

        return [
            'head' => $this->getHeadData(),
            'bread' => $this->bread,
            'bodyClasses' => $bodyClasses,
            'serverDataJSON' => json_encode($this->getServerData()),
            'menu' => $this->getMenu(),
            'route' => $page,
            'dev' => FS_ENV == 'dev',
            'hidden' => $this->hidden,
            'isMob' => $this->session->isMob(),
            'footer' => $this->getFooter(),
            'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? BASE_URL,
            'content' => [
                'main' => [
                    'html' => $this->getContent(CNT_MAIN),
                    'width' => $mainWidth
                ],
                'left' => [
                    'html' => $contentLeft,
                    'width' => $this->content_left_width,
                    'id' => 'left'
                ],
                'right' => [
                    'html' => $contentRight,
                    'width' => $this->content_right_width,
                    'id' => 'right'
                ],
                'top' => [
                    'html' => $this->getContent(CNT_TOP),
                    'id' => 'content_top'
                ],
                'bottom' => [
                    'html' => $this->getContent(CNT_BOTTOM),
                    'id' => 'content_bottom'
                ],
                'overtop' => [
                    'html' => $this->getContent(CNT_OVERTOP)
                ]
            ]
        ];
    }

    /**
     * This is used to set window.serverData on in the frontend.
     */
    public function getServerData(): array
    {
        $user = $this->session->get('user');

        $userData = [
            'id' => $this->session->id(),
            'firstname' => $user['name'] ?? '',
            'lastname' => $user['nachname'] ?? '',
            'may' => $this->session->mayRole(),
            'homeRegionId' => $user['bezirk_id'] ?? null,
            'mailBoxId' => $user['mailbox_id'] ?? null,
            'isFoodsaver' => $this->session->mayRole(Role::FOODSAVER),
            'verified' => $this->session->isVerified(),
            'avatar' => $user['photo'] ?? null,
        ];

        $permissions = null;
        if ($this->session->mayRole()) {
            $userData['token'] = $this->session->user('token');
            $permissions = $this->getPermissions();
        }

        $locations = null;
        if ($pos = $this->session->getLocation()) {
            $locations = [
                'lat' => $pos['lat'],
                'lon' => $pos['lon'],
            ];
        }

        $sentryConfig = null;

        if (defined('RAVEN_JAVASCRIPT_CONFIG')) {
            $sentryConfig = RAVEN_JAVASCRIPT_CONFIG;
        }

        $mapTilesApiKey = defined('GEOAPIFY_API_KEY') ? GEOAPIFY_API_KEY : null;

        return array_merge($this->extraJsServerData, [
            'user' => $userData,
            'permissions' => $permissions,
            'page' => $this->routeHelper->getPage(),
            'subPage' => $this->routeHelper->getSubPage(),
            'locations' => $locations,
            'ravenConfig' => $sentryConfig,
            'isDev' => getenv('FS_ENV') === 'dev',
            'isTest' => getenv('FS_ENV') === 'test',
            'locale' => $this->session->getLocale(),
            'mapTilesApiKey' => $mapTilesApiKey
        ]);
    }

    private function getPermissions(): array
    {
        $data = $this->session->get('user');

        return [
            'mayEditUserProfile' => $this->profilePermissions->mayEditUserProfile($this->session->id()),
            'mayAdministrateUserProfile' => $this->profilePermissions->mayAdministrateUserProfile($this->session->id(), $data['bezirk_id']),
            'administrateBlog' => $this->blogPermissions->mayAdministrateBlog(),
            'editQuiz' => $this->quizPermissions->mayEditQuiz(),
            'handleReports' => $this->reportPermissions->mayHandleReports(),
            'addStore' => $this->storePermissions->mayCreateStore(),
            'manageMailboxes' => $this->mailboxPermissions->mayManageMailboxes(),
            'editContent' => $this->contentPermissions->mayEditContent(),
            'administrateNewsletterEmail' => $this->newsletterEmailPermissions->mayAdministrateNewsletterEmail(),
            'administrateRegions' => $this->regionPermissions->mayAdministrateRegions(),
        ];
    }

    private function getMenu(): string
    {
        $groups = $_SESSION['client']['bezirke'] ?? [];

        $regions = [];
        $workingGroups = [];

        foreach ($groups as $group) {
            $groupId = $group['id'];
            $groupType = $group['type'];
            $group = array_merge($group, [
                'mayHandleFoodsaverRegionMenu' => $this->regionPermissions->mayHandleFoodsaverRegionMenu($groupId),
                'hasConference' => $this->regionPermissions->hasConference($groupType)
            ]);
            if (UnitType::isRegion($groupType)) {
                $group['isAdmin'] = $this->session->isAdminFor($groupId);
                $group['mayAccessReportGroupReports'] = $this->reportPermissions->mayAccessReportGroupReports($groupId);
                $group['mayAccessArbitrationGroupReports'] = $this->reportPermissions->mayAccessArbitrationReports($groupId);
                $group['maySetRegionPin'] = $this->regionPermissions->maySetRegionPin($groupId);
                $regions[] = $group;
            } else {
                $group['isAdmin'] = $this->workGroupPermissions->mayEdit($group);
                $group['hasSubgroups'] = $this->regionGateway->hasSubgroups($groupId);
                if ($group['id'] == RegionIDs::STORE_CHAIN_GROUP) {
                    $group['isChainGroup'] = true;
                }
                $workingGroups[] = $group;
            }
        }

        $props = [
            'regions' => $regions,
            'groups' => $workingGroups,
        ];

        return $this->twig->render(
            'partials/vue-wrapper.twig',
            [
                'id' => 'vue-topbar',
                'component' => 'topbar',
                'props' => $props,
            ]
        );
    }

    private function getFooter(): string
    {
        $params = [
            'version' => defined('SRC_REVISION') ? SRC_REVISION : null,
        ];

        return $this->twig->render(
            'partials/vue-wrapper.twig',
            [
                'id' => 'vue-footer',
                'component' => 'Footer',
                'props' => $params,
            ]
        );
    }

    private function getHeadData(): array
    {
        return [
            'title' => implode(' | ', $this->title),
            'extra' => $this->head,
            'css' => str_replace(["\r", "\n"], '', $this->add_css),
            'jsFunc' => $this->js_func,
            'js' => $this->js,
            'stylesheets' => $this->webpackStylesheets,
            'scripts' => $this->webpackScripts
        ];
    }

    private function addMessages(): void
    {
        if (!isset($_SESSION['msg'])) {
            $_SESSION['msg'] = [];
        }
        if (isset($_SESSION['msg']['error']) && !empty($_SESSION['msg']['error'])) {
            $msg = '';
            foreach ($_SESSION['msg']['error'] as $e) {
                $msg .= '<div class="item">' . $e . '</div>';
            }
            $this->addJs('pulseError("' . $this->sanitizerService->jsSafe($msg, '"') . '");');
        }
        if (isset($_SESSION['msg']['success']) && !empty($_SESSION['msg']['success'])) {
            $msg = '';
            foreach ($_SESSION['msg']['success'] as $i) {
                $msg .= '<p>' . $i . '</p>';
            }
            $this->addJs('pulseSuccess("' . $this->sanitizerService->jsSafe($msg, '"') . '");');
        }
        if (isset($_SESSION['msg']['info']) && !empty($_SESSION['msg']['info'])) {
            $msg = '';
            foreach ($_SESSION['msg']['info'] as $i) {
                $msg .= '<p>' . $i . '</p>';
            }
            $this->addJs('pulseInfo("' . $this->sanitizerService->jsSafe($msg, '"') . '");');
        }
        $_SESSION['msg']['info'] = [];
        $_SESSION['msg']['success'] = [];
        $_SESSION['msg']['error'] = [];
    }

    private function getContent(int $positionCode): string
    {
        return match ($positionCode) {
            CNT_MAIN => $this->content_main,
            CNT_RIGHT => $this->content_right,
            CNT_TOP => $this->content_top,
            CNT_BOTTOM => $this->content_bottom,
            CNT_LEFT => $this->content_left,
            CNT_OVERTOP => $this->content_overtop,
            default => '',
        };
    }

    public function addContent(string $newContent, int $positionCode = CNT_MAIN): void
    {
        switch ($positionCode) {
            case CNT_MAIN:
                $this->content_main .= $newContent;
                break;
            case CNT_RIGHT:
                $this->content_right .= $newContent;
                break;
            case CNT_TOP:
                $this->content_top .= $newContent;
                break;
            case CNT_BOTTOM:
                $this->content_bottom .= $newContent;
                break;
            case CNT_LEFT:
                $this->content_left .= $newContent;
                break;
            case CNT_OVERTOP:
                $this->content_overtop .= $newContent;
                break;
            default:
                break;
        }
    }

    public function addBread(string $name, string $href = ''): void
    {
        $this->bread[] = ['name' => $name, 'href' => $href];
    }

    public function addWebpackScript(string $src): void
    {
        $this->webpackScripts[] = $src;
    }

    public function addWebpackStylesheet(string $src): void
    {
        $this->webpackStylesheets[] = $src;
    }

    public function addStyle(string $css): void
    {
        $this->add_css .= trim($css);
    }

    public function addJs(string $njs): void
    {
        $this->js .= $njs;
    }

    public function addJsFunc(string $nfunc): void
    {
        $this->js_func .= $nfunc;
    }

    /**
     * @deprecated
     */
    public function addJsServerData(string $key, array $data): void
    {
        $this->extraJsServerData[$key] = $data;
    }

    public function addHead(string $str): void
    {
        $this->head .= "\n" . $str;
    }

    public function addTitle(string $name): void
    {
        $this->title[] = $name;
    }

    public function addHidden(string $html): void
    {
        $this->hidden .= $html;
    }

    /**
     * @deprecated - use modern frontend code instead
     */
    public function hiddenDialog(string $id, array $fields, string $title = '', bool $reload = false, string $width = ''): void
    {
        $form = implode('', $fields);

        $get = '';
        if (isset($_GET['id'])) {
            $get = '<input type="hidden" name="id" value="' . (int)$_GET['id'] . '" />';
        }

        $this->addHidden('<div id="' . $id . '"><form>' . $form . $get . '</form></div>');

        $width = $width ? "width: $width," : '';
        $success = $reload ? 'reload();' : '';

        $this->addJs('
		$("#' . $id . '").dialog({
		' . $width . '
		autoOpen: false,
		modal: true,
		title: "' . $title . '",
		buttons: {
			"Speichern": function () {
				showLoader();
				$.ajax({
					dataType: "json",
					url: "/xhr?f=' . $id . '&" + $("#' . $id . ' form").serialize(),
					success: function (data) {
						$("#' . $id . '").dialog(\'close\');
						' . $success . '
						if (data.script != undefined) {
							$.globalEval(data.script);
						}
					},
					complete: function () {
						hideLoader();
					}
				});
			}
		}
	});
	');
    }

    public function setContentWidth(int $left, int $right): void
    {
        $this->content_left_width = $left;
        $this->content_right_width = $right;
    }
}
