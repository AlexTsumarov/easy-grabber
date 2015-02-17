<?php 
$I = new AcceptanceTester($scenario);

$I->wantTo('Smoke test');

$I->amGoingTo('Login');
$I->amOnPage('/wp-login.php');
$I->fillField('Username', 'admin');
$I->fillField('Password','admin');
$I->click('Log In');

$I->amGoingTo('Activate a plugin');
$I->amOnPage('/wp-admin/plugins.php');
//$I->click('#easy-grabber .activate a');
//$I->wait(2);

//$I->amGoingTo('Run a grabber');
//$I->amOnPage('/wp-admin/admin.php?page=grabber_run');
//$I->click('start');

//$I->amGoingTo('Check the results');
//$I->waitForElement('');