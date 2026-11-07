<?php
require_once __DIR__ . '/security.php';
header('Content-Type: application/json');
try {
    $logged = adminIsLoggedIn();
    echo json_encode([
        'loggedIn' => $logged,
        'portals' => [
            'admin' => $logged && adminCanPortal('admin'),
            'beheer' => $logged && adminCanPortal('beheer'),
            'leden' => $logged && adminCanPortal('leden')
        ],
        'ledenAvailable' => is_file(dirname(__DIR__).'/leden/beheer.php'),
        'name' => $logged ? $_SESSION['ADMIN_NAME'] : null,
        'role' => $logged ? $_SESSION['ADMIN_ROLE'] : null,
        'modules' => $logged ? $_SESSION['ADMIN_MODULES'] : []
    ]);
} catch (Throwable $error) {
    http_response_code(503);
    echo '{"loggedIn":false}';
}
