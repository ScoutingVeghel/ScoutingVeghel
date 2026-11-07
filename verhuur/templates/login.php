<?php
require_once __DIR__ . '/../../php/portal-document.php';
function login($error = '') {
    $message = htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return beheerDocumentStart(
        'Inloggen | Scouting Veghel verhuur',
        'admin-login',
        'width=device-width, initial-scale=1'
    ) .
        "<main id='login-form'>" .
        "<img class='login-logo' src='/admin/assets/logo.svg' alt='Scouting Veghel'>" .
        '<h1>Scouting Veghel verhuur</h1>' .
        "<p class='login-intro'>Welkom terug. Log in om de verhuur en reserveringen te beheren.</p>" .
        "<p role='alert'>{$message}</p>" .
        "<form method='post' action='/verhuur/manage.php?m=inbox' target='_top'>
    <p>" .
        "<label for='admin-name'>Gebruikersnaam</label>" .
        '<br>' .
        "<input id='admin-name' type='text' name='gebruikersnaam' autocomplete='username' maxlength='133' required>" .
        "</p>
    <p>" .
        "<label for='admin-password'>Wachtwoord</label>" .
        '<br>' .
        "<input id='admin-password' type='password' name='wachtwoord' autocomplete='current-password' maxlength='72' required>" .
        "</p>
    <button type='submit'>Inloggen</button>" .
        '</form>' .
        "<p class='login-hint'>Na 30 minuten zonder activiteit word je automatisch uitgelogd.</p>" .
        "<a class='login-home' href='/index.html' target='_top'>Terug naar de website</a>" .
        '</main>' .
        '</body>' .
        '</html>';
}
