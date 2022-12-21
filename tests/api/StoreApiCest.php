<?php

namespace api;

use ApiTester;
use Codeception\Util\HttpCode as Http;
use Faker;
use Foodsharing\Modules\Core\DBConstants\Store\Milestone;
use Foodsharing\Modules\Core\DBConstants\Store\StoreLogAction;
use Foodsharing\Modules\Core\DBConstants\Unit\UnitType;

/**
 * Tests for the store api.
 */
class StoreApiCest
{
    private $store;
    private $foodsharer;
    private $user;
    private $teamMember;
    private $manager;
    private $region;
    private $faker;

    private const API_STORES = 'api/stores';
    private const API_REGIONS = 'api/region';
    private const EMAIL = 'email';
    private const ID = 'id';

    public function _before(ApiTester $I)
    {
        $this->region = $I->createRegion();
        $this->store = $I->createStore($this->region['id']);
        $this->foodsharer = $I->createFoodsharer(null, ['verified' => 0]);
        $this->user = $I->createFoodsaver(null, ['verified' => 0]);
        $this->unverifiedUser = $I->createFoodsaver(null, ['verified' => 0]);
        $this->teamMember = $I->createFoodsaver();
        $I->addStoreTeam($this->store[self::ID], $this->teamMember[self::ID], false);
        $this->manager = $I->createStoreCoordinator(null, ['bezirk_id' => $this->region['id']]);
        $I->addStoreTeam($this->store[self::ID], $this->manager[self::ID], true);
        $this->faker = Faker\Factory::create('de_DE');
    }

    public function canNotGetAccessToCommonStoreMetadataAsUnknownUser(ApiTester $I)
    {
        $I->sendGET(self::API_STORES . '/meta-data');
        $I->seeResponseCodeIs(Http::UNAUTHORIZED);
    }

    public function getCommonStoreMetadataAsFoodsaver(ApiTester $I)
    {
        $I->login($this->user[self::EMAIL]);
        $I->sendGET(self::API_STORES . '/meta-data');
        $I->seeResponseCodeIs(Http::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['maxCountPickupSlot' => 10,
                                    'storeChains' => null]);

        $groceries = $I->grabDataFromResponseByJsonPath('$.groceries');
        $I->assertNotCount(0, $groceries);
        $categories = $I->grabDataFromResponseByJsonPath('$.categories');
        $I->assertNotCount(0, $categories);
        $status = $I->grabDataFromResponseByJsonPath('$.status');
        $I->assertNotCount(0, $status);
        $weight = $I->grabDataFromResponseByJsonPath('$.weight');
        $I->assertNotCount(0, $weight);
        $convinceStatus = $I->grabDataFromResponseByJsonPath('$.convinceStatus');
        $I->assertNotCount(0, $convinceStatus);
        $publicTimes = $I->grabDataFromResponseByJsonPath('$.publicTimes');
        $I->assertNotCount(0, $publicTimes);
    }

    public function getCommonStoreMetadataAsStoreOwner(ApiTester $I)
    {
        $I->login($this->manager[self::EMAIL]);
        $I->sendGET(self::API_STORES . '/meta-data');
        $I->seeResponseCodeIs(Http::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['maxCountPickupSlot' => 10]);
        $storeChains = $I->grabDataFromResponseByJsonPath('$.storeChains');
        $I->assertNotCount(0, $storeChains);
    }

    public function canAnonymUserNotAccessToGetListOfStoresInRegion(ApiTester $I)
    {
        $regionRelatedRegion = $I->createRegion();

        $I->sendGET(self::API_REGIONS . '/' . $regionRelatedRegion['id'] . '/stores');
        $I->seeResponseCodeIs(Http::UNAUTHORIZED);
    }

    public function canNotAccessToGetListOfStoresWithInvalidRegion(ApiTester $I)
    {
        $I->sendGET(self::API_REGIONS . '/1234/stores');
        $I->seeResponseCodeIs(Http::UNAUTHORIZED);
    }

    public function foodsharerCanNotAccessToGetListOfStoresInRegion(ApiTester $I)
    {
        $regionRelatedRegion = $I->createRegion();

        $I->login($this->foodsharer[self::EMAIL]);
        $I->sendGET(self::API_REGIONS . '/' . $regionRelatedRegion['id'] . '/stores');
        $I->seeResponseCodeIs(Http::FORBIDDEN);
    }

