<?php
require_once __DIR__.'/beheer-security.php';ledenRequireLogin();
require_once __DIR__.'/form-document.php';require_once __DIR__.'/beheer-document.php';require_once __DIR__.'/functions.php';
$db=siteDatabaseConnection();$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);
if(!$id) { http_response_code(404);exit('Inschrijving niet gevonden.'); }
$person=lq("SELECT p.*,g.name AS group_name,r.details FROM ledenadmin_people p JOIN ledenadmin_groups g ON g.id=p.group_id LEFT JOIN ledenadmin_registrations r ON r.person_id=p.id WHERE p.id=? AND p.created_by='Openbaar ledenformulier' AND (?=1 OR EXISTS(SELECT 1 FROM ledenadmin_access a WHERE a.account=? AND a.group_id=p.group_id))",'iis',[$id,(int)($_SESSION['LEDEN_ROLE']==='central'),$_SESSION['LEDEN_NAME']])->get_result()->fetch_assoc();
if(!$person) { http_response_code(404);exit('Inschrijving niet gevonden.'); }
$values=json_decode($person['details']??'',true);if(!is_array($values))$values=[];
foreach(['iban','payer_name','payer_email'] as $key)$values[$key]=$person[$key];$values['date']=$person['joined'];
$sections=require __DIR__.'/registration-fields.php';
echo publicFormDocument('Inschrijfgegevens');publicBeheerHeader('inschrijven');
?><main class="leden registration-page"><a class="back" href="/leden/beheer.php?onderdeel=inschrijven">← Leden inschrijven</a><h1><?= le($person['name']) ?></h1><p>Groep: <?= le($person['group_name']) ?></p><?php foreach($sections as $section): ?><section><h2><?= le($section['title']) ?></h2><dl class="form-details"><?php foreach($section['fields'] as $field): $value=$values[$field['key']]??'';if(!is_scalar($value))$value='';if(isset($field['options'][$value]))$value=$field['options'][$value];if($field['type']==='date' && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D',(string)$value))$value=implode('-',array_reverse(explode('-',$value))); ?><dt><?= le($field['label']) ?></dt><dd><?= $value!==''?le($value):'—' ?></dd><?php endforeach ?></dl></section><?php endforeach ?><?php if($person['processing_status']==='nieuw' && !$person['left_on']): ?><section><p>Heb je de inschrijving in SOL afgerond?</p><form method="post" action="/leden/beheer.php?onderdeel=inschrijven"><input type="hidden" name="action" value="processed"><input type="hidden" name="id" value="<?= (int)$id ?>"><button>Verwerkt</button></form></section><?php endif ?></main></body></html>
