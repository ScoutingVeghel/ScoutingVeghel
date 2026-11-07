<?php
require_once __DIR__.'/security.php';require_once __DIR__.'/login-log-functions.php';require_once __DIR__.'/portal.php';require_once __DIR__.'/mfa-functions.php';
$return=portalSafeReturn($_POST['return']??($_GET['return']??($_SESSION['PORTAL_LOGIN_RETURN']??'/admin/')));
function loginDestination($return) {
 if(adminMfaRequired() && empty($_SESSION['ADMIN_MFA_ACTIVE'])) {
    $_SESSION['MFA_RETURN']=portalSafeReturn($return);
    return '/admin/mfa.php';
 }
 if(!empty($_SESSION['ADMIN_MFA_ACTIVE']) && empty($_SESSION['ADMIN_MFA_CONFIRMED'])) {
    adminClearSession();
    return '/admin/login.php?return='.rawurlencode(portalSafeReturn($return));
 }
 if(strpos($return,'/leden/')===0 && !adminCan('leden'))return '/admin/';
 if(strpos($return,'/verhuur/')===0 && !array_intersect(['reserveringen','kalender','zoeken','toevoegen','maillog','eigen-groepen'],$_SESSION['ADMIN_MODULES']??[])) return adminCan('leden')?'/leden/beheer.php':'/admin/';
 return $return;
}
if(adminIsLoggedIn(true)){header('Location: '.loginDestination($return),true,303);exit;}
$error='';
if(isset($_SESSION['ADMIN_PENDING']) && time()-($_SESSION['ADMIN_PENDING_TIME']??0)>300){adminClearSession();$error='Je inlogpoging is verlopen. Log opnieuw in.';}
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
 if (($_POST['action'] ?? '') === 'back' && isset($_SESSION['ADMIN_PENDING'])) {
    adminClearSession();
    header('Location: /admin/login.php?return=' . rawurlencode($return), true, 303);
    exit;
 }
 $auditName=$_SESSION['ADMIN_PENDING']??($_POST['gebruikersnaam']??'');
 $auditMethod=isset($_SESSION['ADMIN_PENDING'])?'mfa':'password';
 $auditResult=$auditMethod==='mfa'?'bad_mfa':'bad_password';
 try {
    if(isset($_SESSION['ADMIN_PENDING'])) {
        $name=$_SESSION['ADMIN_PENDING'];$version=$_SESSION['ADMIN_PENDING_VERSION']??-1;
        $db=siteDatabaseConnection();$st=$db->prepare('SELECT auth_version,mfa_secret FROM verhuur_administrators WHERE naam=?');$st->bind_param('s',$name);$st->execute();$row=$st->get_result()->fetch_assoc();
        if(!adminRateAttempt('mfa:'.$name)){$auditResult='rate_limited';http_response_code(429);throw new InvalidArgumentException('Te veel pogingen. Probeer het over 15 minuten opnieuw.');}
        if(!$row || (int)$row['auth_version']!==(int)$version || !adminMfaVerify($db,$name,$_POST['mfa_code']??''))throw new InvalidArgumentException('De code is onjuist, verlopen of al gebruikt.');
        adminRateSuccess('mfa:'.$name);
        adminCompleteLogin($name,$version,$row['mfa_secret']);
    } else {
        $name=$_POST['gebruikersnaam']??'';$password=$_POST['wachtwoord']??'';
        if(!is_string($name)||!is_string($password)||$name===''||strlen($name)>133||$password===''||strlen($password)>72||strpos($password,"\0")!==false)throw new InvalidArgumentException('Naam of wachtwoord is onjuist.');
        if(!adminRateAttempt($name)){$auditResult='rate_limited';http_response_code(429);throw new InvalidArgumentException('Te veel pogingen. Probeer het over 15 minuten opnieuw.');}
        if(!adminCheckCredentials(siteDatabaseConnection(),$name,$password))throw new InvalidArgumentException('Naam of wachtwoord is onjuist.');
        adminRateSuccess($name);
    }
    if(isset($_SESSION['ADMIN_PENDING'])) {
        portalLoginRecord('mfa_required',$auditName,$return,$auditMethod);
        $_SESSION['PORTAL_LOGIN_RETURN']=$return;
        header('Location: /admin/login.php',true,303);exit;
    }
    if(adminIsLoggedIn(true)){portalLoginRecord('success',$auditName,$return,$auditMethod);header('Location: '.loginDestination($return),true,303);exit;}
 } catch(InvalidArgumentException $e){portalLoginRecord($auditResult,$auditName,$return,$auditMethod);$error=$e->getMessage();}
 catch(Throwable $e){portalLoginRecord('error',$auditName,$return,$auditMethod);http_response_code(503);$error='Inloggen is tijdelijk niet beschikbaar.';}
}
echo str_replace('class="portal-page"', 'class="portal-page portal-login-page"', portalDocument('Inloggen'));
?><header class="admin-header"><a class="admin-brand" href="/admin/"><img src="/admin/assets/logo.svg" alt="Scouting Veghel"><span>Scouting Veghel</span></a></header><main class="portal-login"><section><img class="portal-login-logo" src="/admin/assets/logo.svg" alt="Scouting Veghel"><h1><?= isset($_SESSION['ADMIN_PENDING'])?'Tweestapsverificatie':'Inloggen' ?></h1><?php if($error): ?><p class="portal-error" role="alert"><?= portalEscape($error) ?></p><?php endif ?><form method="post" action="/admin/login.php"><input type="hidden" name="return" value="<?= portalEscape($return) ?>"><?php if(isset($_SESSION['ADMIN_PENDING'])): ?><label>Code uit je authenticator-app of herstelcode <input name="mfa_code" maxlength="16" autocomplete="one-time-code" required autofocus></label><?php else: ?><label>Gebruikersnaam <input name="gebruikersnaam" autocomplete="username" maxlength="133" required></label><label>Wachtwoord <input type="password" name="wachtwoord" autocomplete="current-password" maxlength="72" required></label><?php endif ?><button>Inloggen</button></form><?php if(isset($_SESSION['ADMIN_PENDING'])): ?><form method="post" action="/admin/login.php" class="portal-login-back"><input type="hidden" name="return" value="<?= portalEscape($return) ?>"><button type="submit" name="action" value="back">Terug</button></form><?php endif ?></section></main></body></html>
