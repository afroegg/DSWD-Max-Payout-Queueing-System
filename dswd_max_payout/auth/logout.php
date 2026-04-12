<?php
session_start();

/* CLEAR SESSION */
$_SESSION = [];
session_destroy();

/* DESTROY COOKIE (CRITICAL) */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

/* FORCE NO CACHE */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

/* REDIRECT */
header("Location: login.php");
exit;
?>