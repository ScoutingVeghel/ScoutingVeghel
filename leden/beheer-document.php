<?php
require_once dirname(__DIR__).'/admin/portal.php';
function publicBeheerHeader($active) {
 $tabs=['inschrijven'=>'Leden inschrijven','uitschrijven'=>'Leden uitschrijven','overvliegen'=>'Leden overvliegen','aanpassen'=>'Gegevens aanpassen','bank'=>'Bankgegevens in SOL zetten'];
?>
<header class="admin-header beheer-header portal-header"><a class="admin-brand" href="/leden/beheer.php"><img src="/leden/logo.svg" alt="Scouting Veghel"><span>Beheer leden</span></a><nav class="portal-module-nav" aria-label="Beheernavigatie"><?php foreach($tabs as $key=>$label): ?><a href="/leden/beheer.php?onderdeel=<?= htmlspecialchars($key,ENT_QUOTES,'UTF-8') ?>" <?= $active===$key?'aria-current="page"':'' ?>><?= htmlspecialchars($label,ENT_QUOTES,'UTF-8') ?></a><?php endforeach ?><a href="/leden/">Naar formulieren</a><?php if(adminCan('maillog')): ?><a href="/leden/maillog.php" <?= $active==='maillog'?'aria-current="page"':'' ?>>Maillog</a><?php endif ?></nav><?php portalRight('leden'); ?></header>
<?php
}
