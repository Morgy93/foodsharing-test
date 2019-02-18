<?php

namespace Foodsharing\Modules\GeoClean;

use Foodsharing\Modules\Core\Control;
use Foodsharing\Modules\Region\RegionGateway;

class GeoCleanXhr extends Control
{
	private $regionGateway;

	public function __construct(GeoCleanModel $model, RegionGateway $regionGateway)
	{
		$this->model = $model;
		$this->regionGateway = $regionGateway;

		parent::__construct();

		if (!$this->session->may('orga')) {
			return false;
		}
	}

	public function masterupdate()
	{
		if ($bezirke = $this->regionGateway->listIdsForDescendantsAndSelf($_GET['id'])) {
			$this->regionGateway->updateMasterRegions($bezirke, $_GET['id']);
		}
	}

	public function updateGeo()
	{
		$lat = $_GET['lat'];
		$lon = $_GET['lon'];
		$fsid = $_GET['id'];

		if ($this->model->updateGeo($fsid, $lat, $lon)) {
			return array(
				'status' => 1,
				'script' => '$("#fs-' . $fsid . '").parent().parent().remove();pulseInfo("Koordinaten gespeichert!");'
			);
		}
	}
}
