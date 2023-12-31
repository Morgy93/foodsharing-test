<?php

declare(strict_types=1);

namespace Tests\Api;

use Carbon\Carbon;
use Codeception\Util\HttpCode;
use Tests\Support\ApiTester;

class PickupApiCest
{
    private $user;
    private $store;
    private $store2;
    private $store3;
    private $region;
    private $waiter;

    public function _before(ApiTester $I): void
    {
        $this->user = $I->createFoodsaver();
        $this->storeCoordinator = $I->createStoreCoordinator();
        $this->region = $I->createRegion();
        $this->store = $I->createStore($this->region['id']);
        $I->addStoreTeam($this->store['id'], $this->user['id']);
        $I->addStoreTeam($this->store['id'], $this->storeCoordinator['id'], true);
        $this->waiter = $I->createFoodsaver();
        $I->addStoreTeam($this->store['id'], $this->waiter['id'], false, true);
        $this->store2 = $I->createStore($this->region['id'], null, null, ['use_region_pickup_rule' => 1]);
        $I->addStoreTeam($this->store2['id'], $this->user['id']);
        $this->store3 = $I->createStore($this->region['id'], null, null, ['use_region_pickup_rule' => 1]);
        $I->addStoreTeam($this->store3['id'], $this->user['id']);
        $this->store4 = $I->createStore($this->region['id'], null, null, ['use_region_pickup_rule' => 1]);
        $I->addStoreTeam($this->store4['id'], $this->user['id']);
    }

