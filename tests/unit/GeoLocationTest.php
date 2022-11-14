<?php

use Foodsharing\Modules\Core\DTO\GeoLocation;

class GeoLocationTest extends \Codeception\Test\Unit
{
	public function testAddBell()
	{
		$dbResult = ['lon' => 49.921, 'lat' => 5.400];

		$location = GeoLocation::createFromArray($dbResult);

		$this->assertEquals(49.921, $location->lon);
		$this->assertEquals(5.400, $location->lat);
	}
}
