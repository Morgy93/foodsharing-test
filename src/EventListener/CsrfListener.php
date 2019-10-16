<?php

namespace Foodsharing\EventListener;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Foodsharing\Annotation\DisableCsrfProtection;
use Foodsharing\Lib\Session;

class CsrfListener
{
	private $reader;
	private $session;

	/**
	 * @param Reader $reader
	 * @param Session $session
	 */
	public function __construct(Reader $reader, Session $session)
	{
		$this->reader = $reader;
		$this->session = $session;
	}

	public function onKernelController(FilterControllerEvent $event)
	{
		if (!is_array($controllers = $event->getController())) {
			return;
		}

		$httpMethod = $event->getRequest()->getMethod();
		if (in_array($httpMethod, ['GET', 'OPTIONS', 'HEAD'])) {
			// since these methods should not cause any changes, we can savely execute cross site requests
			return;
		}

		list($controller, $methodName) = $controllers;
		$reflectionObject = new \ReflectionObject($controller);
		$reflectionMethod = $reflectionObject->getMethod($methodName);
		$methodAnnotation = $this->reader
			->getMethodAnnotation($reflectionMethod, DisableCsrfProtection::class);

		if ($methodAnnotation) {
			// CSRF Protection is disabled for this method
			return;
		}

		// TODO: This should refactored later to either use a whitelist or try to find a way to read the annotations.
		if ($this->startsWith($event->getRequest()->getRequestUri(), '/api/uploads') &&
			$event->getRequest()->getMethod() === 'GET') {
			return;
		}

		if (!$this->session->isValidCsrfHeader()) {
			throw new SuspiciousOperationException('CSRF Failed: CSRF token missing or incorrect.');
		}
	}

	private function startsWith($haystack, $needle): bool
	{
		return strpos($haystack, $needle) === 0;
	}
}
