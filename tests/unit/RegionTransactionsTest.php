<?php

use Foodsharing\Modules\Foodsaver\FoodsaverGateway;
use Foodsharing\Modules\Region\RegionTransactions;
use PHPUnit\Framework\TestCase;

class RegionTransactionsTest extends TestCase
{
	private RegionTransactions $regionTransactions;

	private FoodsaverGateway $foodsaverGateway;

	protected function setUp(): void
	{
		$this->foodsaverGateway = $this->createMock(FoodsaverGateway::class);
		$this->regionTransactions = new RegionTransactions($this->foodsaverGateway);
	}

	public function testNewFoodsaverVerified()
	{
		$this->assertSame(
			RegionTransactions::NEW_FOODSAVER_VERIFIED,
			$this->regionTransactions->getJoinMessage(['verified' => 1, 'id' => 1])
		);
	}

	public function testNewFoodsaverNeedsVerification()
	{
		$this->foodsaverGateway->method('foodsaverWasVerifiedBefore')->willReturn(true);
		$this->assertSame(
			RegionTransactions::NEW_FOODSAVER_NEEDS_VERIFICATION,
			$this->regionTransactions->getJoinMessage(['verified' => 0, 'id' => 1])
		);
	}

	public function testNewFoodsaverNeedsIntroduction()
	{
		$this->foodsaverGateway->method('foodsaverWasVerifiedBefore')->willReturn(false);
		$this->assertSame(
			RegionTransactions::NEW_FOODSAVER_NEEDS_INTRODUCTION,
			$this->regionTransactions->getJoinMessage(['verified' => 0, 'id' => 1])
		);
	}

	public function testInvalidUserData()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid user data. Id not set.');

		$this->regionTransactions->getJoinMessage([]);
	}
}
