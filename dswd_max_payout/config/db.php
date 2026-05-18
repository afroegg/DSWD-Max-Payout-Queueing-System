<?php
function get_env_value($key, $default = '') {
    $value = getenv($key);
    if ($value === false || trim($value) === '') {
        return $default;
    }
    return trim($value);
}

$host = get_env_value('DB_HOST', 'localhost');
$user = get_env_value('DB_USER', 'root');
$pwd = get_env_value('DB_PASS', '');
$database = get_env_value('DB_NAME', 'railway');
$port = intval(get_env_value('DB_PORT', '3306'));

$conn = new mysqli($host, $user, $pwd, $database, $port);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

$columnCheck = $conn->query("SHOW COLUMNS FROM beneficiaries LIKE 'is_pregnant'");

if ($columnCheck && $columnCheck->num_rows === 0) {
    $conn->query("ALTER TABLE beneficiaries ADD COLUMN is_pregnant TINYINT(1) NOT NULL DEFAULT 0 AFTER sms_opt_in");
}
?>
