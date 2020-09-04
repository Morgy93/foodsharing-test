<?php

namespace Foodsharing\Modules\Bell;

use Foodsharing\Lib\Xhr\Xhr;
use Foodsharing\Modules\Core\Control;

class BellXhr extends Control
{
	private $gateway;

	public function __construct(BellGateway $gateway)
	{
		$this->gateway = $gateway;

		parent::__construct();
	}

	/**
	 * ajax call to refresh infobar messages.
	 */
	public function infobar()
	{
		$this->session->noWrite();

		$xhr = new Xhr();
		$bells = $this->gateway->listBells($this->session->id(), 20);

		$xhr->addData('list', $bells);

		$xhr->send();
	}
}
