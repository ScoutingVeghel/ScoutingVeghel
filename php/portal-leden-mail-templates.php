<?php
// Website notifications are separate from the legacy Power Automate formats.
function ledenMailCatalog() {
    $catalog = [];
    foreach (['register'=>'Inschrijven', 'leave'=>'Uitschrijven', 'transfer'=>'Overvliegen','change'=>'Gegevens aanpassen'] as $type=>$label) {
        foreach (['received.submitter'=>'Aanvraag ontvangen · invuller', 'received.administration'=>'Nieuwe aanvraag · ledenadministratie', 'processed.submitter'=>'Verwerkt in SOL · invuller'] as $variant=>$description) {
            $variables = ['naam_lid','leider','groep','email'];
            if ($type === 'transfer') $variables[] = 'nieuwe_groep'; elseif ($type !== 'change') $variables[] = 'datum';
            if ($variant === 'received.administration') $variables[] = 'beheer_url';
            $catalog['ledenadministratie.'.$type.'.'.$variant] = [
                'label'=>$label.' · '.$description,
                'description'=>$description.'. Deze mail wordt automatisch verstuurd vanuit de ledenadministratie.',
                'variables'=>$variables,
                'required'=>array_values(array_diff($variables, ['leider','email']))
            ];
        }
    }
    return $catalog;
}
function ledenMailDefault($id) {
    [, $type, $phase, $audience] = explode('.', $id);
    $labels = ['register'=>'inschrijving','leave'=>'uitschrijving','transfer'=>'aanvraag voor overvliegen','change'=>'gegevenswijziging'];
    $actions = ['register'=>'Het lid is ingeschreven in SOL.','leave'=>'De uitschrijving is verwerkt in SOL per de opgegeven datum.','transfer'=>'Het lid is in SOL overgezet naar de nieuwe groep.','change'=>'De gewijzigde gegevens zijn verwerkt in SOL.'];
    $title = $phase === 'processed' ? 'Verwerkt in SOL' : ($audience === 'submitter' ? 'Aanvraag ontvangen' : 'Nieuwe '.$labels[$type].' om te verwerken');
    $intro = $phase === 'processed' ? $actions[$type] : ($audience === 'submitter' ? 'We hebben je '.$labels[$type].' ontvangen. De ledenadministratie gaat hiermee aan de slag.' : 'Er staat een nieuwe '.$labels[$type].' klaar om in SOL te verwerken.');
    $content = mailHtmlParagraph($audience === 'submitter' ? 'Hallo {{leider}},' : 'Beste ledenadministratie,');
    $content .= mailHtmlParagraph($intro);
    $fields = ['Naam lid'=>'naam_lid', ($type === 'transfer' ? 'Huidige groep' : 'Groep')=>'groep'];
    if ($type === 'transfer') $fields['Nieuwe groep'] = 'nieuwe_groep'; elseif ($type !== 'change') $fields[$type === 'leave' ? 'Uitschrijven per' : 'Startdatum'] = 'datum';
    $content .= mailHtmlDetails($fields);
    if ($audience === 'administration') $content .= mailHtmlParagraph('Ingevuld door: {{leider}}<br>E-mailadres: {{email}}').mailHtmlButton('beheer_url','Open de aanvragen');
    $content .= mailHtmlParagraph('Met vriendelijke groet,<br>Scouting Veghel');
    return mailHtmlWithSubject(siteMailTemplate('leden', $title, $content), 'Scouting Veghel | '.$title.' | {{naam_lid}}');
}
