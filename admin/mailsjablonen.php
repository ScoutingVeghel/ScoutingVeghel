<?php
$portalModule='mailsjablonen';
require __DIR__.'/module-page.php';

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../php/portal-mail-html.php';
$mailTabs = array_filter(
    mailHtmlCatalog(),
    function ($id) {
        $permissions = [
            'verhuur' => 'mailsjablonen',
            'contact' => 'contact-sjablonen',
            'leden' => 'leden-sjablonen',
            'ledenadministratie' => 'leden-sjablonen'
        ];
        return adminCan($permissions[explode('.', $id)[0]]);
    },
    ARRAY_FILTER_USE_KEY
);
$tab = beheerInput('tab', '');
$notice = '';
$error = '';
$submitted = null;
$submittedSubject = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted = beheerInput('naam');
    if (substr($posted, -5) === '.html') {
        $posted = substr($posted, 0, -5);
    }
    if (!isset($mailTabs[$posted])) {
        http_response_code(403);
        exit('Geen toegang tot dit sjabloon.');
    }
    $tab = $posted;
}
$sectionLabels = ['admin' => 'Admin', 'contact' => 'Contact', 'verhuur' => 'Verhuur', 'ledenadministratie' => 'Ledenadministratie', 'powerautomate' => 'Power Automate'];
$mailSections = [];
foreach ($sectionLabels as $sectionId => $label) {
    $permission = in_array($sectionId, ['ledenadministratie', 'powerautomate'], true) ? 'leden-sjablonen' : ($sectionId === 'contact' ? 'contact-sjablonen' : 'mailsjablonen');
    if (adminCan($permission)) $mailSections[$sectionId] = [];
}
foreach ($mailTabs as $id => $mail) {
    $sectionId = strpos($id, 'leden.') === 0 ? 'powerautomate' : explode('.', $id)[0];
    $mailSections[$sectionId][$id] = $mail;
}
$section = beheerInput('section', 'verhuur');
if ($section === 'website') $section = 'verhuur';
if (isset($mailTabs[$tab])) {
    $section = strpos($tab, 'leden.') === 0 ? 'powerautomate' : explode('.', $tab)[0];
} elseif (!isset($mailSections[$section])) {
    $section = key($mailSections);
}
$mailTabs = $mailSections[$section] ?? [];
$memberMailTypes = ['register'=>'Inschrijven', 'leave'=>'Uitschrijven', 'transfer'=>'Overvliegen','change'=>'Gegevens aanpassen'];
$memberMailType = beheerInput('type', 'register');
if ($section === 'ledenadministratie') {
    if (isset($mailTabs[$tab])) $memberMailType = explode('.', $tab)[1];
    if (!isset($memberMailTypes[$memberMailType])) $memberMailType = 'register';
    $mailTabs = array_filter($mailTabs, static function ($id) use ($memberMailType) {
        return strpos($id, 'ledenadministratie.'.$memberMailType.'.') === 0;
    }, ARRAY_FILTER_USE_KEY);
}

