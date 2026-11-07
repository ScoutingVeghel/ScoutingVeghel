<?php
require_once dirname(__DIR__).'/php/private-config.php';
require_once dirname(__DIR__).'/php/portal-mail-template.php';
function ledenNotificationQueue($type,$id,$phase) {
    if(!in_array($type,['register','leave','transfer','change'],true) || !in_array($phase,['received','processed'],true)) throw new InvalidArgumentException('Ongeldige melding.');
    if($type==='register') {
        $row=lq('SELECT p.name,g.name AS group_name,p.joined AS effective_date,r.details FROM ledenadmin_people p JOIN ledenadmin_groups g ON g.id=p.group_id LEFT JOIN ledenadmin_registrations r ON r.person_id=p.id WHERE p.id=?','i',[$id])->get_result()->fetch_assoc();
        $details=json_decode($row['details']??'',true);$email=$details['leader_email']??'';$sender=$details['leader_name']??'';
    } else {
        $row=lq('SELECT q.person_name AS name,q.submitted_email,q.submitted_by,q.effective_date,g.name AS group_name,d.name AS target_group FROM leden_public_requests q JOIN ledenadmin_groups g ON g.id=q.group_id LEFT JOIN ledenadmin_groups d ON d.id=q.target_group_id WHERE q.id=? AND q.request_type=?','is',[$id,$type])->get_result()->fetch_assoc();
        $email=$row['submitted_email']??'';$sender=$row['submitted_by']??'';
    }
    if(!$row) throw new RuntimeException('Aanvraag niet gevonden voor mail.');
    $valid=filter_var($email,FILTER_VALIDATE_EMAIL)!==false;
    $labels=['register'=>'inschrijving','leave'=>'uitschrijving','transfer'=>'aanvraag voor overvliegen','change'=>'gegevenswijziging'];
    $actions=['register'=>'Het lid is ingeschreven in SOL.','leave'=>'De uitschrijving is verwerkt in SOL per de opgegeven datum.','transfer'=>'Het lid is in SOL overgezet naar de nieuwe groep.','change'=>'De gewijzigde gegevens zijn verwerkt in SOL.'];
    $targets=$phase==='received'?['submitter'=>$email,'administration'=>siteMailRecipient('ledenadministratie')]:['submitter'=>$email];
    foreach($targets as $audience=>$recipient) {
        if($audience==='submitter' && !$valid) continue;
        $title=$phase==='received'?($audience==='submitter'?'Aanvraag ontvangen':'Nieuwe '.$labels[$type].' om te verwerken'):'Verwerkt in SOL';
        $intro=$phase==='processed'?$actions[$type]:($audience==='submitter'?'We hebben je '.$labels[$type].' ontvangen. De ledenadministratie gaat hiermee aan de slag.':'Er staat een nieuwe '.$labels[$type].' klaar om in SOL te verwerken.');
        require_once dirname(__DIR__).'/php/portal-mail-html.php';
        $base = getenv('SV_ENV') === 'local' ? ('http://'.($_SERVER['HTTP_HOST'] ?? 'localhost:8000')) : 'https://scoutingveghel.nl';
        if (getenv('SV_ENV') !== 'local' && ($_SERVER['HTTP_HOST'] ?? '') === 'test.scoutingveghel.nl') $base = 'https://test.scoutingveghel.nl';
        $part = ['register'=>'inschrijven','leave'=>'uitschrijven','transfer'=>'overvliegen','change'=>'aanpassen'][$type];
        $templateId = 'ledenadministratie.'.$type.'.'.$phase.'.'.$audience;
        $html = mailHtmlRender($templateId, [
            'naam_lid'=>$row['name'], 'leider'=>$sender ?: 'leider', 'groep'=>$row['group_name'],
            'nieuwe_groep'=>$row['target_group'] ?? '', 'email'=>$email,
            'datum'=>!empty($row['effective_date']) ? date('d-m-Y',strtotime($row['effective_date'])) : '',
            'beheer_url'=>$base.'/leden/beheer.php?onderdeel='.$part
        ]);
        $subject = mailHtmlSubject($html);
        if(strlen($subject)>255)$subject='Scouting Veghel | '.$title;
        lq('INSERT IGNORE INTO leden_mail_outbox(entity_type,entity_id,phase,audience,recipient,subject,body) VALUES (?,?,?,?,?,?,?)','sisssss',[$type,$id,$phase,$audience,$recipient,$subject,$html]);
    }
    return $valid;
}
function ledenNotificationDeliver($type,$id) {
    // Submission and approval are already committed; mail failures must never lose a request.
    global $db;
    $success=true;
    try {
        $rows=lq("SELECT id FROM leden_mail_outbox WHERE entity_type=? AND entity_id=? AND status<>'sent' ORDER BY id",'si',[$type,$id])->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach($rows as $row) {
            $db->begin_transaction();
            $mail=lq("SELECT * FROM leden_mail_outbox WHERE id=? AND status<>'sent' FOR UPDATE",'i',[$row['id']])->get_result()->fetch_assoc();
            if(!$mail) { $db->commit();continue; }
            $delivered=false;
            try { require_once dirname(__DIR__).'/php/portal-mailer.php'; $result=sendMail($mail['recipient'],$mail['subject'],$mail['body'],'',siteMailInlineImages(),'noreply | Scouting Veghel');$delivered=!empty($result[0]); }
            catch(Throwable $e) { $delivered=false; }
            lq("UPDATE leden_mail_outbox SET status=?,attempts=attempts+1,sent_at=? WHERE id=?",'ssi',[$delivered?'sent':'failed',$delivered?date('Y-m-d H:i:s'):null,$mail['id']]);
            $db->commit();if(!$delivered)$success=false;
        }
    } catch(Throwable $e) { $db->rollback();$success=false;error_log('Leden: mailaflevering niet afgerond; melding blijft bewaard.'); }
    return $success;
}
