<?php
require_once dirname(__DIR__).'/php/database.php';
date_default_timezone_set('Europe/Amsterdam');
$host=parse_url('http://'.($_SERVER['HTTP_HOST']??''),PHP_URL_HOST);
$local=in_array($host,['localhost','127.0.0.1','[::1]'],true);
if(!$local && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off')) { http_response_code(403);exit('Gebruik HTTPS om dit formulier in te vullen.'); }
ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');
session_name('SVLEDENFORM');session_set_cookie_params(['lifetime'=>0,'path'=>'/leden/','secure'=>!$local,'httponly'=>true,'samesite'=>'Strict']);session_start();
header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
if(empty($_SESSION['FORM_CSRF'])) $_SESSION['FORM_CSRF']=bin2hex(random_bytes(32));
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
 $token=$_POST['_form_csrf']??null;
 if(!is_string($token) || !hash_equals($_SESSION['FORM_CSRF'],$token)) { http_response_code(403);exit('Het formulier is verlopen. Ververs de pagina en probeer opnieuw.'); }
}

function publicFormRateAttempt($name) {
    $path =
        getenv('SV_ENV') === 'local'
            ? dirname(__DIR__, 2) . '/local/run/public-leden-rate.json'
            : sys_get_temp_dir() . '/sv-public-leden-' . hash('sha256', __DIR__) . '.json';
    $file = @fopen($path, 'c+');
    if (!$file || !flock($file, LOCK_EX)) {
        if ($file) {
            fclose($file);
        }
        throw new RuntimeException('Login limiter unavailable');
    }
    @chmod($path, 0600);
    $now = time();
    $state = json_decode(stream_get_contents($file), true);
    if (!is_array($state)) {
        $state = [];
    }
    foreach ($state as $key => $entry) {
        if (($entry['until'] ?? 0) <= $now) {
            unset($state[$key]);
        }
    }
    $keys = [
        hash('sha256', 'ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown')),
        hash('sha256', 'user:' . strtolower($name))
    ];
    $allowed = count($state) < 10000;
    foreach ($keys as $key) {
        if (($state[$key]['count'] ?? 0) >= 10) {
            $allowed = false;
        }
    }
    if ($allowed) {
        foreach ($keys as $key) {
            if (!isset($state[$key])) {
                $state[$key] = ['count' => 0, 'until' => $now + 900];
            }
            $state[$key]['count']++;
        }
        $data = json_encode($state);
        rewind($file);
        $ok = ftruncate($file, 0) && fwrite($file, $data) === strlen($data) && fflush($file);
    } else {
        $ok = true;
    }
    flock($file, LOCK_UN);
    fclose($file);
    if (!$ok) {
        throw new RuntimeException('Cannot save login limiter');
    }
    return $allowed;
}
