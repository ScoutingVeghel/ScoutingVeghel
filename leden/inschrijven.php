<?php
require_once __DIR__.'/form-security.php';
require_once __DIR__.'/form-document.php';
require_once __DIR__.'/functions.php';require_once __DIR__.'/notifications.php';
$registrationRole='lid';$isLeader=false;$formTitle='Inschrijven lid';$formPage='inschrijven.php';
$db=siteDatabaseConnection();$actor='Openbaar ledenformulier';
require_once __DIR__.'/groups.php';
$groups=ledenFixedGroups($db);
$group=filter_var($_POST['group']??($_GET['group']??0),FILTER_VALIDATE_INT);
$group=in_array($group,array_map('intval',array_column($groups,'id')),true)?$group:0;
$sections=require __DIR__.'/registration-fields.php';
if ($isLeader) $sections=[$sections[0]];
$values=[]; $error='';
foreach($sections as $section) foreach($section['fields'] as $field) $values[$field['key']]='';
$values['registration_group']=(string)$group;
$values['date']=date('Y-m-d');
function lrvalue($key) {
    $value=$_POST[$key]??'';
    if (!is_string($value)) throw new InvalidArgumentException('Ongeldige invoer. Controleer de velden.');
    return trim($value);
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
 try {
    if (!$group) throw new InvalidArgumentException('Kies de groep waarvoor je het lid inschrijft.');
    if (!publicFormRateAttempt(session_id())) { http_response_code(429);throw new InvalidArgumentException('Er zijn te veel formulieren verstuurd. Probeer het over 15 minuten opnieuw.'); }
    // Collect first, so all entered values remain visible when validation fails.
    foreach($sections as $section) foreach($section['fields'] as $f) if ($f['type']!=='readonly') $values[$f['key']]=lrvalue($f['key']);
    foreach($sections as $section) foreach($section['fields'] as $f) {
        $v=$values[$f['key']];
        if ($f['type']==='readonly') continue;
        if ($f['type']==='number' && $v!=='' && (!preg_match('/^[0-9]+$/D',$v) || !preg_match('/[1-9]/',$v))) throw new InvalidArgumentException('Vul alleen cijfers in bij: '.$f['label']);
        if ($f['required'] && $v==='') throw new InvalidArgumentException('Vul dit veld in: '.$f['label']);
        if (strlen($v)>($f['type']==='textarea'?4000:254)) throw new InvalidArgumentException('De invoer is te lang bij: '.$f['label']);
        if ($v!=='' && $f['type']==='email' && !filter_var($v,FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Vul een geldig e-mailadres in bij: '.$f['label']);
        if ($v!=='' && isset($f['options']) && !array_key_exists($v,$f['options'])) throw new InvalidArgumentException('Kies een geldige optie bij: '.$f['label']);
        if ($f['type']==='checkbox' && !in_array($v,['','1'],true)) throw new InvalidArgumentException('Ongeldige toestemming.');
        if ($v!=='' && $f['type']==='date') {
            $format=$f['key']==='birth_date'?'d-m-Y':'Y-m-d';
            $date=DateTimeImmutable::createFromFormat('!'.$format,$v);
            if (!$date || $date->format($format)!==$v || $date->format('Y-m-d')>date('Y-m-d')) throw new InvalidArgumentException('Kies een geldige datum, uiterlijk vandaag, bij: '.$f['label']);
        }
    }
    if (!$isLeader && strlen($values['payer_name'])>200) throw new InvalidArgumentException('De naam van de rekeninghouder is te lang.');
    $name=implode(' ',array_filter([$values['first_names'],$values['middle_name'],$values['last_name']],static function($v){return $v!=='';}));
    if (strlen($name)>200) throw new InvalidArgumentException('De volledige naam is te lang.');
    $joined=ldate();
    $birthDate=DateTimeImmutable::createFromFormat('!d-m-Y',$values['birth_date'])->format('Y-m-d');
    if ($joined<$birthDate) throw new InvalidArgumentException('De startdatum mag niet voor de geboortedatum liggen.');
    if (!$isLeader) {
    $values['guardian1_name']=implode(' ',array_filter([$values['guardian1_first_names'],$values['guardian1_middle_name'],$values['guardian1_last_name']],static function($v){return $v!=='';}));
    foreach(['guardian2'] as $prefix) {
        $entered=$values[$prefix.'_name']!=='' || $values[$prefix.'_email']!=='' || $values[$prefix.'_phone']!=='';
        if ($entered && ($values[$prefix.'_name']==='' || $values[$prefix.'_relation']==='' || $values[$prefix.'_phone']==='')) throw new InvalidArgumentException('Vul bij een ouder / verzorger ook naam, relatie en telefoonnummer in.');
    }
    $iban=liban($values['iban']); $values['iban']=$iban;
    } else { $iban=''; }
    $values['birth_date']=$birthDate;
    $db->begin_transaction();
    lq('INSERT INTO ledenadmin_people(group_id,name,role,iban,payer_name,payer_email,joined,created_by) VALUES (?,?,?,?,?,?,?,?)','isssssss',[$group,$name,$registrationRole,$iban,$values['payer_name']??'',$values['payer_email']??'',$joined,$actor]);
    $id=$db->insert_id;
    $values['registration_id']=$id; $values['processing']='nieuw';
    $values['registration_group']=$group;
    lq('INSERT INTO ledenadmin_registrations(person_id,form_version,details) VALUES (?,?,?)','iss',[$id,$isLeader?'2026-11-01-leiding':'2026-11-01-publiek-leider',json_encode($values,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
    lq('INSERT INTO ledenadmin_events(person_id,action,effective_date,to_group,actor) VALUES (?,\'register\',?,?,?)','isis',[$id,$joined,$group,$actor]);
    ledenNotificationQueue('register',$id,'received');
    $db->commit();ledenNotificationDeliver('register',$id); $_SESSION['FORM_SENT']=true;$_SESSION['FORM_CSRF']=bin2hex(random_bytes(32));
    header('Location: /leden/inschrijven.php?ontvangen=1',true,303); exit;
 } catch(InvalidArgumentException $e) { $db->rollback(); $error=$e->getMessage(); }
 catch(Throwable $e) { $db->rollback(); http_response_code(503); $error='De inschrijving kon niet worden opgeslagen. Probeer het opnieuw.'; error_log('Ledeninschrijving opslaan mislukt.'); }
}
echo publicFormDocument();publicFormHeader();
?>
<main class="leden registration-page">
<?php if(isset($_GET['ontvangen']) && !empty($_SESSION['FORM_SENT'])): ?>
<section class="signup-celebration" aria-labelledby="celebration-title"><div class="confetti" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div><div class="celebration-badge" aria-hidden="true">✓</div><p class="celebration-eyebrow">Inschrijving ontvangen</p><h1 id="celebration-title">Hoera, een nieuw<br>scoutingavontuur!</h1><p class="celebration-lead">De inschrijving is verstuurd. Tijd voor nieuwe vrienden, buiten zijn en mooie avonturen bij Scouting Veghel!</p><div class="celebration-next"><strong>Alles is goed aangekomen.</strong><p>De gegevens zijn opgeslagen en staan klaar voor de ledenadministratie. Zij verwerken de inschrijving verder.</p></div><p class="celebration-thanks">Bedankt voor het invullen. Samen maken we scouting mogelijk!</p><div class="celebration-actions"><a class="receipt-primary" href="/leden/inschrijven.php">Nog een lid inschrijven <span aria-hidden="true">→</span></a><a class="receipt-secondary" href="/leden/">Terug naar het overzicht</a></div></section></main></body></html><?php exit; endif ?>
<h1>Inschrijven lid</h1><p class="intro">Vul de gegevens van het lid, de voogden en de betaling in.</p>
<?php if($error): ?><p class="message" role="alert"><?= le($error) ?></p><?php endif ?>
<?php if(!$groups): ?><p>Er zijn nog geen groepen ingesteld voor inschrijving.</p><?php else: ?>
<p>Velden met * zijn verplicht. Vul contactpersonen en aanvullende gegevens in voor zover van toepassing.</p>
<form method="post" action="/leden/inschrijven.php"><input type="hidden" name="_form_csrf" value="<?= le($_SESSION['FORM_CSRF']) ?>"><section><label>Groep * <select name="group" required><option value="">Kies een groep</option><?php foreach($groups as $g): ?><option value="<?= (int)$g['id'] ?>" <?= $group===(int)$g['id']?'selected':'' ?>><?= le($g['name']) ?></option><?php endforeach ?></select></label></section>
<?php foreach($sections as $section): ?><section><h2><?= le($section['title']) ?></h2>
<div class="registration-fields"><?php foreach($section['fields'] as $f): $key=$f['key']; $type=$f['type']; $required=$f['required']?'required':''; ?>
<label class="<?= in_array($type,['textarea','checkbox'],true)?'field-wide':'' ?><?= $type==='checkbox'?' consent-field':'' ?>">
<?php if($type==='checkbox'): ?><input type="checkbox" name="<?= le($key) ?>" value="1" <?= $values[$key]==='1'?'checked':'' ?> <?= $required ?>><span><?= le($f['label']) ?><?= $f['required']?' *':'' ?></span>
<?php else: ?><span><?= le($f['label']) ?><?= $f['required']?' *':'' ?></span>
<?php if($type==='readonly'): ?><input type="text" value="<?= le($values[$key]) ?>" readonly>
<?php elseif($type==='group'): ?><select name="<?= le($key) ?>" required><?php foreach($groups as $g): ?><option value="<?= (int)$g['id'] ?>" <?= (int)$values[$key]===(int)$g['id']?'selected':'' ?>><?= le($g['name']) ?></option><?php endforeach ?></select>
<?php elseif($type==='select'): ?><select name="<?= le($key) ?>" <?= $required ?>><option value="">Maak een keuze</option><?php foreach($f['options'] as $value=>$label): ?><option value="<?= le($value) ?>" <?= $values[$key]===$value?'selected':'' ?>><?= le($label) ?></option><?php endforeach ?></select>
<?php elseif($type==='textarea'): ?><textarea name="<?= le($key) ?>" rows="4" maxlength="4000"><?= le($values[$key]) ?></textarea>
<?php elseif($key==='birth_date'): ?><input type="text" name="birth_date" value="<?= le($values[$key]) ?>" placeholder="dd-mm-jjjj" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" maxlength="10" aria-label="Geboortedatum (dd-mm-jjjj)" required>
<?php else: ?><input type="<?= le($type) ?>" name="<?= le($key) ?>" value="<?= le($values[$key]) ?>" <?= $type==='date'?'max="'.date('Y-m-d').'"':'maxlength="254"' ?> <?= $type==='number'?'min="1" step="1" inputmode="numeric"':'' ?> <?= $required ?>><?php endif ?><?php endif ?></label>
<?php endforeach ?></div></section><?php endforeach ?>
<div class="registration-actions"><button>Inschrijving opslaan</button><a href="/leden/">Annuleren</a></div></form><?php endif ?>
</main></body></html>
