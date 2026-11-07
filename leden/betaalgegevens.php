<?php
require_once __DIR__.'/form-security.php';
require_once __DIR__.'/form-document.php';
require_once __DIR__.'/functions.php';
$db=siteDatabaseConnection();$rows=array_fill(0,25,['name'=>'','iban'=>'','payer_name'=>'']);$error='';
try {
 if($_SERVER['REQUEST_METHOD']==='POST') {
    $input=$_POST['rows']??null;
    if(!is_array($input) || count($input)<1 || count($input)>100) throw new InvalidArgumentException('Vul een lijst van maximaal 100 leden in.');
    $rows=[];
    foreach($input as $row) {
        if(!is_array($row)) throw new InvalidArgumentException('Controleer de ingevulde lijst.');
        $clean=[];
        foreach(['name','iban','payer_name'] as $key) {
            $value=$row[$key]??'';
            if(!is_string($value)) throw new InvalidArgumentException('Controleer de ingevulde gegevens.');
            $clean[$key]=trim($value);
        }
        $rows[]=$clean;
    }
    if(($_POST['action']??'save')==='add') {
        if(count($rows)>=100) throw new InvalidArgumentException('Je kunt maximaal 100 leden per lijst invullen.');
        $rows[]=['name'=>'','iban'=>'','payer_name'=>''];
    } else {
        $validated=[];
        foreach($rows as $index=>$row) {
            if($row['name']==='' && $row['iban']==='' && $row['payer_name']==='') continue;
            if($row['name']==='' || strlen($row['name'])>200 || $row['payer_name']==='' || strlen($row['payer_name'])>200) throw new InvalidArgumentException('Rij '.($index+1).': vul naam lid en naam rekeninghouder in (maximaal 200 tekens).');
            if(strlen($row['iban'])>50) throw new InvalidArgumentException('Rij '.($index+1).': het IBAN-nummer is te lang.');
            try { $row['iban']=liban($row['iban']); } catch(InvalidArgumentException $e) { throw new InvalidArgumentException('Rij '.($index+1).': vul een geldig IBAN-nummer in.'); }
            $validated[]=$row;
        }
        if(!$validated) throw new InvalidArgumentException('Vul de betaalgegevens van minimaal één lid in.');
        if(!publicFormRateAttempt(session_id())) { http_response_code(429);throw new InvalidArgumentException('Er zijn te veel formulieren verstuurd. Probeer het over 15 minuten opnieuw.'); }
        $batch=bin2hex(random_bytes(16));$db->begin_transaction();
        foreach($validated as $row) lq('INSERT INTO leden_public_payments(batch_id,person_name,iban,payer_name) VALUES (?,?,?,?)','ssss',[$batch,$row['name'],$row['iban'],$row['payer_name']]);
        $db->commit();$_SESSION['PAYMENT_FORM_SENT']=count($validated);$_SESSION['FORM_CSRF']=bin2hex(random_bytes(32));
        header('Location: /leden/betaalgegevens.php?ontvangen=1',true,303);exit;
    }
 }
} catch(InvalidArgumentException $e) { $db->rollback();$error=$e->getMessage(); }
catch(Throwable $e) { $db->rollback();http_response_code(503);$error='De lijst kon niet worden verstuurd. Probeer het opnieuw.'; }
echo publicFormDocument('Betaalgegevenslijst vullen');publicFormHeader('betaalgegevens');
?>
<main class="leden registration-page"><h1>Betaalgegevenslijst vullen</h1>
<?php if(isset($_GET['ontvangen']) && !empty($_SESSION['PAYMENT_FORM_SENT'])): $count=$_SESSION['PAYMENT_FORM_SENT'];unset($_SESSION['PAYMENT_FORM_SENT']); ?><section><h2>Lijst ontvangen</h2><p>De betaalgegevens van <?= (int)$count ?> <?= $count===1?'lid zijn':'leden zijn' ?> opgeslagen voor verwerking.</p><a href="/leden/">Terug naar het overzicht</a></section>
<?php else: ?><p class="intro">Vul per lid de naam, het IBAN-nummer en de naam van de rekeninghouder in.</p>
<?php if($error): ?><p class="message" role="alert"><?= le($error) ?></p><?php endif ?>
<form method="post" action="/leden/betaalgegevens.php" id="payment-list"><input type="hidden" name="_form_csrf" value="<?= le($_SESSION['FORM_CSRF']) ?>"><section><div class="payment-table-wrap"><table class="payment-table"><thead><tr><th>Naam lid</th><th>IBAN-nummer</th><th>Naam rekeninghouder</th><th></th></tr></thead><tbody id="payment-rows">
<?php foreach($rows as $index=>$row): ?><tr><td><input name="rows[<?= $index ?>][name]" data-field="name" value="<?= le($row['name']) ?>" maxlength="200" aria-label="Naam lid rij <?= $index+1 ?>"></td><td><input name="rows[<?= $index ?>][iban]" data-field="iban" value="<?= le($row['iban']) ?>" maxlength="50" autocomplete="off" aria-label="IBAN-nummer rij <?= $index+1 ?>"></td><td><input name="rows[<?= $index ?>][payer_name]" data-field="payer_name" value="<?= le($row['payer_name']) ?>" maxlength="200" aria-label="Naam rekeninghouder rij <?= $index+1 ?>"></td><td><button type="button" class="remove-payment" aria-label="Rij <?= $index+1 ?> verwijderen">Verwijderen</button></td></tr><?php endforeach ?>
</tbody></table></div><button type="submit" name="action" value="add" formnovalidate id="add-payment">Lid toevoegen</button><p id="payment-row-status" role="status"></p></section><div class="registration-actions"><button type="submit" name="action" value="save">Betaalgegevens versturen</button><a href="/leden/">Annuleren</a></div></form><script src="/leden/betaalgegevens.js" defer></script><?php endif ?></main></body></html>
