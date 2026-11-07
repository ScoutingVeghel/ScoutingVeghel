<?php
// SMTP settings come from the local environment or private hosting configuration.
function smtpConfiguration() {
    if (getenv('SV_ENV') === 'local') {
        // Local mail is captured on disk; never load hosting credentials.
        return [
            'host' => 'localhost',
            'port' => 587,
            'username' => '',
            'password' => '',
            'from' => 'website@example.invalid',
            'from_name' => 'Website | Scouting Veghel'
        ];
    }
    if (getenv('SV_SMTP_HOST') !== false) {
        $config = [
            'host' => getenv('SV_SMTP_HOST'),
            'port' => getenv('SV_SMTP_PORT') ?: 587,
            'username' => getenv('SV_SMTP_USER'),
            'password' => getenv('SV_SMTP_PASSWORD'),
            'from' => getenv('SV_SMTP_FROM')
        ];
    } else {
        $path = getenv('SV_SMTP_CONFIG') ?: dirname(__DIR__, 2) . '/private/mail.php';
        $file = realpath($path);
        $webroot = realpath(dirname(__DIR__));
        if (
            !$file ||
            !is_file($file) ||
            $file === $webroot ||
            strpos($file, $webroot . DIRECTORY_SEPARATOR) === 0
        ) {
            throw new RuntimeException('Configureer SMTP buiten public_html.');
        }
        $config = (static function ($file) {
            $loaded = require $file;
            if (is_array($loaded)) {
                return $loaded;
            }
            // Existing private hosting files may still define the original variables.
            if (isset($SMTP_SERVER, $SMTP_PORT, $SMTP_USER, $SMTP_PASSWORD, $SMTP_MAIL_FROM)) {
                return [
                    'host' => $SMTP_SERVER,
                    'port' => $SMTP_PORT,
                    'username' => $SMTP_USER,
                    'password' => $SMTP_PASSWORD,
                    'from' => $SMTP_MAIL_FROM
                ];
            }
            throw new RuntimeException('Ongeldige private SMTP-configuratie.');
        })($file);
    }
    if (!is_array($config)) {
        throw new RuntimeException('Ongeldige SMTP-configuratie.');
    }
    foreach (['host', 'username', 'password', 'from'] as $key) {
        if (!isset($config[$key]) || !is_string($config[$key]) || $config[$key] === '') {
            throw new RuntimeException('Onvolledige SMTP-configuratie.');
        }
    }
    $port = filter_var($config['port'] ?? 587, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 65535]
    ]);
    if (
        $port === false ||
        !filter_var($config['from'], FILTER_VALIDATE_EMAIL) ||
        preg_match('/[\r\n]/', $config['host'])
    ) {
        throw new RuntimeException('Ongeldige SMTP-configuratie.');
    }
    $fromName = getenv('SV_SMTP_FROM_NAME') ?: ($config['from_name'] ?? 'Website | Scouting Veghel');
    if (!is_string($fromName) || trim($fromName) === '' || preg_match('/[\r\n]/', $fromName)) {
        throw new RuntimeException('Ongeldige afzendernaam.');
    }
    $config['from_name'] = trim($fromName);
    $config['port'] = $port;
    return $config;
}
