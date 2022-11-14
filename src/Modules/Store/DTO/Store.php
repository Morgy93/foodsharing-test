<?php

namespace Foodsharing\Modules\Store\DTO;

use DateTime;
use Foodsharing\Modules\Core\DTO\GeoLocation;

/**
 * This class is a representation of an Store.
 */
class Store
{
	/**
	 * Unique identifier of the store in database.
	 */
	public int $id;

	/**
	 * Name of the store.
	 */
	public string $name;

	/**
	 * Region which is manages and is responsible for this store.
	 */
	public int $regionId;

	/**
	 * Location of the store.
	 */
	public GeoLocation $location;

	public string $street;
	public string $zip;
	public string $city;

	public string $publicInfo;
	public int $publicTime;

	public int $categoryId;
	public int $chainId;
	public int $cooperationStatus;

	public string $description;
	// public array $foodTypes; // specialcased in StoreTransaction

	public ?string $contactName;
	public ?string $contactPhone;
	public ?string $contactFax;
	public ?string $contactEmail;
	public ?DateTime $cooperationStart;

	public int $calendarInterval;
	public int $useRegionPickupRule;
	public int $weight;
	public int $effort;
	public bool $publicity;
	public bool $sticker;

	public ?DateTime $createdAt = null;
	public DateTime $updatedAt;

	public static function createFromArray($queryResult): Store
	{
		$obj = new Store();
		$obj->id = $queryResult['id'];
		$obj->name = $queryResult['name'];
		$obj->regionId = $queryResult['region_id'];
		$obj->location = GeoLocation::createFromArray($queryResult);
		$obj->street = $queryResult['street'];
		$obj->zip = $queryResult['zip'];
		$obj->city = $queryResult['city'];
		$obj->publicInfo = $queryResult['public_info'];
		$obj->publicTime = $queryResult['public_time'];
		$obj->categoryId = $queryResult['categoryId'];
		$obj->chainId = $queryResult['chainId'];
		$obj->cooperationStatus = $queryResult['cooperationStatus'];

		$obj->description = $queryResult['description'];

		// some fields are missing is missing

		$obj->publicity = $queryResult['publicity'] == 1;
		$obj->sticker = $queryResult['sticker'] == 1;

		$createAt = DateTime::createFromFormat('Y-m-d', $queryResult['createdAt']);
		if ($createAt) {
			$obj->createdAt = $createAt;
		}

		$updatedAt = DateTime::createFromFormat('Y-m-d', $queryResult['updatedAt']);
		if ($updatedAt) {
			$obj->updatedAt = $updatedAt;
		}

		return $obj;
	}
}
