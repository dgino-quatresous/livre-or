<?php
require_once __DIR__ . '/header.php';
ensure_session_started();
// Supprime toutes les variables de session et détruit la session
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();
header('Location: index.php');
exit;
?>
