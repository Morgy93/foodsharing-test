<?php

namespace Foodsharing\RestApi;

use Foodsharing\Lib\Session as Session;
use Foodsharing\Modules\Settings\SettingsGateway;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LocaleRestController extends AbstractFOSRestController
{
	private SettingsGateway $settingsGateway;
	private Session $session;

	public function __construct(
		SettingsGateway $settingsGateway,
		Session $session
	) {
		$this->settingsGateway = $settingsGateway;
		$this->session = $session;
	}

	/**
	 * Returns the locale setting for the current session.
	 *
	 * @Rest\Get("locale")
	 */
	public function getLocaleAction(): Response
	{
		if (!$this->session->may()) {
			throw new HttpException(401);
		}

		$locale = $this->session->getLocale();

		return $this->handleView($this->view(['locale' => $locale], 200));
	}

	/**
	 * Sets the locale for the current session.
	 *
	 * @Rest\Post("locale")
	 * @Rest\RequestParam(name="locale")
	 */
	public function setLocaleAction(ParamFetcher $paramFetcher): Response
	{
		if (!$this->session->may()) {
			throw new HttpException(401);
		}

		$locale = $paramFetcher->get('locale');
		if (empty($locale)) {
			$locale = Session::DEFAULT_LOCALE;
		}

		$this->session->set('locale', $locale);
		$this->settingsGateway->setLocale($this->session->id(), $locale);

		return $this->getLocaleAction();
	}
}
