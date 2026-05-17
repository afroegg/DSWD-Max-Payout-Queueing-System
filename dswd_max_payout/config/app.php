<?php
$defaultBase = 'http://localhost/dswd_max_payout';
$envBase = getenv('APP_BASE_URL');

if ($envBase !== false && trim($envBase) !== '') {
    define('BASE_URL', rtrim(trim($envBase), '/'));
} else {
    define('BASE_URL', $defaultBase);
}

$envSemaphore = getenv('SEMAPHORE_API_KEY');
define('SEMAPHORE_API_KEY', ($envSemaphore !== false && trim($envSemaphore) !== '') ? trim($envSemaphore) : '');
?>