    public function unverifiedFoodsaverCanAccessToGetListOfStoresInRegion(ApiTester $I)
    {
        $regionRelatedRegion = $I->createRegion();

        $I->login($this->unverifiedUser[self::EMAIL]);
        $I->sendGET(self::API_REGIONS . '/' . $regionRelatedRegion['id'] . '/stores');
        $I->seeResponseCodeIs(Http::OK);
    }

    public function verifiedFoodsaverCanAccessToGetListOfStoresInRegion(ApiTester $I)
    {
        $regionRelatedRegion = $I->createRegion();

        $I->login($this->user[self::EMAIL]);
        $I->sendGET(self::API_REGIONS . '/' . $regionRelatedRegion['id'] . '/stores');
        $I->seeResponseCodeIs(Http::OK);
    }

    public function foodsaverWithRegionRelationCanAccessToGetListOfStoresInRegion(ApiTester $I)
    {
        $regionRelatedRegion = $I->createRegion();
        $I->addRegionMember($regionRelatedRegion['id'], $this->user['id'], true);

        $I->login($this->user[self::EMAIL]);
        $I->sendGET(self::API_REGIONS . '/' . $regionRelatedRegion['id'] . '/stores');
        $I->seeResponseCodeIs(Http::OK);
    }

    public function testContentofGetListOfStoresInRegion(ApiTester $I)
    {
        $regionTop = $I->createRegion(null, ['type' => UnitType::CITY]);
        $I->addRegionMember($regionTop['id'], $this->user['id'], true);

        $regionChild1 = $I->createRegion(null, ['parent_id' => $regionTop['id'], 'type' => UnitType::PART_OF_TOWN]);
        $store1 = $I->createStore($regionChild1['id']);
        $regionChild2 = $I->createRegion(null, ['parent_id' => $regionTop['id'], 'type' => UnitType::PART_OF_TOWN]);
        $store2 = $I->createStore($regionChild2['id']);

        $I->login($this->user[self::EMAIL]);
        $I->sendGET(self::API_REGIONS . '/' . $regionTop['id'] . '/stores');
        $I->seeResponseCodeIs(Http::OK);
        $I->seeResponseIsJson();

        $ids = $I->grabDataFromResponseByJsonPath('stores.*.id');
        $I->assertContains($store1[self::ID], $ids);
        $I->assertContains($store2[self::ID], $ids);

        $names = $I->grabDataFromResponseByJsonPath('stores.*.name');
        foreach ($names as $name) {
            $I->assertNull($name);
        }
    }

    public function testContentofGetListOfStoresInRegionExpanded(ApiTester $I)
    {
        $regionTop = $I->createRegion(null, ['type' => UnitType::CITY]);
        $I->addRegionMember($regionTop['id'], $this->user['id'], true);

        $regionChild1 = $I->createRegion(null, ['parent_id' => $regionTop['id'], 'type' => UnitType::PART_OF_TOWN]);
        $store1 = $I->createStore($regionChild1['id']);
        $regionChild2 = $I->createRegion(null, ['parent_id' => $regionTop['id'], 'type' => UnitType::PART_OF_TOWN]);
        $store2 = $I->createStore($regionChild2['id']);

        $I->login($this->user[self::EMAIL]);
        $I->sendGET(self::API_REGIONS . '/' . $regionTop['id'] . '/stores', ['expand' => true]);
        $I->seeResponseCodeIs(Http::OK);
        $I->seeResponseIsJson();

        $ids = $I->grabDataFromResponseByJsonPath('stores.*.id');
        $I->assertContains($store1[self::ID], $ids);
        $I->assertContains($store2[self::ID], $ids);

        $names = $I->grabDataFromResponseByJsonPath('stores.*.name');
        $I->assertContains($store1['name'], $names);
        $I->assertContains($store2['name'], $names);
    }

    public function getStore(ApiTester $I)
    {
        $I->login($this->teamMember[self::EMAIL]);
        $I->sendGET(self::API_STORES . '/' . $this->store[self::ID]);
        $I->seeResponseCodeIs(Http::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['id' => $this->store[self::ID]]);
        $I->seeResponseContainsJson(['phone' => $this->store['telefon']]);
    }

