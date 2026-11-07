<?php
$portalModule = 'mfa';
require __DIR__ . '/module-page.php';
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/mfa-functions.php';
$notice = '';
$error = '';
$recoveryCodes = [];
$name = $_SESSION['ADMIN_NAME'];
if (time() - ($_SESSION['MFA_SETUP_TIME'] ?? 0) > 600) {
    unset($_SESSION['MFA_SETUP'], $_SESSION['MFA_SETUP_TIME']);
}
$stmt = $database->prepare('SELECT mfa_secret FROM verhuur_administrators WHERE naam=?');
$stmt->bind_param('s', $name);
$stmt->execute();
$stmt->bind_result($active);
$stmt->fetch();
$stmt->close();
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = beheerInput('action');
        if ($action === 'start' && !$active) {
            if (empty($_SESSION['MFA_SETUP'])) {
                $_SESSION['MFA_SETUP'] = adminMfaSecret();
                $_SESSION['MFA_SETUP_TIME'] = time();
            }
        } elseif ($action === 'enable' && !$active) {
            $secret = $_SESSION['MFA_SETUP'] ?? '';
            if (!$secret || time() - ($_SESSION['MFA_SETUP_TIME'] ?? 0) > 600) {
                throw new InvalidArgumentException('Start het instellen opnieuw.');
            }
            if (!adminRateAttempt($name, 'mfa-setup')) {
                http_response_code(429);
                throw new InvalidArgumentException(
                    'Te veel codepogingen voor dit account. Probeer het over 15 minuten opnieuw.'
                );
            }
            $step = adminMfaStep($secret, beheerInput('code'));
            if ($step === false) {
                throw new InvalidArgumentException('De app-code is onjuist.');
            }
            adminRateSuccess($name, 'mfa-setup');
            for ($i = 0; $i < 8; $i++) {
                $recoveryCodes[] = bin2hex(random_bytes(8));
            }
            $hashes = json_encode(
                array_map(function ($code) {
                    return hash('sha256', $code);
                }, $recoveryCodes)
            );
            $stmt = $database->prepare(
                'UPDATE verhuur_administrators SET mfa_secret=?,mfa_last=?,mfa_recovery=? WHERE naam=? AND mfa_secret IS NULL'
            );
            $stmt->bind_param('siss', $secret, $step, $hashes, $name);
            $stmt->execute();
            $ok = $stmt->affected_rows === 1;
            $stmt->close();
            if (!$ok) {
                throw new RuntimeException('Setup conflict');
            }
            unset($_SESSION['MFA_SETUP'], $_SESSION['MFA_SETUP_TIME']);
            $active = $secret;
            session_regenerate_id(true);
            $_SESSION['ADMIN_MFA_PROOF'] = hash('sha256', $secret);
            $notice =
                'Tweestapsverificatie staat aan. Bewaar onderstaande herstelcodes veilig; ze worden één keer getoond.';
        } elseif ($action === 'disable' && $active) {
            if (adminMfaRequired()) {
                throw new InvalidArgumentException('Tweestapsverificatie is verplicht en kan niet worden uitgezet.');
            }
            if (!adminRateAttempt($name, 'mfa-setup') || !adminMfaVerify($database, $name, beheerInput('code'))) {
                throw new InvalidArgumentException('De code is onjuist, al gebruikt of er zijn te veel pogingen gedaan.');
            }
            adminRateSuccess($name, 'mfa-setup');
            $stmt = $database->prepare('UPDATE verhuur_administrators SET mfa_secret=NULL,mfa_last=-1,mfa_recovery=NULL WHERE naam=?');
            $stmt->bind_param('s', $name);
            $stmt->execute();
            $stmt->close();
            unset($_SESSION['ADMIN_MFA_PROOF']);
            $active = null;
            $notice = 'Tweestapsverificatie is uitgezet.';
        } else {
            throw new InvalidArgumentException('Ongeldige actie.');
        }
    }
} catch (InvalidArgumentException $e) {
    $error = $e->getMessage();
} catch (Throwable $e) {
    http_response_code(503);
    $error = 'De wijziging kon niet worden opgeslagen.';
}
echo beheerDocumentStart('Tweestapsverificatie | Beheer', '', 'width=device-width, initial-scale=1', [
    '/admin/mfa.css?v=20261101-qr-large'
]);
?>
<main class="maintenance-page"><h1>Tweestapsverificatie</h1><p><?= adminMfaRequired()
    ? 'Tweestapsverificatie is verplicht voor alle beheeraccounts. Stel je authenticator-app in om verder te gaan.'
    : 'Tweestapsverificatie is lokaal optioneel. Je kunt je account extra beveiligen met een authenticator-app.' ?> Naast je wachtwoord gebruik je dan een code die elke 30 seconden verandert.</p>
