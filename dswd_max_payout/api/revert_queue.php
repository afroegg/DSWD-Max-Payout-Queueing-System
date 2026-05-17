<?php
include('../auth/check.php');
include('../config/db.php');

date_default_timezone_set('Asia/Manila');

function goBack($counter_number = 1) {
    header('Location: ../staff/counter.php?counter=' . intval($counter_number));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') goBack();

$queue_id = intval($_POST['queue_id'] ?? 0);
$counter_number = intval($_POST['counter_number'] ?? 1);
if ($counter_number <= 0) $counter_number = 1;
if ($queue_id <= 0) goBack($counter_number);

$check = $conn->prepare("SELECT id, workflow_status FROM queue_entries WHERE id = ? AND DATE(transaction_date) = CURDATE() LIMIT 1");
$check->bind_param('i', $queue_id);
$check->execute();
$result = $check->get_result();
if (!$result || $result->num_rows === 0) goBack($counter_number);

$row = $result->fetch_assoc();
$currentStatus = $row['workflow_status'];

if ($currentStatus === 'CALLED_STEP_2') {
    $newStatus = 'WAITING_STEP_2';
    $newOldStatus = 'waiting';
    $update = $conn->prepare("UPDATE queue_entries SET status = ?, workflow_status = ?, table_number = NULL, counter_number = NULL, paid_at = NULL WHERE id = ?");
    $update->bind_param('ssi', $newOldStatus, $newStatus, $queue_id);
} elseif ($currentStatus === 'WAITING_STEP_3') {
    $newStatus = 'CALLED_STEP_2';
    $newOldStatus = 'serving';
    $update = $conn->prepare("UPDATE queue_entries SET status = ?, workflow_status = ?, table_number = ?, counter_number = ?, assessed_at = NULL, paid_at = NULL WHERE id = ?");
    $update->bind_param('ssiii', $newOldStatus, $newStatus, $counter_number, $counter_number, $queue_id);
} elseif ($currentStatus === 'CALLED_STEP_3') {
    $newStatus = 'WAITING_STEP_3';
    $newOldStatus = 'waiting';
    $update = $conn->prepare("UPDATE queue_entries SET status = ?, workflow_status = ?, table_number = NULL, counter_number = NULL, paid_at = NULL WHERE id = ?");
    $update->bind_param('ssi', $newOldStatus, $newStatus, $queue_id);
} else {
    goBack($counter_number);
}

$update->execute();
goBack($counter_number);
?>
