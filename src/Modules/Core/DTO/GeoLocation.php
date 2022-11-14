<?php

namespace Foodsharing\Modules\Core\DTO;

/**
 * Describes a location by its coordinates, so that a representation on a map is possible.
 */
class GeoLocation
{
	/**
	 * Latitude of the location.
	 */
	public float $lat;

	/**
	 * Longitude of the location.
	 */
	public float $lon;

	public static function createFromArray($queryResult)
	{
		$obj = new GeoLocation();
		$obj->lat = $queryResult['lat'];
		$obj->lon = $queryResult['lon'];

		return $obj;
	}
}
