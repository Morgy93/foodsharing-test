<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

$phonenumber = '+49483123213';
$mobilenumber = '+491518417482';
$I = new AcceptanceTester($scenario);
$I->wantTo('edit my profile fields as a foodsaver');

$foodsaver = $I->createFoodsaver(null, ['name' => 'fs', 'nachname' => 'one']);

$I->login($foodsaver['email']);

$I->amOnPage('/?page=settings');
$I->waitForElement('#telefon', 10);

/*
 * This part should check geocoding. Apparently, the Typeahead autocompletion does not come up in selenium. Help appreciated.
$I->fillField('#addresspicker', 'Kantstraße 20, Wurzen');
$I->wait(1);
$I->click('Kantstraße 20, Wurzen, Germany', '.tt-dropdown-menu');

$I->see('Kantstraße 20', '#anschrift');
$I->see('Wurzen', '#ort');
$I->see('04808', '#plz');
*/

$I->fillField('#telefon', $phonenumber);
$I->fillField('#handy', $mobilenumber);
$I->fillField('#geb_datum', '1988-05-31');
$I->fillField('#homepage', 'www.matthias-larisch.de');
$I->fillField('#about_me_public', 'Ich mag foodsharing.');

$I->click('Speichern');
$I->see('Änderungen wurden gespeichert');
$I->waitForPageBody();
/*
 * There is no way to change these without typeahead/geocode. Maybe some complaining users are right? :)
$I->see('Kantstraße 20', '#anschrift');
$I->see('Wurzen', '#ort');
$I->see('04808', '#plz');
*/
$I->seeInField('#telefon', $phonenumber);
$I->seeInField('#handy', $mobilenumber);
$I->seeInField('#geb_datum', '1988-05-31');
$I->seeInField('#homepage', 'http://www.matthias-larisch.de');
$I->seeInField('#about_me_public', 'Ich mag foodsharing.');

$I->fillField('#homepage', 'https://www.matthias-larisch.de');
$I->click('Speichern');
$I->see('Änderungen wurden gespeichert');
$I->seeInField('#homepage', 'https://www.matthias-larisch.de');
