<?php

namespace Foodsharing\Permissions;

use Foodsharing\Modules\Event\EventGateway;
use Foodsharing\Modules\FairTeiler\FairTeilerGateway;
use Foodsharing\Modules\Region\RegionGateway;
use Foodsharing\Modules\WallPost\WallPostGateway;

class WallPostPermissions
{
	private $wallPostGateway;
	private $regionGateway;
	private $eventGateway;
	private $fairteilerGateway;

	public function __construct(
		RegionGateway $regionGateway,
		WallPostGateway $wallPostGateway,
		EventGateway $eventGateway,
		FairteilerGateway $fairteilerGateway
	) {
		$this->wallPostGateway = $wallPostGateway;
		$this->regionGateway = $regionGateway;
		$this->eventGateway = $eventGateway;
		$this->fairteilerGateway = $fairteilerGateway;
	}

	public function mayReadWall($fsId, $target, $targetId)
	{
		if (!$fsId) {
			return false;
		}

		switch ($target) {
			case 'bezirk':
				return $this->regionGateway->hasMember($fsId, $targetId);
			case 'event':
				/* ToDo merge with access logic inside event */
				$event = $this->eventGateway->getEventWithInvites($targetId);

				return $event['public'] || isset($event['invites']['may'][$fsId]);
			case 'fairteiler':
				return true;
			case 'question':
				return $this->regionGateway->hasMember($fsId, 341);
			case 'usernotes':
				return $this->regionGateway->hasMember($fsId, 432);
			default:
				return $fsId > 0;
		}
	}

	public function mayWriteWall($fsId, $target, $targetId)
	{
		if (!$fsId) {
			return false;
		}

		switch ($target) {
			case 'foodsaver':
				return $fsId == $targetId;
			case 'question':
				return $fsId > 0;
			default:
				return $fsId > 0 && $this->mayReadWall($fsId, $target, $targetId);
		}
	}

	/**
	 * method describing _global_ deletion access to walls. Every author is always allowed to remove their own posts.
	 *
	 * @param $fsId
	 * @param $target
	 * @param $targetId
	 */
	public function mayDeleteFromWall($fsId, $target, $targetId)
	{
		if (!$fsId) {
			return false;
		}

		switch ($target) {
			case 'foodsaver':
				return $fsId == $targetId;
			case 'bezirk':
				return $this->regionGateway->isAdmin($fsId, $targetId);
			case 'question':
				return $this->mayReadWall($fsId, $target, $targetId);
			case 'usernotes':
				return $this->mayReadWall($fsId, $target, $targetId);
			default:
				return false;
		}
	}
}