function mailEditorSections($sections, $labels, $active) {
    echo '<nav class="access-tabs" aria-label="Soort mailsjablonen">';
    foreach ($sections as $id => $mails) {
        echo '<a href="mailsjablonen.php?section='.beheerEscape($id).'"'.($active === $id ? ' aria-current="page"' : '').'>'.beheerEscape($labels[$id]).'</a>';
    }
    echo '</nav>';
}
if (!$mailTabs) {
    beheerHead('Mailsjablonen');
    echo '<main class="mail-editor-page"><h1>Mailsjablonen</h1>';
    mailEditorSections($mailSections, $sectionLabels, $section);
    echo '<h2>'.beheerEscape($sectionLabels[$section] ?? 'Mailsjablonen').'</h2><p>Voor dit onderdeel zijn nog geen bewerkbare mailsjablonen beschikbaar.</p></main>';
    beheerFoot();
    return;
}
if (!isset($mailTabs[$tab])) $tab = key($mailTabs);
$key = $tab . '.html';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = beheerInput('action');
        if ($action === 'preview' || $action === 'save') {
            $submitted = beheerInput('inhoud');
            if (isset($_POST['onderwerp'])) {
                $submittedSubject = $_POST['onderwerp'];
                $submitted = mailHtmlWithSubject($submitted, $submittedSubject);
            }
            mailHtmlValidate($tab, $submitted);
            if ($action === 'preview') {
                $html = mailHtmlPreview($tab, $submitted);
                while (ob_get_level()) {
                    ob_end_clean();
                }
                header('Content-Type: application/json');
                echo json_encode(['html' => $html, 'subject' => mailHtmlSubject($html)]);
                exit();
            }
            $stmt = $database->prepare(
                'INSERT INTO beheer_mail_templates (naam,inhoud) VALUES (?,?) ON DUPLICATE KEY UPDATE inhoud=VALUES(inhoud)'
            );
            $stmt->bind_param('ss', $key, $submitted);
            $stmt->execute();
            $stmt->close();
            $notice = 'Het volledige mailsjabloon is opgeslagen.';
        } elseif ($action === 'reset') {
            $stmt = $database->prepare('DELETE FROM beheer_mail_templates WHERE naam=?');
            $stmt->bind_param('s', $key);
            $stmt->execute();
            $stmt->close();
            $notice = 'De standaardopmaak is hersteld.';
        } else {
            throw new InvalidArgumentException('Ongeldige actie.');
        }
    }
} catch (InvalidArgumentException $e) {
    $error = $e->getMessage();
} catch (Throwable $e) {
    http_response_code(503);
    $error = 'Het sjabloon kon niet worden geladen of opgeslagen.';
    error_log('Scouting Veghel: HTML template editor failed (' . get_class($e) . ').');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && beheerInput('action') === 'preview') {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => $error]);
    exit();
}
// Read afresh after a save/reset; legacy text overrides remain part of the default template.
$stmt = $database->prepare('SELECT inhoud FROM beheer_mail_templates WHERE naam=?');
$stmt->bind_param('s', $key);
$stmt->execute();
$stmt->bind_result($stored);
$exists = $stmt->fetch();
$stmt->close();
$source = $submitted ?? ($exists ? $stored : mailHtmlDefault($tab));
// Previously saved member templates also need a logo usable outside this site's mailer.
if (strpos($tab, 'leden.') === 0) {
    $source = str_replace('cid:scouting-veghel-logo', siteMailPublicLogoUrl(), $source);
}
if ($submitted === null || !$error) {
    $source = mailHtmlPretty($source);
}
$preview = '';
try {
    $preview = mailHtmlPreview($tab, $source);
} catch (Throwable $e) {
    if (!$error) {
        $error =
            $e instanceof InvalidArgumentException
                ? $e->getMessage()
                : 'Het voorbeeld kon niet worden geladen.';
    }
}
beheerHead('Mailsjablonen');
$subject = is_string($submittedSubject) ? $submittedSubject : '';
if ($submittedSubject === null) {
    try {
        $subject = mailHtmlSubject($source);
    } catch (Throwable $e) {
        $subject = '';
    }
}
?>
<main class="mail-editor-page"><h1>Mailsjablonen</h1><p>Bewerk het onderwerp en de HTML-mailinhoud in de twee aparte blokken. Het voorbeeld gebruikt fictieve gegevens en verandert direct tijdens het typen. Alleen Opslaan bewaart je wijzigingen.</p>
<?php if ($notice): ?><p class="success" role="status"><?= beheerEscape(
    $notice
) ?></p><?php endif; ?>
<?php if ($error): ?><p class="error" role="alert"><?= beheerEscape($error) ?></p><?php endif; ?>
<?php mailEditorSections($mailSections, $sectionLabels, $section); ?>
<?php if (
    $section === 'powerautomate'
): ?><p>Deze formats zijn bedoeld voor Power Automate. Kopieer het onderwerp en de HTML-mailinhoud naar je flow. Power Automate vult je expressies in en verstuurt de mail.</p><?php endif; ?>
<?php if ($section === 'ledenadministratie'): ?>
<nav class="access-tabs" aria-label="Ledenadministratie formulieren"><?php foreach ($memberMailTypes as $type => $label): ?><a href="mailsjablonen.php?section=ledenadministratie&amp;type=<?= beheerEscape($type) ?>" <?= $memberMailType === $type ? 'aria-current="page"' : '' ?>><?= beheerEscape($label) ?></a><?php endforeach; ?></nav>
<?php endif; ?>
<nav class="access-tabs" aria-label="Automatische mails"><?php foreach (
    $mailTabs
    as $id => $mail
): ?><a href="mailsjablonen.php?tab=<?= beheerEscape($id) ?>" <?= $tab === $id
    ? 'aria-current="page"'
    : '' ?>><?= beheerEscape($mail['label']) ?></a><?php endforeach; ?></nav>
