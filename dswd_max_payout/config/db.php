<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "dswd_max_payout";

$conn = new mysqli(
    "HOST_FROM_PROVIDER",
    "USERNAME",
    "PASSWORD",
    "DATABASE"
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

date_default_timezone_set('Asia/Manila');
?>
