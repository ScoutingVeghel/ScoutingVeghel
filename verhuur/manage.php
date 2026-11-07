<?php
require_once dirname(__DIR__).'/admin/security.php';
adminRequireLogin();
$adminBootStage = 'instellingen';
try {
    require_once __DIR__ . '/bootstrap.php';
    $adminBootStage = 'templates';
    require_once __DIR__ . '/templates/includes.php';
    require_once __DIR__ . '/calendar-functions.php';
    $adminBootStage = 'pagina';
    $module = beheerInput('m', 'inbox');
    switch ($module) {
        case 'inbox':
            echo inbox(beheerId('s', 0), beheerId('i', 0));
            break;
        case 'search':
            echo search();
            break;
        case 'toevoegen':
            echo toevoegen();
            break;
        case 'agenda': // Preserve old bookmarks and direct links.
        case 'kalender':
            try {
                $start = adminCalendarStart(
                    beheerInput('from', ''),
                    isset($_GET['y']) ? beheerId('y') : null
                );
            } catch (InvalidArgumentException $error) {
                http_response_code(400);
                beheerHead('Kalender');
                echo '<p>Kies een geldige startmaand.</p>';
                beheerFoot();
                break;
            }
            $rows = adminCalendarReservations(
                $database,
                $start->format('Y-m-d 00:00:00'),
                $start
                    ->modify('+14 months')
                    ->modify('last day of this month')
                    ->format('Y-m-d 23:59:59')
            );
            $months = [];
            for ($i = 0; $i < 15; $i++) {
                $month = $start->modify('+' . $i . ' months');
                $months[] = displayMonth(
                    (int) $month->format('n'),
                    (int) $month->format('Y'),
                    $rows,
                    null,
                    $start->format('Y-m')
                );
            }
            beheerHead('Kalender');
            echo adminCalendarView($start, $months);
            beheerFoot();
            break;
        default:
            http_response_code(404);
            beheerHead('Onbekende pagina');
            echo '<p>Deze verhuurpagina bestaat niet.</p>';
            beheerFoot();
    }
} catch (Throwable $error) {
    http_response_code(503);
    $reference = 'BEHEER-' . strtoupper($adminBootStage) . '-' . (int) $error->getCode();
    error_log('Scouting Veghel: ' . $reference . ' (' . get_class($error) . ').');
    echo beheerDocumentStart(
        'Verhuur tijdelijk niet beschikbaar',
        '',
        'width=device-width,initial-scale=1'
    ) .
        '<main class="empty-state">' .
        '<h1>Verhuur kon niet worden geladen</h1>' .
        '<p>Het laden van de verhuurpagina is mislukt. Geef onderstaande foutcode door zodat dit gericht kan worden hersteld.</p>' .
        '<p>' .
        '<strong>' .
        htmlspecialchars($reference, ENT_QUOTES, 'UTF-8') .
        '</strong></p><p><a href="/verhuur/index.html">Opnieuw proberen</a> · <a href="/admin/logout.php">Uitloggen</a></p></main></body></html>';
}