<h2><?= beheerEscape($mailTabs[$tab]['label']) ?></h2><p><?= beheerEscape(
    $mailTabs[$tab]['description']
) ?></p>
<div class="mail-editor-layout"><form method="post" action="mailsjablonen.php" id="mail-html-form"><input type="hidden" name="naam" value="<?= beheerEscape(
    $key
) ?>">
<section class="mail-editor-block" aria-label="Onderwerp"><label for="mail-subject"><strong>Titel / onderwerp</strong></label><input id="mail-subject" name="onderwerp" type="text" maxlength="500" required value="<?= beheerEscape(
    $subject
) ?>"><p>Dit is het onderwerp in de inbox van de ontvanger. Invoegvelden zijn ook hier te gebruiken.</p></section>
<section class="mail-editor-block" aria-label="Mailinhoud"><label for="mail-html"><strong>Mailinhoud (HTML)</strong></label><textarea id="mail-html" name="inhoud" spellcheck="false" maxlength="60000" required><?= beheerEscape(
    mailHtmlWithoutSubject($source)
) ?></textarea>
</section>
<div class="mail-editor-actions"><button name="action" value="save">Opslaan</button><button name="action" value="reset" class="button-secondary" formnovalidate>Standaard herstellen</button></div>
<details><summary>Beschikbare invoegvelden</summary><p>Deze worden bij verzending automatisch ingevuld. Houd verplichte links en gegevens in het sjabloon.</p><ul><?php foreach (
    $mailTabs[$tab]['variables']
    as $variable
): ?><li><code>{{<?= beheerEscape($variable) ?>}}</code><?= in_array(
    $variable,
    $mailTabs[$tab]['required'],
    true
)
    ? ' (verplicht)'
    : '' ?></li><?php endforeach; ?></ul><?php if (
    strpos($tab, 'leden.') === 0
): ?><p>Voor Power Automate mag je <code>{{speltak}}</code> vervangen door je eigen expressie, bijvoorbeeld <code>@{outputs('Create_item')?['body/field_22']}</code>. Dit kan in het onderwerp en in de mailtekst. Het voorbeeld toont die code letterlijk; Power Automate vult de waarde in. Het logo gebruikt een openbare HTTPS-link en heeft geen bijlage nodig.</p><?php else: ?><p>Gebruik <code>cid:scouting-veghel-logo</code> als afbeelding voor het logo. De afbeelding wordt met de mail meegestuurd.</p><?php endif; ?></details></form>
<section class="mail-preview-panel" aria-label="Mailvoorbeeld"><h3>Voorbeeld</h3><p id="mail-preview-status" role="status">Voorbeeld met fictieve gegevens.</p><iframe id="mail-preview" title="Voorbeeld van de automatische mail" sandbox="allow-same-origin" scrolling="no" referrerpolicy="no-referrer" srcdoc="<?= beheerEscape(
    $preview
) ?>"></iframe></section></div>
<script src="/admin/mail-editor.js?v=20261031-subject"></script></main><?php beheerFoot();