    public function canOnlyAccessStoreAsFoodsaver(ApiTester $I)
    {
        $I->sendGET(self::API_STORES . '/' . $this->store[self::ID]);
        $I->seeResponseCodeIs(Http::UNAUTHORIZED);

        $I->login($this->foodsharer[self::EMAIL]);
        $I->sendGET(self::API_STORES . '/' . $this->store[self::ID]);
        $I->seeResponseCodeIs(Http::FORBIDDEN);
    }

    public function canOnlySeeStoreDetailsAsMember(ApiTester $I)
    {
        $I->login($this->user[self::EMAIL]);
        $I->sendGET(self::API_STORES . '/' . $this->store[self::ID]);
        $I->seeResponseCodeIs(Http::OK);
        $I->seeResponseContainsJson(['id' => $this->store[self::ID]]);
        $I->dontSeeResponseContainsJson(['phone' => $this->store['telefon']]);
    }

    public function patchStoreTeamStatusOk(ApiTester $I)
    {
        $I->login($this->manager[self::EMAIL]);
        $I->sendPATCH(self::API_STORES . '/' . $this->store[self::ID], ['teamStatus' => 2]);
        $I->seeResponseCodeIs(Http::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['id' => $this->store[self::ID]]);
        $I->seeResponseContainsJson(['phone' => $this->store['telefon']]);
    }

    public function patchStoreTeamStatusNotFound(ApiTester $I)
    {
        $I->login($this->manager[self::EMAIL]);
        $I->sendPATCH(self::API_STORES . '/' . $this->store[self::ID] + 1, ['teamStatus' => 2]);
        $I->seeResponseCodeIs(Http::NOT_FOUND);
    }

    public function patchStoreTeamStatusInvalid(ApiTester $I)
    {
        $I->login($this->manager[self::EMAIL]);
        $I->sendPATCH(self::API_STORES . '/' . $this->store[self::ID], ['teamStatus' => 3]);
        $I->seeResponseCodeIs(Http::BAD_REQUEST);

        $I->login($this->manager[self::EMAIL]);
        $I->sendPATCH(self::API_STORES . '/' . $this->store[self::ID], ['teamStatus' => 'a']);
        $I->seeResponseCodeIs(Http::BAD_REQUEST);
    }

    public function canPatchOnlyAccessStoreAsFoodsaver(ApiTester $I)
    {
        $I->sendPATCH(self::API_STORES . '/' . $this->store[self::ID], ['teamStatus' => 2]);
        $I->seeResponseCodeIs(Http::UNAUTHORIZED);

        $I->login($this->foodsharer[self::EMAIL]);
        $I->sendPATCH(self::API_STORES . '/' . $this->store[self::ID], ['teamStatus' => 2]);
        $I->seeResponseCodeIs(Http::FORBIDDEN);

        $I->login($this->teamMember[self::EMAIL]);
        $I->sendPATCH(self::API_STORES . '/' . $this->store[self::ID], ['teamStatus' => 2]);
        $I->seeResponseCodeIs(Http::FORBIDDEN);
    }

    public function canWriteStoreWallpostAndGetAllPosts(ApiTester $I): void
    {
        $I->login($this->teamMember[self::EMAIL]);
        $newWallPost = $this->faker->realText(200);
        $I->sendPOST(self::API_STORES . '/' . $this->store[self::ID] . '/posts', ['text' => $newWallPost]);

        $I->seeResponseCodeIs(Http::OK);
        $I->seeResponseIsJson();
        $I->seeInDatabase('fs_betrieb_notiz', [
            'foodsaver_id' => $this->teamMember[self::ID],
            'betrieb_id' => $this->store[self::ID],
            'text' => $newWallPost,
        ]);

        $I->sendGET(self::API_STORES . '/' . $this->store[self::ID] . '/posts');

        $I->seeResponseCodeIs(Http::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['text' => $newWallPost]);
    }

    public function noStoreWallIfNotInTeam(ApiTester $I): void
    {
        $I->login($this->user[self::EMAIL]);

        $I->sendGET(self::API_STORES . '/' . $this->store[self::ID] . '/posts');

        $I->seeResponseCodeIs(Http::FORBIDDEN);

        $I->sendPOST(self::API_STORES . '/' . $this->store[self::ID] . '/posts', ['text' => 'Lorem ipsum.']);

        $I->seeResponseCodeIs(Http::FORBIDDEN);
    }

