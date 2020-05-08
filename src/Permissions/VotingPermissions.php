<?php

namespace Foodsharing\Permissions;

use Foodsharing\Lib\Session;
use Foodsharing\Modules\Voting\VotingGateway;

final class VotingPermissions
{
	private $session;
	private $votingGateway;

	public function __construct(Session $session, VotingGateway $votingGateway)
	{
		$this->session = $session;
		$this->votingGateway = $votingGateway;
	}

	public function mayVote(int $pollId): bool
	{
		try {
			return $this->votingGateway->hasUserVoted($pollId, $this->session->id());
		} catch (\Exception $e) {
			return false;
		}
	}
}
