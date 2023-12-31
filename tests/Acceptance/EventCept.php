<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

$I = new AcceptanceTester($scenario);

$region = $I->createRegion();
$foodsaver = $I->createFoodsaver(null, ['bezirk_id' => $region['id']]);

$I->wantTo('create an event');
$I->login($foodsaver['email']);

$I->amOnPage($I->eventAddUrl($region['id']));
$I->see('Was ist das für ein Event?');