    public function noStoreWallIfNotLoggedIn(ApiTester $I): void
    {
        $I->sendGET(self::API_STORES . '/' . $this->store[self::ID] . '/posts');

        $I->seeResponseCodeIs(Http::UNAUTHORIZED);

        $I->sendPOST(self::API_STORES . '/' . $this->store[self::ID] . '/posts', ['text' => 'Lorem ipsum.']);

        $I->seeResponseCodeIs(Http::UNAUTHORIZED);
    }

    /**
     * All team members can remove their own posts at any time.
     */
    public function canRemoveOwnStorePost(ApiTester $I): void
    {
        $wallPost = [
            'betrieb_id' => $this->store[self::ID],
            'foodsaver_id' => $this->teamMember[self::ID],
            'text' => $this->faker->realText(100),
            'zeit' => $this->faker->dateTimeBetween('-14 days', '-30m')->format('Y-m-d H:i:s'),
            'milestone' => Milestone::NONE,
        ];
        $postId = $I->haveInDatabase('fs_betrieb_notiz', $wallPost);

        $I->login($this->teamMember[self::EMAIL]);

        $I->sendDELETE(self::API_STORES . '/' . $this->store[self::ID] . '/posts/' . $postId);

        $I->seeResponseCodeIs(Http::OK);
        $I->seeResponseIsJson();
        $I->dontSeeInDatabase('fs_betrieb_notiz', ['id' => $postId]);

        $I->seeInDatabase('fs_store_log', [
            'store_id' => $this->store[self::ID],
            'fs_id_a' => $this->teamMember[self::ID],
            'fs_id_p' => $this->teamMember[self::ID],
            'content' => $wallPost['text'],
            'date_reference' => $wallPost['zeit'],
            'action' => StoreLogAction::DELETED_FROM_WALL,
        ]);
    }

    /**
     * Store managers can remove posts by others if they are older than 1 month.
     */
    public function storeManagerCanRemoveOldStorePost(ApiTester $I): void
    {
        $wallPost = [
            'betrieb_id' => $this->store[self::ID],
            'foodsaver_id' => $this->teamMember[self::ID],
            'text' => $this->faker->realText(100),
            'zeit' => $this->faker->dateTimeBetween('-66 days', '-33 days')->format('Y-m-d H:i:s'),
            'milestone' => Milestone::NONE,
        ];
        $postId = $I->haveInDatabase('fs_betrieb_notiz', $wallPost);

        $I->login($this->manager[self::EMAIL]);

        $I->sendDELETE(self::API_STORES . '/' . $this->store[self::ID] . '/posts/' . $postId);

        $I->seeResponseCodeIs(Http::OK);
        $I->dontSeeInDatabase('fs_betrieb_notiz', ['id' => $postId]);

        $I->seeInDatabase('fs_store_log', [
            'store_id' => $this->store[self::ID],
            'fs_id_a' => $this->manager[self::ID],
            'fs_id_p' => $this->teamMember[self::ID],
            'content' => $wallPost['text'],
            'date_reference' => $wallPost['zeit'],
            'action' => StoreLogAction::DELETED_FROM_WALL,
        ]);
    }

    public function storeManagerCannotRemoveNewStorePost(ApiTester $I): void
    {
        $wallPost = [
            'betrieb_id' => $this->store[self::ID],
            'foodsaver_id' => $this->teamMember[self::ID],
            'text' => $this->faker->realText(100),
            'zeit' => $this->faker->dateTimeBetween('-14 days', '-30m')->format('Y-m-d H:i:s'),
            'milestone' => Milestone::NONE,
        ];
        $postId = $I->haveInDatabase('fs_betrieb_notiz', $wallPost);

        $I->login($this->manager[self::EMAIL]);

        $I->sendDELETE(self::API_STORES . '/' . $this->store[self::ID] . '/posts/' . $postId);

        $I->seeResponseCodeIs(Http::FORBIDDEN);
        $I->seeInDatabase('fs_betrieb_notiz', ['id' => $postId]);
    }
}
