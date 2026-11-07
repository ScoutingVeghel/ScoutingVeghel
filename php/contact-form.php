<?php

require_once __DIR__ . "/PhpMailerProxy.php";
require_once __DIR__ . "/mail-html.php";

$MAIL_ADDRESS_VERHUUR = 'verhuur@scoutingveghel.nl';
$MAIL_ADDRESS_WEBSITE = 'website@scoutingveghel.nl';
$MAIL_ADDRESS_GEBE = 'groepsbegeleiding@scoutingveghel.nl';
$MAIL_ADDRESS_BESTUUR = 'bestuur@scoutingveghel.nl';

session_start();
date_default_timezone_set('Europe/Paris');

if (isset($_POST["name"]) && isset($_POST["mailadr"]) && isset($_POST["whoTo"]) && isset($_POST["tArea"])) {
    // Now check the captcha.
    $robot = "Fout: Google denkt dat je een robot bent.";
    if(isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
        // To see out public and private key see: https://www.google.com/recaptcha
        // Public key (front-end): 6LdOoSsUAAAAAJzkLaGujoiw3qZ3NIZ5HYhGuDjK
        // The invisible option is chosen.
        $secret = getenv('RECAPTCHA_SECRET');
        if (!is_string($secret) || $secret === '') {
            $contactConfig = require dirname(__DIR__, 2) . '/private/contact.php';
            $secret = $contactConfig['recaptcha_secret'] ?? '';
        }
        if (!is_string($secret) || $secret === '') { http_response_code(503); echo 'Het formulier is tijdelijk niet beschikbaar.'; exit; }

        //get verify response data
        $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$secret.'&response='.$_POST['g-recaptcha-response']);
        $responseData = json_decode($verifyResponse);

        if (!$responseData->success) {
            echo $robot;
            exit;
        }
    } else {
        echo $robot;
        exit;
    }

    $naam = trim(strip_tags($_POST["name"]), " \n");
    $mail = trim(strip_tags($_POST["mailadr"]), " \n");
    $who = trim(strip_tags($_POST["whoTo"]), " \n");
    $area = trim(strip_tags($_POST["tArea"]), " \n");
    $toMail = "";

    if ($naam != "" && $who != "") {
        $headers = "From: $naam <" . $mail . ">\r\n";

        switch ($who) {
            case "gebe":
                $toMail = $MAIL_ADDRESS_GEBE;
                break;
            case "verhuur":
                $toMail = $MAIL_ADDRESS_VERHUUR;
                break;
            case "bestuur":
                $toMail = $MAIL_ADDRESS_BESTUUR;
                break;
            case "sponsor":
                $toMail = $MAIL_ADDRESS_BESTUUR;
                break;
            case "site":
                $toMail = $MAIL_ADDRESS_WEBSITE;
                break;
            default:
                $toMail = "";
                break;
        }
    }

    $subject = "[SV-Contact]: Vraag/opmerking van: \"$naam\"";
    $labels = ['gebe' => 'Groepsbegeleiding', 'verhuur' => 'Verhuur', 'bestuur' => 'Bestuur', 'sponsor' => 'Sponsoring', 'site' => 'Website'];
    $message = mailHtmlRender('contact.groep', [
        'naam' => $naam, 'email' => $mail, 'groep' => $labels[$who] ?? $who, 'bericht' => $area
    ]);
    $subject = mailHtmlSubject($message);

    if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        header('HTTP/1.1 400 Bad Request');
        echo "Fout: E-mailadres ongeldig.";
        exit;
    }
    else if (!sendMail($toMail, $subject, $message, $mail, siteMailInlineImages())[0]) {
        header('HTTP/1.1 400 Bad Request');
        echo "Fout: Verzenden e-mail mislukt.";
        exit;
    } else {
        header('HTTP/1.1 200 Ok');
        echo 'Hallo ' . $naam . ', je vraag of opmerking is verstuurd. Er zal zo spoedig mogelijk een antwoord gegeven worden.';
        exit;
    }
}
?>
