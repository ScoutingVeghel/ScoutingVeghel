<?php
require_once __DIR__.'/form-security.php';
require_once __DIR__.'/form-document.php';
require_once __DIR__.'/functions.php';require_once __DIR__.'/notifications.php';
$db=siteDatabaseConnection();
require_once __DIR__.'/groups.php';
$groups=ledenFixedGroups($db);
$values=['name'=>'','group'=>'','date'=>'','reason'=>'','submitted_by'=>'','submitted_email'=>''];$error='';
if($_SERVER['REQUEST_METHOD']==='POST') {
 try {
    foreach($values as $key=>$unused) {
        $value=$_POST[$key]??'';
        if(!is_string($value)) throw new InvalidArgumentException('Controleer de ingevulde gegevens.');
        $values[$key]=trim($value);
    }
    if($values['name']==='' || strlen($values['name'])>200) throw new InvalidArgumentException('Vul de naam van het lid in (maximaal 200 tekens).');
    $group=filter_var($values['group'],FILTER_VALIDATE_INT);
    if(!in_array($group,array_map('intval',array_column($groups,'id')),true)) throw new InvalidArgumentException('Kies een geldige groep.');
    $date=DateTimeImmutable::createFromFormat('!Y-m-d',$values['date']);
    if(!$date || $date->format('Y-m-d')!==$values['date'] || $values['date']<'1900-01-01') throw new InvalidArgumentException('Kies een geldige uitschrijfdatum.');
    if(strlen($values['reason'])>2000) throw new InvalidArgumentException('De reden mag maximaal 2000 tekens bevatten.');
    if($values['submitted_by']==='' || strlen($values['submitted_by'])>200) throw new InvalidArgumentException('Vul in door wie het formulier is ingevuld.');
    if(strlen($values['submitted_email'])>254 || !filter_var($values['submitted_email'],FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Vul een geldig e-mailadres in van de invuller.');
    if(!publicFormRateAttempt(session_id())) { http_response_code(429);throw new InvalidArgumentException('Er zijn te veel formulieren verstuurd. Probeer het over 15 minuten opnieuw.'); }
    $db->begin_transaction();
    lq("INSERT INTO leden_public_requests(request_type,person_name,group_id,effective_date,reason,submitted_by,submitted_email) VALUES ('leave',?,?,?,?,?,?)",'sissss',[$values['name'],$group,$values['date'],$values['reason']===''?null:$values['reason'],$values['submitted_by'],$values['submitted_email']]);
    $requestId=$db->insert_id;ledenNotificationQueue('leave',$requestId,'received');
    $db->commit();ledenNotificationDeliver('leave',$requestId);
    $_SESSION['LEAVE_FORM_SENT']=true;$_SESSION['FORM_CSRF']=bin2hex(random_bytes(32));
    header('Location: /leden/uitschrijven.php?ontvangen=1',true,303);exit;
 } catch(InvalidArgumentException $e) { $db->rollback();$error=$e->getMessage(); }
 catch(Throwable $e) { $db->rollback();http_response_code(503);$error='De uitschrijving kon niet worden verstuurd. Probeer het opnieuw.'; }
}
echo publicFormDocument('Uitschrijven lid');publicFormHeader('uitschrijven');
?>
<main class="leden registration-page"><h1>Uitschrijven lid</h1>
<?php if(isset($_GET['ontvangen']) && !empty($_SESSION['LEAVE_FORM_SENT'])): unset($_SESSION['LEAVE_FORM_SENT']); ?><section><h2>Aanvraag ontvangen</h2><p>De uitschrijfaanvraag is opgeslagen voor verwerking.</p><a href="/leden/">Terug naar het overzicht</a></section>
<?php else: ?><p class="intro">Vul de gegevens in om een uitschrijving door te geven.</p>
<?php if($error): ?><p class="message" role="alert"><?= le($error) ?></p><?php endif ?>
<?php if(!$groups): ?><p>Er zijn nog geen groepen ingesteld.</p><?php else: ?><form method="post" action="/leden/uitschrijven.php"><input type="hidden" name="_form_csrf" value="<?= le($_SESSION['FORM_CSRF']) ?>"><section><div class="registration-fields">
<label>Naam lid * <input name="name" value="<?= le($values['name']) ?>" maxlength="200" required></label>
<label>Groep * <select name="group" required><option value="">Kies een groep</option><?php foreach($groups as $g): ?><option value="<?= (int)$g['id'] ?>" <?= (string)$g['id']===$values['group']?'selected':'' ?>><?= le($g['name']) ?></option><?php endforeach ?></select></label>
<label>Uitschrijven per * <input type="date" name="date" value="<?= le($values['date']) ?>" min="1900-01-01" required></label>
<label class="field-wide">Reden (optioneel) <textarea name="reason" maxlength="2000" rows="4"><?= le($values['reason']) ?></textarea></label>
</div></section><section><h2>Ingevuld door</h2><div class="registration-fields"><label>Naam * <input name="submitted_by" value="<?= le($values['submitted_by']) ?>" maxlength="200" required></label><label>E-mailadres * <input type="email" name="submitted_email" value="<?= le($values['submitted_email']) ?>" maxlength="254" required></label></div></section><div class="registration-actions"><button>Uitschrijving versturen</button><a href="/leden/">Annuleren</a></div></form><?php endif ?><?php endif ?></main></body></html>