<?php if ($notice): ?><p role="status"><?= beheerEscape($notice) ?></p><?php endif; ?>
<?php if ($error): ?><p class="error" role="alert"><?= beheerEscape($error) ?></p><?php endif; ?>
<?php if (
    $recoveryCodes
): ?><h2>Herstelcodes</h2><p>Elke code werkt één keer. Bewaar ze buiten deze website.</p><pre><?= beheerEscape(
    implode("\n", $recoveryCodes)
) ?></pre><?php endif; ?>
<?php if (
    $active
): ?><p><strong>Tweestapsverificatie staat aan.</strong><?php if (adminMfaRequired()): ?> Deze beveiliging is verplicht en kan niet worden uitgezet.<?php endif; ?></p>
<?php if (!adminMfaRequired()): ?><form method="post"><input type="hidden" name="action" value="disable"><label>App-code of herstelcode <input name="code" autocomplete="one-time-code" required maxlength="16"></label><button>Uitzetten</button></form><?php endif; ?>
<p><a href="<?= beheerEscape(portalSafeReturn($_SESSION['MFA_RETURN'] ?? '/admin/')) ?>">Verder naar het portaal</a></p>
<?php elseif (
    !empty($_SESSION['MFA_SETUP'])
): ?><h2>Koppel je authenticator-app</h2><p>Open je authenticator-app, voeg een account toe en scan deze QR-code.</p>
<div id="mfa-qr" class="mfa-qr" data-provisioning-uri="<?= beheerEscape(adminMfaProvisioningUri($name, $_SESSION['MFA_SETUP'])) ?>"></div>
<p id="mfa-qr-fallback" hidden>De QR-code kon niet worden getoond. Gebruik de onderstaande sleutel om het account handmatig toe te voegen.</p>
<noscript><p>Schakel JavaScript in om de QR-code te zien, of gebruik de onderstaande sleutel.</p></noscript>
<script src="/admin/vendor/qrcode-generator/qrcode.js" defer></script>
<script src="/admin/mfa-qr.js?v=20261101-qr-large" defer></script>
<details class="mfa-manual"><summary>Handmatig instellen met een sleutel</summary><p>Voeg in je authenticator-app een account toe: <strong>Scouting Veghel (<?= beheerEscape(
    $name
) ?>)</strong>. Kies een tijdgebonden code (TOTP), 6 cijfers, 30 seconden, SHA-1.</p><p>Geheime sleutel: <code><?= beheerEscape(
    $_SESSION['MFA_SETUP']
) ?></code></p></details><p>De QR-code en sleutel zijn 10 minuten geldig voor het instellen. Deel ze niet.</p><form method="post"><input type="hidden" name="action" value="enable"><label>Code uit de app <input name="code" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" required maxlength="6"></label><button>Controleren en activeren</button></form>
<?php else: ?><form method="post"><input type="hidden" name="action" value="start"><button>Tweestapsverificatie instellen</button></form><?php endif; ?></main>
<?php beheerFoot();
