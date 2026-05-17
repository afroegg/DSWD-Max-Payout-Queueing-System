<?php
function env_value($names) {
    foreach ($names as $name) {
        $value = getenv($name);
        if ($value !== false && trim($value) !== '') {
            return trim($value);
        }
    }
    return '';
}

$db_host = env_value(['DB_HOST', 'MYSQLHOST', 'MYSQL_HOST']);
$db_user = env_value(['DB_USER', 'MYSQLUSER', 'MYSQL_USER']);
$db_pass = env_value(['DB_PASS', 'MYSQLPASSWORD', 'MYSQL_PASSWORD']);
$db_name = env_value(['DB_NAME', 'MYSQLDATABASE', 'MYSQL_DATABASE']);
$db_port = env_value(['DB_PORT', 'MYSQLPORT', 'MYSQL_PORT']);

$database_url = env_value(['MYSQL_URL', 'DATABASE_URL', 'JAWSDB_URL', 'CLEARDB_DATABASE_URL']);

if ($database_url !== '' && ($db_host === '' || $db_user === '' || $db_name === '' || $db_port === '')) {
    $parts = parse_url($database_url);

    if ($parts !== false) {
        $db_host = $db_host !== '' ? $db_host : ($parts['host'] ?? '');
        $db_user = $db_user !== '' ? $db_user : ($parts['user'] ?? '');
        $db_pass = $db_pass !== '' ? $db_pass : ($parts['pass'] ?? '');
        $db_port = $db_port !== '' ? $db_port : ($parts['port'] ?? 3306);

        if ($db_name === '' && isset($parts['path'])) {
            $db_name = ltrim($parts['path'], '/');
        }
    }
}

$is_render = getenv('RENDER') || getenv('RENDER_SERVICE_ID') || getenv('RENDER_EXTERNAL_URL');

if ($db_host === '' || $db_user === '' || $db_name === '' || $db_port === '') {
    if ($is_render) {
        die('Database config missing. Add database environment variables in Render.');
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

$columnCheck = $conn->query("SHOW COLUMNS FROM beneficiaries LIKE 'is_pregnant'");

if ($columnCheck && $columnCheck->num_rows === 0) {
    $conn->query("ALTER TABLE beneficiaries ADD COLUMN is_pregnant TINYINT(1) NOT NULL DEFAULT 0 AFTER sms_opt_in");
}
?>
