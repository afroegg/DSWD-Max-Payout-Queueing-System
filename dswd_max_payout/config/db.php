<?php
/*
    Database connection for Render/Railway and local XAMPP.

    Online deployment:
    Set these in Render Environment Variables:
    DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT

    This also supports Railway's common variable names:
    MYSQLHOST, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE, MYSQLPORT

    Local XAMPP:
    If no online env vars exist, it falls back to:
    localhost, root, empty password, dswd_max_payout, 3306
*/

$db_host = getenv('DB_HOST') ?: getenv('MYSQLHOST');
$db_user = getenv('DB_USER') ?: getenv('MYSQLUSER');
$db_pass = getenv('DB_PASS') ?: getenv('MYSQLPASSWORD');
$db_name = getenv('DB_NAME') ?: getenv('MYSQLDATABASE');
$db_port = getenv('DB_PORT') ?: getenv('MYSQLPORT');

$is_render = getenv('RENDER') || getenv('RENDER_SERVICE_ID') || getenv('RENDER_EXTERNAL_URL');

if (!$db_host || !$db_user || !$db_name || !$db_port) {
    if ($is_render) {
        die('Database environment variables are missing. Add DB_HOST, DB_USER, DB_PASS, DB_NAME, and DB_PORT in Render.');
    }

    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'dswd_max_payout';
    $db_port = 3306;
}

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, intval($db_port));

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
?>
