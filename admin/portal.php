<?php
function portalEscape($value) { return htmlspecialchars((string)$value,ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8'); }
function portalUserMenu($portal='admin') {
 $portal=in_array($portal,['admin','beheer','leden'],true)?$portal:'admin';
?>
<details class="portal-user-menu"><summary><svg class="portal-user-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 22v-3a8 8 0 0 1 16 0v3"/></svg><span><?= portalEscape($_SESSION['ADMIN_NAME']??'') ?></span></summary><div class="portal-user-dropdown"><p><?= portalEscape($_SESSION['ADMIN_NAME']??'') ?></p><a href="/admin/account.php?portal=<?= $portal ?>" target="_top">Accountgegevens</a><a href="/admin/account.php?portal=<?= $portal ?>#wachtwoord" target="_top">Wachtwoord wijzigen</a><a href="/admin/?module=mfa" target="_top">Tweestapsverificatie</a><a href="/admin/logout.php" target="_top">Uitloggen</a></div></details>
<?php
}
function portalHeader($portal='admin',$active='') {
 $labels=['admin'=>'Admin','beheer'=>'Verhuur','leden'=>'Beheer leden'];
 $links=$portal==='admin'?['onderhoud'=>['Onderhoud','/admin/onderhoud.php'],'mailsjablonen'=>['Mailsjablonen','/admin/mailsjablonen.php'],'accounts'=>['Groepen en beheerders','/admin/accounts.php'],'loginlog'=>['Inlogpogingen','/admin/loginlog.php'],'maillog'=>['Maillog','/admin/maillog.php']]:[];
?>
<header class="admin-header portal-header"><a class="admin-brand" href="/<?= $portal==='beheer'?'verhuur':$portal ?>/<?= $portal==='leden'?'beheer.php':'' ?>"><img src="/admin/assets/logo.svg" alt="Scouting Veghel"><span><?= portalEscape($labels[$portal]??'Admin') ?></span></a><nav class="portal-module-nav" aria-label="Portaalnavigatie"><?php foreach($links as $module=>$link): if(!portalCanModule($module))continue; ?><a href="<?= portalEscape($link[1]) ?>" <?= $active===$module?'aria-current="page"':'' ?>><?= portalEscape($link[0]) ?></a><?php endforeach ?></nav><?php portalRight($portal); ?></header>
<?php
}
function portalRight($portal) {
 $portals=['admin'=>['Admin','/admin/'],'beheer'=>['Verhuur','/verhuur/'],'leden'=>['Leden','/leden/beheer.php']];
?><div class="portal-right"><nav class="portal-switcher" aria-label="Portalen"><?php foreach($portals as $key=>$link): if(!adminCanPortal($key))continue; ?><a href="<?= $link[1] ?>" target="_top" <?= $key===$portal?'aria-current="true"':'' ?>><?= $link[0] ?></a><?php endforeach ?></nav><?php portalUserMenu($portal); ?></div><?php
}
function portalCanModule($module) {
 if($module==='mfa')return true;
 if($module==='mailsjablonen')return adminCan('mailsjablonen')||adminCan('contact-sjablonen')||adminCan('leden-sjablonen');
 return adminCan($module);
}
function portalDocument($title) { return '<!doctype html><html lang="nl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.portalEscape($title).' | Scouting Veghel</title><link rel="stylesheet" href="/admin/assets/portal-base.css?v=20261029-autoheight"><link rel="stylesheet" href="/admin/portal.css?v=20261101-layoutlog"></head><body class="portal-page">'; }
