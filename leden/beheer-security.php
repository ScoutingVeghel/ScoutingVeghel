<?php
require_once dirname(__DIR__).'/admin/security.php';
function ledenSyncIdentity() {
    $_SESSION['LEDEN_NAME']=$_SESSION['ADMIN_NAME'];
    $_SESSION['LEDEN_VERSION']=$_SESSION['ADMIN_AUTH_VERSION'];
    $_SESSION['LEDEN_ROLE']=adminCan('leden-centraal')?'central':'group';
    $_SESSION['LEDEN_MANAGE_ACCOUNTS']=adminCan('accounts');
}
function ledenLoggedIn() { if(!adminIsLoggedIn() || !adminCan('leden')) return false;ledenSyncIdentity();return true; }
function ledenRequireLogin() { adminRequireLogin();if(!adminCan('leden'))adminAccessDenied();ledenSyncIdentity(); }
function ledenCanManageAccounts() { return adminCan('accounts'); }
function ledenClearSession() { adminClearSession(); }
function ledenCsrf() { return adminCsrfToken(); }
function ledenRateAttempt($name) { return adminRateAttempt($name); }
