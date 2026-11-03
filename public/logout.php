<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/auth.php';

startAppSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Sign out must be submitted from the dashboard.');
}

if (!appCsrfTokenIsValid($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Your sign-out request could not be verified. Return to the dashboard and try again.');
}

$_SESSION = [];
$cookieParameters = session_get_cookie_params();
if (ini_get('session.use_cookies')) {
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $cookieParameters['path'],
        'domain' => $cookieParameters['domain'],
        'secure' => $cookieParameters['secure'],
        'httponly' => $cookieParameters['httponly'],
        'samesite' => $cookieParameters['samesite'] ?? 'Lax',
    ]);
}

session_destroy();
header('Location: login.php?signed_out=1', true, 303);
exit;
