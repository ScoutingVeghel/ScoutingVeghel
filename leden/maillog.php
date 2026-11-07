<?php
require_once __DIR__.'/beheer-security.php';ledenRequireLogin();if(!adminCan('maillog'))adminAccessDenied();
require_once __DIR__.'/beheer-document.php';
ob_start();publicBeheerHeader('maillog');$portalModuleHeader=ob_get_clean();
require dirname(__DIR__).'/admin/module-page.php';
require dirname(__DIR__).'/admin/maillog-module.php';
