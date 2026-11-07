<?php
require_once __DIR__.'/beheer-security.php';ledenRequireLogin();if(!adminCan('maillog'))adminAccessDenied();
require dirname(__DIR__).'/admin/maillog-module.php';
