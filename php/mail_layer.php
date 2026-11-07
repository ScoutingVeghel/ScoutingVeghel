<?php

require_once __DIR__ . "/PhpMailerProxy.php";
require_once __DIR__ . "/mail-html.php";

$MAIL_ADDRESS_VERHUUR = 'verhuur@scoutingveghel.nl';
$MAIL_ADDRESS_WEBSITE = 'website@scoutingveghel.nl';

$VERIFY_BASE_URL = 'https://www.scoutingveghel.nl/php/verhuur-confirm.php?key=';
$HUURVOOORWAARDEN_URL = 'https://www.scoutingveghel.nl/docs/huurvoorwaarden.pdf';
$HUUROVEREENKOMST_BASE_URL = 'https://www.scoutingveghel.nl/php/huurovereenkomst.php?key=';


/** Keep the legacy public rental entry points while using the shared mail design. */
function sendConfirmEmailEllen($name, $email) {
    global $MAIL_ADDRESS_VERHUUR, $MAIL_ADDRESS_WEBSITE;
    $content = mailHtmlParagraph('Beste verhuurteam,') .
        mailHtmlParagraph('Er is een reservering voor de blokhut bevestigd.') .
        mailHtmlParagraph('<strong>Naam / groep</strong><br>' . htmlspecialchars((string) $name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
            '<br><br><strong>E-mailadres</strong><br>' . htmlspecialchars((string) $email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) .
        mailHtmlParagraph('Met vriendelijke groet,<br>Website Scouting Veghel');
    $message = siteMailTemplate('verhuur', 'Reservering bevestigd', $content);
    return sendMailWithFrom($MAIL_ADDRESS_VERHUUR, 'Reservering blokhut Scouting Veghel bevestigd.', $message,
        $MAIL_ADDRESS_WEBSITE, $MAIL_ADDRESS_WEBSITE, siteMailInlineImages());
}

function sendConfirmEmail($toMail, $naam, $hashEmail, $activity, $startSTR, $endSTR, $aantalPers) {
    global $MAIL_ADDRESS_VERHUUR, $VERIFY_BASE_URL;
    $message = mailHtmlRender('verhuur.optie', [
        'naam' => $naam, 'activiteit' => $activity,
        'periode' => $endSTR !== '' ? 'Van ' . $startSTR . ' tot ' . $endSTR : 'Op ' . $startSTR,
        'personen' => $aantalPers, 'bevestig_url' => $VERIFY_BASE_URL . rawurlencode($hashEmail)
    ]);
    return sendMailWithFrom($toMail, mailHtmlSubject($message), $message,
        $MAIL_ADDRESS_VERHUUR, $MAIL_ADDRESS_VERHUUR, siteMailInlineImages());
}

function sendDocuments($name, $toMail, $confirm_key) {
    global $MAIL_ADDRESS_VERHUUR, $HUURVOOORWAARDEN_URL, $HUUROVEREENKOMST_BASE_URL;
    $message = mailHtmlRender('verhuur.documenten', [
        'naam' => $name, 'overeenkomst_url' => $HUUROVEREENKOMST_BASE_URL . rawurlencode($confirm_key),
        'voorwaarden_url' => $HUURVOOORWAARDEN_URL
    ]);
    return sendMailWithFrom($toMail, mailHtmlSubject($message), $message,
        $MAIL_ADDRESS_VERHUUR, $MAIL_ADDRESS_VERHUUR, siteMailInlineImages());
}
