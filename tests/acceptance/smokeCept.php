<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('Check WP install');
$I->amOnPage('/wp-login.php');
$I->fillField('Username', 'admin');
$I->fillField('Password','admin');
$I->click('Log In');
$I->see('Dashboard');