    public function acceptsDifferentIsoFormats(ApiTester $I): void
    {
        $I->login($this->user['email']);
        $id = $this->user['id'];
        $pickupBaseDate = Carbon::now()->add('2 days');
        $pickupBaseDate->hours(13)->minutes(45)->seconds(0);
        $I->addPickup($this->store['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);
        $I->sendPOST('api/stores/' . $this->store['id'] . '/pickups/' . $pickupBaseDate->copy()->setTimezone('UTC')->format('Y-m-d\TH:i:s') . '+0000/' . $id);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $pickupBaseDate->minutes(50);
        $I->addPickup($this->store['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);
        $I->sendPOST('api/stores/' . $this->store['id'] . '/pickups/' . $pickupBaseDate->copy()->setTimezone('+01:00')->format('Y-m-d\TH:i:s') . '.000+01:00/' . $id);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $pickupBaseDate->minutes(55);
        $I->addPickup($this->store['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);
        $I->sendPOST('api/stores/' . $this->store['id'] . '/pickups/' . $pickupBaseDate->copy()->setTimezone('-01:00')->format('Y-m-d\TH:i:s') . '-01:00/' . $id);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $pickupBaseDate->minutes(35);
        $I->addPickup($this->store['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);
        $I->sendPOST('api/stores/' . $this->store['id'] . '/pickups/' . $pickupBaseDate->copy()->setTimezone('UTC')->format('Y-m-d\TH:i:s') . 'Z/' . $id);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
    }

    public function signupAsWaiterDoesNotWork(ApiTester $I): void
    {
        $I->login($this->waiter['email']);
        $pickupBaseDate = Carbon::now()->add('2 days');
        $pickupBaseDate->hours(14)->minutes(50)->seconds(0);
        $I->addPickup($this->store['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);
        $I->sendPOST('api/stores/' . $this->store['id'] . '/pickups/' . $pickupBaseDate->toIso8601String() . '/' . $this->waiter['id']);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function signupForDifferentUserShouldBeRejected(ApiTester $I): void
    {
        $I->login($this->user['email']);
        $pickupBaseDate = Carbon::now()->add('2 days');
        $pickupBaseDate->hours(14)->minutes(50)->seconds(0);
        $I->addPickup($this->store['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);
        $I->sendPOST('api/stores/' . $this->store['id'] . '/pickups/' . $pickupBaseDate->toIso8601String() . '/' . $this->waiter['id']);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function signupForNotAviablableSlots(ApiTester $I): void
    {
        $I->login($this->user['email']);
        $pickupBaseDate = Carbon::now()->add('2 days');
        $pickupBaseDate->hours(14)->minutes(50)->seconds(0);
        $I->addPickup($this->store['id'], ['time' => $pickupBaseDate, 'fetchercount' => 1]);
        $I->sendPOST('api/stores/' . $this->store['id'] . '/pickups/' . $pickupBaseDate->toIso8601String() . '/' . $this->waiter['id']);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function signupReturnsPickupConfirmationState(ApiTester $I): void
    {
        $I->login($this->storeCoordinator['email']);
        $pickupBaseDate = Carbon::now()->add('2 days');
        $pickupBaseDate->hours(14)->minutes(45)->seconds(0);
        $I->addPickup($this->store['id'], ['time' => $pickupBaseDate, 'fetchercount' => 1]);
        $I->sendPOST('api/stores/' . $this->store['id'] . '/pickups/' . $pickupBaseDate->toIso8601String() . '/' . $this->storeCoordinator['id']);
        $I->sendPOST('api/stores/' . $this->store['id'] . '/pickups/' . $pickupBaseDate->toIso8601String() . '/' . $this->user['id']);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function pickupDescriptionVisible(ApiTester $I)
    {
        $I->login($this->user['email']);

        //Create a pickup
        $pickupBaseDate = Carbon::now()->add('1 days');
        $pickupBaseDate->hours(14)->minutes(45)->seconds(0);
        $I->addPickup($this->store['id'], ['time' => $pickupBaseDate, 'fetchercount' => 1, 'description' => 'some description']);

        $I->sendGet('api/stores/' . $this->store['id'] . '/pickups');
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->canSeeResponseContainsJson([
            'description' => 'some description'
        ]);
    }

    public function pickupDescriptionEditable(ApiTester $I)
    {
        $I->login($this->storeCoordinator['email']);

        //Create a pickup
        $pickupBaseDate = Carbon::now()->add('1 days');
        $pickupBaseDate->hours(14)->minutes(45)->seconds(0);
        $I->addPickup($this->store['id'], ['time' => $pickupBaseDate, 'fetchercount' => 1]);

        $I->sendPatch('api/stores/' . $this->store['id'] . '/pickups/' . $pickupBaseDate->toIso8601String(),
            ['description' => 'random description', 'totalSlots' => 3]
        );
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->canSeeResponseContainsJson([
            'created' => false
        ]);

        $I->sendGet('api/stores/' . $this->store['id'] . '/pickups');
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->canSeeResponseContainsJson([
            'description' => 'random description'
        ]);
    }

    public function createOnetimePickupWithDescription(ApiTester $I)
    {
        $I->login($this->storeCoordinator['email']);

        //Create a pickup
        $pickupBaseDate = Carbon::now()->add('1 days');
        $pickupBaseDate->hours(14)->minutes(45)->seconds(0);

        $I->sendPatch('api/stores/' . $this->store['id'] . '/pickups/' . $pickupBaseDate->toIso8601String(),
            ['description' => 'another random description', 'totalSlots' => 3]
        );
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->canSeeResponseContainsJson([
            'created' => true
        ]);

        $I->sendGet('api/stores/' . $this->store['id'] . '/pickups');
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->canSeeResponseContainsJson([
            'description' => 'another random description'
        ]);
    }

    public function createAndEnterRegularPickupWithDescription(ApiTester $I)
    {
        $coordinator = $I->createStoreCoordinator();
        $I->addStoreTeam($this->store['id'], $coordinator['id'], true, false, true);
        $I->login($coordinator['email']);

        //Create a pickup
        $pickupBaseDate = Carbon::now()->next('Monday')->add('1 weeks');
        $pickupBaseDate->hours(10)->minutes(30)->seconds(0);

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut('api/stores/' . $this->store['id'] . '/regularPickup',
            [[
                'description' => 'regular slot description',
                'maxCountOfSlots' => 1,
                'startTimeOfPickup' => '10:30:00',
                'weekday' => 1,
            ]]
        );
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);

        $I->sendGet('api/stores/' . $this->store['id'] . '/pickups');
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->canSeeResponseContainsJson([
            'description' => 'regular slot description'
        ]);

        // Enter into that regular slot
        $I->sendPOST('api/stores/' . $this->store['id'] . '/pickups/' . $pickupBaseDate->toIso8601String() . '/' . $coordinator['id']);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);

        $I->sendGet('api/stores/' . $this->store['id'] . '/pickups');
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);

        // Make sure the description of the regular pickup slot is still there (now as a onetime pickup)
        $I->canSeeResponseContainsJson([
            'description' => 'regular slot description',
            'occupiedSlots' => [
                ['isConfirmed' => true]
            ]
        ]);
    }

    public function signupAsCoordinarIsPreconfirmed(ApiTester $I): void
    {
        $pickupBaseDate = Carbon::now()->add('2 days');
        $pickupBaseDate->hours(16)->minutes(45)->seconds(0);
        $coordinator = $I->createStoreCoordinator();
        $I->addStoreTeam($this->store['id'], $coordinator['id'], true, false, true);
        $I->login($coordinator['email']);
        $I->addPickup($this->store['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);
        $I->sendPOST('api/stores/' . $this->store['id'] . '/pickups/' . $pickupBaseDate->toIso8601String() . '/' . $coordinator['id']);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->canSeeResponseContainsJson([
            'isConfirmed' => true
        ]);
    }

    public function AsWaiterICannotSeePickups(ApiTester $I): void
    {
        $pickupBaseDate = Carbon::now()->add('2 days');
        $pickupBaseDate->hours(16)->minutes(55)->seconds(0);
        $I->addPickup($this->store['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);
        $I->login($this->waiter['email']);
        $I->sendGET('api/stores/' . $this->store['id'] . '/pickups');
        $I->seeResponseCodeIs(HttpCode::FORBIDDEN);
    }

    public function testSinglePickupInListExistsAndIsValid(ApiTester $I): void
    {
        $pickupBaseDate = Carbon::now()->add('2 days');
        $pickupBaseDate->hours(16)->minutes(55)->seconds(0);
        $I->addPickup($this->store['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);
        $I->login($this->user['email']);
        $I->sendGET('api/stores/' . $this->store['id'] . '/pickups');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->canSeeResponseContainsJson([
            'pickups' => [[
            'date' => $pickupBaseDate->toIso8601String(),
            'totalSlots' => 2,
            'occupiedSlots' => [],
            'isAvailable' => true
            ]]
        ]);
    }

    public function testListPickupWithHistoryShowFutureAndHistory(ApiTester $I): void
    {
        $pickupBaseDate = Carbon::now()->addMinute(1)->second(0);

        // Pickup 5 hours ago - regular replaced by manual planed
        $regularPickup5HoursBeforeDate = $pickupBaseDate->copy()->subHours(5);
        $I->addRecurringPickup($this->store['id'], [
            'dow' => $regularPickup5HoursBeforeDate->dayOfWeek,
            'time' => sprintf('%02d:%s:00', $regularPickup5HoursBeforeDate->hour, $pickupBaseDate->minute),
            'fetcher' => 4
        ]);
        $I->addPickup($this->store['id'], ['time' => $regularPickup5HoursBeforeDate, 'fetchercount' => 4]);
        $I->addPicker($this->store['id'], $this->user['id'], ['date' => $regularPickup5HoursBeforeDate]);

        // Pickup 3 hours ago - manual planed
        $manualPickup3HoursBeforeDate = $pickupBaseDate->copy()->subHours(3);
        $I->addPickup($this->store['id'], ['time' => $manualPickup3HoursBeforeDate, 'fetchercount' => 1]);
        $I->addPicker($this->store['id'], $this->user['id'], ['date' => $manualPickup3HoursBeforeDate]);

        // Pickup 1 hour ago - regular planed (not replaced by manual planed)
        $regularPickup1HoursBeforeDate = $pickupBaseDate->copy()->subHours(1);
        $I->addRecurringPickup($this->store['id'], [
            'dow' => $regularPickup1HoursBeforeDate->dayOfWeek,
            'time' => sprintf('%02d:%s:00', $regularPickup1HoursBeforeDate->hour, $pickupBaseDate->minute),
            'fetcher' => 3
        ]);
        $I->addPicker($this->store['id'], $this->user['id'], ['date' => $regularPickup1HoursBeforeDate]);

        // Pickup in future
        $I->addPickup($this->store['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);

        // Test
        $I->login($this->user['email']);
        $I->sendGET('api/stores/' . $this->store['id'] . '/pickups');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->canSeeResponseContainsJson([
            'pickups' => [
                [
                    'date' => $pickupBaseDate->toIso8601String(),
                    'totalSlots' => 2,
                    'occupiedSlots' => [],
                    'isAvailable' => true
                ], [
                    'date' => $manualPickup3HoursBeforeDate->toIso8601String(),
                    'totalSlots' => 1,
                    'occupiedSlots' => [['isConfirmed' => true, 'profile' => ['id' => $this->user['id']]]],
                    'isAvailable' => false
                ], [
                    'date' => $regularPickup1HoursBeforeDate->toIso8601String(),
                    'totalSlots' => 3,
                    'occupiedSlots' => [['isConfirmed' => true, 'profile' => ['id' => $this->user['id']]]],
                    'isAvailable' => false
                ], [
                    'date' => $regularPickup5HoursBeforeDate->toIso8601String(),
                    'totalSlots' => 4,
                    'occupiedSlots' => [['isConfirmed' => true, 'profile' => ['id' => $this->user['id']]]],
                    'isAvailable' => false
                ]
            ]
        ]);
    }

    public function testSinglePickupHistoryInListExistsAndIsValid(ApiTester $I): void
    {
        $refDate = Carbon::now()->subYears(3)->subHours(8);
        $I->haveInDatabase('fs_abholer', [
            'betrieb_id' => $this->store['id'],
            'foodsaver_id' => $this->storeCoordinator['id'],
            'date' => $refDate
        ]);

        $e = $I->grabFromDatabase('fs_abholer', 'date', [
            'betrieb_id' => $this->store['id'],
            'foodsaver_id' => $this->storeCoordinator['id']
        ]);

        $startDate = $refDate->copy()->subYears(2);
        $endDate = $refDate->copy()->addYears(2);

        $I->login($this->storeCoordinator['email']);
        $I->sendGET('api/stores/' . $this->store['id'] . '/history/' . $startDate->toIso8601String() . '/' . $endDate->toIso8601String());
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->canSeeResponseContainsJson([
            'pickups' => [
                [
                    'occupiedSlots' => [
                        [
                            'profile' => [
                                'id' => $this->storeCoordinator['id']
                            ],
                            'date' => $refDate->toIso8601String(),
                            'date_ts' => $refDate->timestamp,
                            'confirmed' => 0
                        ]
                    ]
            ]]
        ]);
    }

    public function cannotSignOutOfPastPickup(ApiTester $I): void
    {
        $pickupBaseDate = Carbon::now()->sub('2 days');
        $pickupBaseDate->hours(14)->minutes(45)->seconds(0);
        $I->addPickup($this->store['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);
        $I->addPicker($this->store['id'], $this->user['id'], ['date' => $pickupBaseDate]);

        $I->login($this->user['email']);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendDELETE('api/stores/' . $this->store['id'] . '/pickups/' . $pickupBaseDate->toIso8601String() . '/' . $this->user['id']);
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function canSignOutOfPickupWithMessage(ApiTester $I): void
    {
        $pickupBaseDate = Carbon::now()->add('2 days');
        $pickupBaseDate->hours(14)->minutes(45)->seconds(0);
        $I->addPickup($this->store['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);
        $I->addPicker($this->store['id'], $this->user['id'], ['date' => $pickupBaseDate]);

        $I->login($this->storeCoordinator['email']);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendDELETE('api/stores/' . $this->store['id'] . '/pickups/' . $pickupBaseDate->toIso8601String() . '/' . $this->user['id'], ['sendKickMessage' => true, 'message' => 'Hallo']);
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    public function checkDistrictRules(ApiTester $I): void
    {
        /*
              Create PickupRule

            7 days, max pickup 3 overall, max 2 per day, rule ignored 48 hours before pickup
        */
        $I->createDistrictPickupRule((int)$this->region['id'], '7', '3', '2', '48');
        $I->login($this->user['email']);

        // Test for maximum 3 pickups in 7 days over multiple stores.
        $pickupBaseDate = Carbon::now()->add('3 days');
        $pickupBaseDate->hours(10)->minutes(00)->seconds(0);
        $I->addPickup($this->store2['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);
        $I->addPicker($this->store2['id'], $this->user['id'], ['date' => $pickupBaseDate]);

        $pickupBaseDate = Carbon::now()->add('4 days');
        $pickupBaseDate->hours(11)->minutes(00)->seconds(0);
        $I->addPickup($this->store3['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);
        $I->addPicker($this->store3['id'], $this->user['id'], ['date' => $pickupBaseDate]);

        $pickupBaseDate = Carbon::now()->add('5 days');
        $pickupBaseDate->hours(11)->minutes(00)->seconds(0);
        $I->addPickup($this->store4['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);

        // This signup is ok because it is the third one
        $I->sendGET('api/stores/' . $this->store4['id'] . '/pickupRuleCheck/' . $pickupBaseDate->toIso8601String() . '/' . $this->user['id']);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->canSeeResponseContainsJson([
            'result' => true
        ]);

        // this signup breaks the rule as it is the fourth one
        $I->addPicker($this->store4['id'], $this->user['id'], ['date' => $pickupBaseDate]);

        $pickupBaseDate = Carbon::now()->add('6 days');
        $pickupBaseDate->hours(11)->minutes(00)->seconds(0);
        $I->addPickup($this->store4['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);
        $I->sendGET('api/stores/' . $this->store4['id'] . '/pickupRuleCheck/' . $pickupBaseDate->toIso8601String() . '/' . $this->user['id']);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->canSeeResponseContainsJson([
            'result' => false
        ]);

        // Test for third signups on the same day over multiple stores
        $pickupBaseDate = Carbon::now()->add('20 days');
        $pickupBaseDate->hours(10)->minutes(00)->seconds(0);
        $I->addPickup($this->store2['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);
        $I->addPicker($this->store2['id'], $this->user['id'], ['date' => $pickupBaseDate]);

        $pickupBaseDate = Carbon::now()->add('20 days');
        $pickupBaseDate->hours(11)->minutes(00)->seconds(0);
        $I->addPickup($this->store3['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);
        $I->addPicker($this->store3['id'], $this->user['id'], ['date' => $pickupBaseDate]);

        $pickupBaseDate = Carbon::now()->add('20 days');
        $pickupBaseDate->hours(12)->minutes(00)->seconds(0);
        $I->addPickup($this->store4['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);
        $I->sendGET('api/stores/' . $this->store4['id'] . '/pickupRuleCheck/' . $pickupBaseDate->toIso8601String() . '/' . $this->user['id']);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->canSeeResponseContainsJson([
            'result' => false
        ]);

        // test for ignoring of the rule if signup date is closer then ignorerulehours
        $pickupBaseDate = Carbon::now()->addDay();
        $pickupBaseDate->hours(10)->minutes(00)->seconds(0);
        $I->addPickup($this->store2['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);
        $I->addPicker($this->store2['id'], $this->user['id'], ['date' => $pickupBaseDate]);

        $pickupBaseDate = Carbon::now()->addDay();
        $pickupBaseDate->hours(11)->minutes(00)->seconds(0);
        $I->addPickup($this->store4['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);
        $I->addPicker($this->store4['id'], $this->user['id'], ['date' => $pickupBaseDate]);

        $pickupBaseDate = Carbon::now()->addDay();
        $pickupBaseDate->hours(12)->minutes(00)->seconds(0);
        $I->addPickup($this->store4['id'], ['time' => $pickupBaseDate, 'fetchercount' => 2]);
        $I->sendGET('api/stores/' . $this->store4['id'] . '/pickupRuleCheck/' . $pickupBaseDate->toIso8601String() . '/' . $this->user['id']);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->canSeeResponseContainsJson([
            'result' => true
        ]);
    }
}
