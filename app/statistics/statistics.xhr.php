<?php

use Foodsharing\Modules\Core\Control;

class StatisticsXhr extends Control
{
	public function __construct()
	{
		$this->model = new StatisticsModel();
		$this->view = new StatisticsView();

		parent::__construct();
	}
}
