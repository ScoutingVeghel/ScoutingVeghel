<?php
require_once __DIR__ . '/security.php';
adminRequireLogin();
require_once dirname(__DIR__) . '/php/portal-functions.php';
date_default_timezone_set('Europe/Amsterdam');
$database = siteDatabaseConnection();
