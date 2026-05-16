<?php
/*
    Database connection.
    Works for both Render/Railway environment variables and local XAMPP fallback.

    Render/Railway recommended env vars:
    DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT

    XAMPP fallback:
    host: localhost
    user: root
    pass: empty
    db: dswd_max_payout
*/

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'dswd_max_payout';
$db_port = intval(getenv('DB_PORT') ?: 3306);

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
?>
