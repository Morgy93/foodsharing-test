<?php

declare(strict_types=1);

namespace Tests\Api;

use Codeception\Example;
use Codeception\Util\HttpCode;
use Tests\Support\ApiTester;

class LoginApiCest
{
    /**
     * @example ["createFoodsaver", "Wähle den Bezirk aus"]
     * @example ["createFoodsharer", "Viel Spaß beim Retten"]
     * @example ["createStoreCoordinator", "Dein Stammbezirk ist"]
     * @example ["createAmbassador", "Dein Stammbezirk ist"]
     * @example ["createOrga", "Dein Stammbezirk ist"]
     */
    public function checkLogin(ApiTester $I, Example $example): void
    {
        $pass = sq('pass');
        $user = $I->{$example[0]}($pass);

        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');

        $I->sendPOST('api/user/login', [
            'email' => $user['email'],
            'password' => $pass
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeInDatabase('fs_foodsaver', [
            'email' => $user['email']
        ]);
    }

    /**
     * @example ["createFoodsaver", "Hallo ", "Foodsaver für"]
     */
    public function checkInvalidLogin(ApiTester $I, Example $example): void
    {
        $pass = sq('pass');
        $user = call_user_func([$I, $example[0]], $pass);

        $I->haveHttpHeader('Content-Type', 'application/x-www-form-urlencoded');

        $I->sendPOST('api/user/login', [
            'email' => $user['email'],
            'password' => 'WROOOONG'
        ]);

        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseContains('email or password are invalid');
    }
}
