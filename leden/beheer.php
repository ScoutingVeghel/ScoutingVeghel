<?php
require_once __DIR__.'/beheer-security.php';ledenRequireLogin();
require_once __DIR__.'/form-document.php';require_once __DIR__.'/beheer-document.php';require_once __DIR__.'/functions.php';require_once __DIR__.'/notifications.php';
$db=siteDatabaseConnection();$central=$_SESSION['LEDEN_ROLE']==='central';$actor=$_SESSION['LEDEN_NAME'];
$tabs=['inschrijven'=>'Leden inschrijven','uitschrijven'=>'Leden uitschrijven','overvliegen'=>'Leden overvliegen','aanpassen'=>'Gegevens aanpassen','bank'=>'Bankgegevens in SOL zetten'];
$tab=$_GET['onderdeel']??'inschrijven';if(!is_string($tab) || !isset($tabs[$tab])) $tab='inschrijven';
$requestType=['uitschrijven'=>'leave','overvliegen'=>'transfer','aanpassen'=>'change'][$tab]??null;
if($tab==='bank' && !$central) { http_response_code(403);exit('Alleen de centrale ledenadministratie mag de bankgegevens verwerken.'); }
$error='';$notice=$_SESSION['WORKLIST_NOTICE']??'';unset($_SESSION['WORKLIST_NOTICE']);
try {
 if($_SERVER['REQUEST_METHOD']==='POST') {
    $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);
    if(!$id || $id<1 || !in_array($_POST['action']??null,['processed','retry_mail'],true)) throw new InvalidArgumentException('Kies een geldige aanvraag.');
    $db->begin_transaction();
    if($tab==='inschrijven') {
        $request=lq("SELECT p.id,p.group_id,p.processing_status AS status FROM ledenadmin_people p WHERE p.id=? AND p.created_by='Openbaar ledenformulier' AND p.left_on IS NULL AND (?=1 OR EXISTS(SELECT 1 FROM ledenadmin_access a WHERE a.account=? AND a.group_id=p.group_id)) FOR UPDATE",'iis',[$id,(int)$central,$actor])->get_result()->fetch_assoc();
    } elseif($tab==='bank') $request=lq('SELECT id,status FROM leden_public_payments WHERE id=? FOR UPDATE','i',[$id])->get_result()->fetch_assoc();
    else $request=lq("SELECT q.id,q.status FROM leden_public_requests q WHERE q.id=? AND q.request_type=? AND (?=1 OR EXISTS(SELECT 1 FROM ledenadmin_access a WHERE a.account=? AND a.group_id=q.group_id)) FOR UPDATE",'isis',[$id,$requestType,(int)$central,$actor])->get_result()->fetch_assoc();
    if(!$request) { http_response_code(403);throw new InvalidArgumentException('Deze aanvraag is niet beschikbaar voor jouw account.'); }
    if(($_POST['action']??null)==='retry_mail') {
        $type=['inschrijven'=>'register','uitschrijven'=>'leave','overvliegen'=>'transfer','aanpassen'=>'change'][$tab]??null;
        if(!$type) throw new InvalidArgumentException('Voor deze aanvraag zijn geen mails beschikbaar.');
        $db->commit();$ok=ledenNotificationDeliver($type,$id);
        $_SESSION['WORKLIST_NOTICE']=$ok?'De openstaande mails zijn verstuurd.':'De mail kon nog niet worden verstuurd. Probeer het later opnieuw.';
        header('Location: /leden/beheer.php?onderdeel='.$tab,true,303);exit;
    }
    if($request['status']!=='nieuw') throw new InvalidArgumentException('Deze aanvraag is al verwerkt.');
    if($tab==='inschrijven') {
        lq("UPDATE ledenadmin_people SET processing_status='verwerkt' WHERE id=?",'i',[$id]);
        lq("INSERT INTO ledenadmin_events(person_id,action,effective_date,from_group,to_group,actor) VALUES (?,'sol_verwerkt',?,?,?,?)",'isiis',[$id,date('Y-m-d'),$request['group_id'],$request['group_id'],$actor]);
    } else {
        $table=$tab==='bank'?'leden_public_payments':'leden_public_requests';
        lq("UPDATE ".$table." SET status='verwerkt',processed_by=?,processed_at=NOW() WHERE id=?",'si',[$actor,$id]);
    }
    $notificationType=['inschrijven'=>'register','uitschrijven'=>'leave','overvliegen'=>'transfer','aanpassen'=>'change'][$tab]??null;
    $hasRecipient=$notificationType?ledenNotificationQueue($notificationType,$id,'processed'):true;
    $db->commit();$mailOk=$notificationType?ledenNotificationDeliver($notificationType,$id):true;
    $_SESSION['WORKLIST_NOTICE']='De aanvraag is gemarkeerd als verwerkt.'.(!$hasRecipient?' Er is geen geldig e-mailadres van de invuller; er is geen bevestiging verstuurd.':(!$mailOk?' De bevestigingsmail kon nog niet worden verstuurd en blijft bewaard.':''));
    header('Location: /leden/beheer.php?onderdeel='.$tab,true,303);exit;
 }
} catch(InvalidArgumentException $e) { $db->rollback();$error=$e->getMessage(); }
catch(Throwable $e) { $db->rollback();http_response_code(503);$error='De verwerking kon niet worden opgeslagen.'; }
$steps=[
 'inschrijven'=>['Open de inschrijfgegevens en controleer lid, voogden en betaalgegevens.','Schrijf het lid bij de juiste groep in SOL in.','Klik op Verwerkt zodra de inschrijving in SOL klaar is.'],
 'uitschrijven'=>['Controleer de naam, groep, uitschrijfdatum en eventuele reden.','Verwerk de uitschrijving in SOL per de opgegeven datum.','Klik op Verwerkt zodra de uitschrijving in SOL klaar is.'],
 'overvliegen'=>['Controleer de naam, huidige groep en nieuwe groep.','Pas de groepsindeling van het lid aan in SOL.','Klik op Verwerkt zodra de wijziging in SOL klaar is.'],
 'aanpassen'=>['Controleer het lid, de groep en de aangeleverde wijzigingen.','Pas alleen de opgegeven gegevens aan in SOL.','Klik op Verwerkt zodra alle wijzigingen in SOL staan.'],
 'bank'=>['Controleer de naam van het lid, het IBAN-nummer en de rekeninghouder.','Neem de bankgegevens over bij het juiste lid in SOL.','Klik per regel op Verwerkt zodra de bankgegevens in SOL staan.']
];
$rows=[];
if($tab==='inschrijven') $rows=lq("SELECT p.id,p.name,p.processing_status AS status,(SELECT e.actor FROM ledenadmin_events e WHERE e.person_id=p.id AND e.action='sol_verwerkt' ORDER BY e.id DESC LIMIT 1) AS processed_by,(SELECT e.created_at FROM ledenadmin_events e WHERE e.person_id=p.id AND e.action='sol_verwerkt' ORDER BY e.id DESC LIMIT 1) AS processed_at,g.name AS group_name,p.joined AS effective_date,JSON_UNQUOTE(JSON_EXTRACT(r.details,'$.leader_name')) AS submitted_by,JSON_UNQUOTE(JSON_EXTRACT(r.details,'$.leader_email')) AS submitted_email FROM ledenadmin_people p JOIN ledenadmin_groups g ON g.id=p.group_id LEFT JOIN ledenadmin_registrations r ON r.person_id=p.id WHERE p.created_by='Openbaar ledenformulier' AND (?=1 OR EXISTS(SELECT 1 FROM ledenadmin_access a WHERE a.account=? AND a.group_id=p.group_id)) ORDER BY p.id",'is',[(int)$central,$actor])->get_result()->fetch_all(MYSQLI_ASSOC);
elseif($tab==='bank') $rows=lq("SELECT id,person_name AS name,iban,payer_name,created_at,status,processed_by,processed_at FROM leden_public_payments ORDER BY status='verwerkt',id")->get_result()->fetch_all(MYSQLI_ASSOC);
else $rows=lq("SELECT q.id,q.status,q.processed_by,q.processed_at,q.person_name AS name,g.name AS group_name,d.name AS target_group,q.effective_date,q.reason,q.changes,q.submitted_by,q.submitted_email FROM leden_public_requests q JOIN ledenadmin_groups g ON g.id=q.group_id LEFT JOIN ledenadmin_groups d ON d.id=q.target_group_id WHERE q.request_type=? AND (?=1 OR EXISTS(SELECT 1 FROM ledenadmin_access a WHERE a.account=? AND a.group_id=q.group_id)) ORDER BY q.id",'sis',[$requestType,(int)$central,$actor])->get_result()->fetch_all(MYSQLI_ASSOC);
$failedMail=[];
$worklistType=['inschrijven'=>'register','uitschrijven'=>'leave','overvliegen'=>'transfer','aanpassen'=>'change'][$tab]??null;
if($worklistType) foreach(lq("SELECT entity_id,COUNT(*) AS total FROM leden_mail_outbox WHERE entity_type=? AND status<>'sent' GROUP BY entity_id",'s',[$worklistType])->get_result() as $failure) $failedMail[(int)$failure['entity_id']]=(int)$failure['total'];
echo publicFormDocument('Beheer leden');
?>
<?php publicBeheerHeader($tab); ?>
<main class="leden registration-page"><h1><?= le($tabs[$tab]) ?></h1><p class="intro">Ingelogd als <?= le($actor) ?>.</p>
<?php if($error): ?><p class="message" role="alert"><?= le($error) ?></p><?php endif ?><?php if($notice): ?><p class="success-message" role="status"><?= le($notice) ?></p><?php endif ?>
<section><h2>Wat moet er gebeuren?</h2><ol><?php foreach($steps[$tab] as $step): ?><li><?= le($step) ?></li><?php endforeach ?></ol><p>De knop Verwerkt bevestigt de handeling in SOL. Deze pagina stuurt zelf geen wijzigingen naar SOL.</p></section>
<section><h2>Aanvragen (<?= count($rows) ?>)</h2><?php if($tab==='bank'): ?><p>Neem de aangeleverde bankgegevens over in SOL.</p><?php else: ?><p>Ontvangen aanvragen voor de groepen waarvoor je toegang hebt.</p><?php endif ?>
<?php if(!$rows): ?><p>Er zijn nog geen aanvragen in dit onderdeel.</p><?php else: ?><div class="payment-table-wrap<?= $tab==='inschrijven'?' registration-requests':'' ?>"><table class="payment-table"><thead><tr><th>Naam lid</th><?php if($tab==='bank'): ?><th>IBAN-nummer</th><th>Naam rekeninghouder</th><?php else: ?><th>Groep</th><?php if($tab==='aanpassen'): ?><th>Gewijzigde gegevens</th><?php elseif($tab==='overvliegen'): ?><th>Nieuwe groep</th><?php else: ?><th><?= $tab==='uitschrijven'?'Uitschrijven per':'Startdatum' ?></th><?php endif ?><?php if($tab==='uitschrijven'): ?><th>Reden</th><?php endif ?><th>Ingevuld door</th><th>E-mailadres</th><?php endif ?><th>Status</th><th>Goedgekeurd door</th><th>Goedgekeurd op</th><th>Actie</th></tr></thead><tbody>
<?php foreach($rows as $row): ?><tr><td><?php if($tab==='inschrijven'): ?><span class="request-field-label" aria-hidden="true">Naam lid</span><?php endif ?><?= le($row['name']) ?><?php if($tab==='inschrijven'): ?><br><a href="/leden/inschrijving.php?id=<?= (int)$row['id'] ?>">Gegevens bekijken</a><?php endif ?></td><?php if($tab==='bank'): ?><td><?= le($row['iban']) ?></td><td><?= le($row['payer_name']) ?></td><?php else: ?><td><?php if($tab==='inschrijven'): ?><span class="request-field-label" aria-hidden="true">Groep</span><?php endif ?><?= le($row['group_name']) ?></td><?php if($tab==='aanpassen'): ?><td><dl class="request-changes"><?php foreach(json_decode($row['changes']??'{}',true)??[] as $field=>$value): ?><dt><?= le(['address'=>'Nieuw adres','email'=>'Nieuw e-mailadres','phone'=>'Nieuw telefoonnummer','iban'=>'Nieuw IBAN','payer_name'=>'Rekeninghouder'][$field]??$field) ?></dt><dd><?= nl2br(le($value)) ?></dd><?php endforeach ?></dl></td><?php elseif($tab==='overvliegen'): ?><td><?= le($row['target_group']) ?></td><?php else: ?><td><?php if($tab==='inschrijven'): ?><span class="request-field-label" aria-hidden="true">Startdatum</span><?php endif ?><?= le(implode('-',array_reverse(explode('-',$row['effective_date'])))) ?></td><?php endif ?><?php if($tab==='uitschrijven'): ?><td><?= le($row['reason']??'—') ?></td><?php endif ?><td><?php if($tab==='inschrijven'): ?><span class="request-field-label" aria-hidden="true">Ingevuld door</span><?php endif ?><?= le($row['submitted_by']??'—') ?></td><td><?php if($tab==='inschrijven'): ?><span class="request-field-label" aria-hidden="true">E-mailadres</span><?php endif ?><?= le($row['submitted_email']??'—') ?></td><?php endif ?><td><?php if($tab==='inschrijven'): ?><span class="request-field-label" aria-hidden="true">Status</span><?php endif ?><span class="worklist-status <?= $row['status']==='verwerkt'?'done':'pending' ?>"><?= $row['status']==='verwerkt'?'Verwerkt':'Nieuw' ?></span></td><td><?php if($tab==='inschrijven'): ?><span class="request-field-label" aria-hidden="true">Goedgekeurd door</span><?php endif ?><?= $row['status']==='verwerkt'?le($row['processed_by']??'—'):'—' ?></td><td><?php if($tab==='inschrijven'): ?><span class="request-field-label" aria-hidden="true">Goedgekeurd op</span><?php endif ?><?= $row['status']==='verwerkt' && $row['processed_at']?le(date('d-m-Y H:i',strtotime($row['processed_at']))):'—' ?></td><td><?php if($tab==='inschrijven'): ?><span class="request-field-label" aria-hidden="true">Actie</span><?php endif ?><?php if($row['status']==='nieuw'): ?><form method="post" action="/leden/beheer.php?onderdeel=<?= le($tab) ?>"><input type="hidden" name="action" value="processed"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button>Verwerkt</button></form><?php else: ?>Afgerond<?php endif ?><?php if(isset($failedMail[(int)$row['id']])): ?><p>Mail nog niet verstuurd.</p><form method="post" action="/leden/beheer.php?onderdeel=<?= le($tab) ?>"><input type="hidden" name="action" value="retry_mail"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button>Mail opnieuw proberen</button></form><?php endif ?></td></tr><?php endforeach ?></tbody></table></div><?php endif ?></section></main></body></html>
