<?php
require_once __DIR__.'/security.php';adminRequireLogin();require_once __DIR__.'/portal.php';
$portal=$_GET['portal']??'admin';if(!is_string($portal)||!in_array($portal,['admin','beheer','leden'],true))$portal='admin';
$db=siteDatabaseConnection();$name=$_SESSION['ADMIN_NAME'];$error='';$notice=$_SESSION['PORTAL_ACCOUNT_NOTICE']??'';unset($_SESSION['PORTAL_ACCOUNT_NOTICE']);
try {
 if($_SERVER['REQUEST_METHOD']==='POST') {
    if(!adminRateAttempt('own-password:'.$name))throw new InvalidArgumentException('Te veel pogingen. Probeer later opnieuw.');
    $current=$_POST['current_password']??null;$password=$_POST['password']??null;$confirm=$_POST['password_confirm']??null;
    if(!is_string($current)||!is_string($password)||!is_string($confirm)||strlen($current)>72||strlen($password)<12||strlen($password)>72||strpos($password,"\0")!==false)throw new InvalidArgumentException('Gebruik een nieuw wachtwoord van 12 tot 72 bytes.');
    if(!hash_equals($password,$confirm))throw new InvalidArgumentException('De nieuwe wachtwoorden komen niet overeen.');
    $db->begin_transaction();$st=$db->prepare('SELECT pass_hash,pass,auth_version FROM verhuur_administrators WHERE naam=? FOR UPDATE');$st->bind_param('s',$name);$st->execute();$account=$st->get_result()->fetch_assoc();
    if(!$account||(int)$account['auth_version']!==(int)$_SESSION['ADMIN_AUTH_VERSION'])throw new InvalidArgumentException('Je sessie is gewijzigd. Log opnieuw in.');
    $valid=$account['pass_hash']?password_verify($current,$account['pass_hash']):($account['pass']!==''&&hash_equals($account['pass'],$current));
    if(!$valid)throw new InvalidArgumentException('Je huidige wachtwoord is onjuist.');
    if($current===$password)throw new InvalidArgumentException('Kies een ander wachtwoord.');
    $hash=password_hash($password,PASSWORD_DEFAULT);$st=$db->prepare("UPDATE verhuur_administrators SET pass='',pass_hash=?,auth_version=auth_version+1 WHERE naam=?");$st->bind_param('ss',$hash,$name);$st->execute();$db->commit();
    $_SESSION['ADMIN_AUTH_VERSION']=(int)$account['auth_version']+1;session_regenerate_id(true);$_SESSION['ADMIN_CSRF']=bin2hex(random_bytes(32));
    $_SESSION['PORTAL_ACCOUNT_NOTICE']='Je wachtwoord is gewijzigd voor alle portalen. Andere sessies zijn afgesloten.';
    header('Location: /admin/account.php?portal='.$portal,true,303);exit;
 }
}catch(InvalidArgumentException $e){$db->rollback();$error=$e->getMessage();}catch(Throwable $e){$db->rollback();http_response_code(503);$error='Opslaan is tijdelijk niet beschikbaar.';}
$st=$db->prepare('SELECT g.naam,a.mfa_secret IS NOT NULL AS mfa FROM verhuur_administrators a JOIN beheer_access_groups g ON g.code=a.rol WHERE a.naam=?');$st->bind_param('s',$name);$st->execute();$account=$st->get_result()->fetch_assoc();
echo portalDocument('Mijn account');portalHeader($portal,'account');
?><main class="portal-account"><h1>Mijn account</h1><?php if($error): ?><p class="portal-error" role="alert"><?= portalEscape($error) ?></p><?php endif ?><?php if($notice): ?><p class="portal-success" role="status"><?= portalEscape($notice) ?></p><?php endif ?><section class="maintenance-step"><h2>Accountgegevens</h2><dl class="portal-facts"><dt>Gebruikersnaam</dt><dd><?= portalEscape($name) ?></dd><dt>Rechtengroep</dt><dd><?= portalEscape($account['naam']) ?></dd><dt>Tweestapsverificatie</dt><dd><?= $account['mfa']?'Aan':'Uit' ?></dd></dl><p><a href="/admin/?module=mfa">Tweestapsverificatie instellen</a></p></section><section class="maintenance-step" id="wachtwoord"><h2>Wachtwoord wijzigen</h2><form method="post" action="/admin/account.php?portal=<?= $portal ?>"><label>Huidig wachtwoord <input type="password" name="current_password" maxlength="72" autocomplete="current-password" required></label><label>Nieuw wachtwoord <input type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" required></label><label>Herhaal nieuw wachtwoord <input type="password" name="password_confirm" minlength="12" maxlength="72" autocomplete="new-password" required></label><button>Wachtwoord wijzigen</button></form></section></main></body></html>
