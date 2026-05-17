<?php
include('../auth/check.php');
include('../config/db.php');

date_default_timezone_set('Asia/Manila');

function goBack() {
    header('Location: ../staff/verifier.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') goBack();

$beneficiary_id = intval($_POST['beneficiary_id'] ?? 0);
$new_queue_type_input = strtoupper(trim($_POST['new_queue_type'] ?? ''));

if ($beneficiary_id <= 0) goBack();
if ($new_queue_type_input !== 'PAL' && $new_queue_type_input !== 'PRIO') goBack();

if ($new_queue_type_input === 'PRIO') {
    $new_queue_type = 'priority';
    $prefix = 'PRIO-';
    $substringStart = 6;
} else {
    $new_queue_type = 'regular';
    $prefix = 'PAL-';
    $substringStart = 5;
}

$getCurrent = $conn->prepare("SELECT id, workflow_status FROM queue_entries WHERE beneficiary_id = ? AND DATE(transaction_date) = CURDATE() AND (workflow_status IS NULL OR workflow_status != 'CANCELLED') ORDER BY id DESC LIMIT 1");
$getCurrent->bind_param('i', $beneficiary_id);
$getCurrent->execute();
$currentResult = $getCurrent->get_result();

if (!$currentResult || $currentResult->num_rows === 0) goBack();

$current = $currentResult->fetch_assoc();
$queue_id = intval($current['id']);
if ($current['workflow_status'] === 'PAID') goBack();

$getLast = $conn->prepare("SELECT MAX(CAST(SUBSTRING(queue_number, ?) AS UNSIGNED)) AS last_number FROM queue_entries WHERE DATE(transaction_date) = CURDATE() AND queue_type = ? AND queue_number LIKE CONCAT(?, '%') AND id != ?");
$getLast->bind_param('issi', $substringStart, $new_queue_type, $prefix, $queue_id);
$getLast->execute();
$lastResult = $getLast->get_result();

$nextNumber = 1;
if ($lastResult && $lastResult->num_rows > 0) {
    $lastRow = $lastResult->fetch_assoc();
    if ($lastRow['last_number'] !== null) $nextNumber = intval($lastRow['last_number']) + 1;
}

$new_queue_number = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

$update = $conn->prepare("UPDATE queue_entries SET queue_number = ?, queue_type = ?, status = 'waiting', workflow_status = 'WAITING_STEP_2', table_number = NULL, called_at = NULL, assessed_at = NULL, paid_at = NULL WHERE id = ?");
$update->bind_param('ssi', $new_queue_number, $new_queue_type, $queue_id);
$update->execute();

goBack();
?>
