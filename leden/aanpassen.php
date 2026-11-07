<?php
require_once __DIR__.'/form-security.php';
require_once __DIR__.'/form-document.php';
require_once __DIR__.'/functions.php';
require_once __DIR__.'/notifications.php';
require_once __DIR__.'/groups.php';
$db=siteDatabaseConnection();
$groups=ledenFixedGroups($db);
$values=array_fill_keys(['name','group','address','email','phone','iban','payer_name','submitted_by','submitted_email'],'');
$error='';
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
    $changes=[];
    if($values['address']!=='') {
        if(strlen($values['address'])>500) throw new InvalidArgumentException('Het adres mag maximaal 500 tekens bevatten.');
        $changes['address']=$values['address'];
    }
    if($values['email']!=='') {
        if(strlen($values['email'])>254 || !filter_var($values['email'],FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Vul een geldig nieuw e-mailadres in.');
        $changes['email']=$values['email'];
    }
    if($values['phone']!=='') {
        if(strlen($values['phone'])>50 || !preg_match('/^[+0-9() .\/-]+$/D',$values['phone']) || strlen(preg_replace('/\D/','',$values['phone']))<6) throw new InvalidArgumentException('Vul een geldig nieuw telefoonnummer in.');
        $changes['phone']=$values['phone'];
    }
    if($values['iban']!=='') {
        $changes['iban']=liban($values['iban']);
        if($values['payer_name']==='' || strlen($values['payer_name'])>200) throw new InvalidArgumentException('Vul bij een nieuw IBAN ook de naam van de rekeninghouder in.');
        $changes['payer_name']=$values['payer_name'];
    } elseif($values['payer_name']!=='') throw new InvalidArgumentException('Vul bij de rekeninghouder ook het nieuwe IBAN in.');
    if(!$changes) throw new InvalidArgumentException('Vul minimaal één gegeven in dat je wilt aanpassen.');
    if($values['submitted_by']==='' || strlen($values['submitted_by'])>200) throw new InvalidArgumentException('Vul in door wie het formulier is ingevuld.');
    if(strlen($values['submitted_email'])>254 || !filter_var($values['submitted_email'],FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Vul een geldig e-mailadres in van de invuller.');
    if(!publicFormRateAttempt(session_id())) { http_response_code(429);throw new InvalidArgumentException('Er zijn te veel formulieren verstuurd. Probeer het over 15 minuten opnieuw.'); }
    $db->begin_transaction();
    lq("INSERT INTO leden_public_requests(request_type,person_name,group_id,effective_date,changes,submitted_by,submitted_email) VALUES ('change',?,?,CURDATE(),?,?,?)",'sisss',[$values['name'],$group,json_encode($changes,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),$values['submitted_by'],$values['submitted_email']]);
    $id=$db->insert_id;
    ledenNotificationQueue('change',$id,'received');
    $db->commit();
    ledenNotificationDeliver('change',$id);
    $_SESSION['CHANGE_FORM_SENT']=true;$_SESSION['FORM_CSRF']=bin2hex(random_bytes(32));
    header('Location: /leden/aanpassen.php?ontvangen=1',true,303);exit;
 } catch(InvalidArgumentException $e) { $db->rollback();$error=$e->getMessage(); }
 catch(Throwable $e) { $db->rollback();http_response_code(503);$error='De aanpassing kon niet worden verstuurd. Probeer het opnieuw.'; }
}
echo publicFormDocument('Gegevens aanpassen');publicFormHeader('aanpassen');
?>
<main class="leden registration-page"><h1>Gegevens aanpassen</h1>
<?php if(isset($_GET['ontvangen']) && !empty($_SESSION['CHANGE_FORM_SENT'])): unset($_SESSION['CHANGE_FORM_SENT']); ?><section><h2>Aanvraag ontvangen</h2><p>De gewijzigde gegevens zijn opgeslagen voor verwerking door de ledenadministratie.</p><a href="/leden/">Terug naar het overzicht</a></section>
<?php else: ?><p class="intro">Geef een nieuw adres, e-mailadres, telefoonnummer of IBAN door. Vul alleen in wat moet veranderen; lege velden blijven ongewijzigd. Je kunt meerdere wijzigingen tegelijk doorgeven.</p>
<?php if($error): ?><p class="message" role="alert"><?= le($error) ?></p><?php endif ?>
<form method="post" action="/leden/aanpassen.php"><input type="hidden" name="_form_csrf" value="<?= le($_SESSION['FORM_CSRF']) ?>">
<section><h2>Om welk lid gaat het?</h2><div class="registration-fields">
<label>Naam lid * <input name="name" value="<?= le($values['name']) ?>" maxlength="200" required></label>
<label>Groep * <select name="group" required><option value="">Kies een groep</option><?php foreach($groups as $g): ?><option value="<?= (int)$g['id'] ?>" <?= (string)$g['id']===$values['group']?'selected':'' ?>><?= le($g['name']) ?></option><?php endforeach ?></select></label>
</div></section><section><h2>Wat wil je aanpassen?</h2><div class="registration-fields">
<label class="field-wide">Nieuw adres (straat, huisnummer, postcode en woonplaats) <textarea name="address" maxlength="500" rows="3" autocomplete="street-address"><?= le($values['address']) ?></textarea></label>
<label>Nieuw e-mailadres <input type="email" name="email" value="<?= le($values['email']) ?>" maxlength="254" autocomplete="email"></label>
<label>Nieuw telefoonnummer <input type="tel" name="phone" value="<?= le($values['phone']) ?>" maxlength="50" autocomplete="tel"></label>
<label>Nieuw IBAN <input name="iban" value="<?= le($values['iban']) ?>" maxlength="50" autocomplete="off"></label>
<label>Naam rekeninghouder (verplicht bij nieuw IBAN) <input name="payer_name" value="<?= le($values['payer_name']) ?>" maxlength="200"></label>
</div></section><section><h2>Ingevuld door</h2><div class="registration-fields">
<label>Naam * <input name="submitted_by" value="<?= le($values['submitted_by']) ?>" maxlength="200" required></label>
<label>E-mailadres * <input type="email" name="submitted_email" value="<?= le($values['submitted_email']) ?>" maxlength="254" required></label>
</div></section><div class="registration-actions"><button>Aanpassing versturen</button><a href="/leden/">Annuleren</a></div></form><?php endif ?></main></body></html>
