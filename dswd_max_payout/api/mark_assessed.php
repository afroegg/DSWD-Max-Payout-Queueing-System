<?php
include('../auth/check.php');
include('../config/db.php');

date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../staff/counter.php");
    exit;
}

$queue_id = intval($_POST['queue_id'] ?? 0);
$counter_number = intval($_POST['counter_number'] ?? 1);
if ($counter_number <= 0) $counter_number = 1;

function goBack($counter_number, $message) {
    $message = addslashes($message);
    echo "<script>alert('{$message}'); window.location.href = '../staff/counter.php?counter={$counter_number}';</script>";
    exit;
}

if ($queue_id <= 0) goBack($counter_number, 'Queue ID is required.');

$check = $conn->prepare("SELECT id, queue_number, beneficiary_id, workflow_status FROM queue_entries WHERE id = ? AND DATE(transaction_date) = CURDATE() LIMIT 1");
$check->bind_param("i", $queue_id);
$check->execute();
$result = $check->get_result();
if (!$result || $result->num_rows === 0) goBack($counter_number, 'Queue not found for today.');
$row = $result->fetch_assoc();

if ($row['workflow_status'] !== 'CALLED_STEP_2') goBack($counter_number, 'Only called Step 2 queues can be marked as assessed.');

$formCheck = $conn->prepare("SELECT id FROM eligibility_forms WHERE queue_entry_id = ? AND beneficiary_id = ? AND eligibility_status = 'Eligible' AND form_locked = 1 AND approved_cash_amount > 0 LIMIT 1");
$formCheck->bind_param("ii", $queue_id, $row['beneficiary_id']);
$formCheck->execute();
$formResult = $formCheck->get_result();
if (!$formResult || $formResult->num_rows === 0) {
    goBack($counter_number, 'GIS form must be approved and locked before marking assessed.');
}

$update = $conn->prepare("UPDATE queue_entries SET status = 'waiting', workflow_status = 'WAITING_STEP_3', table_number = NULL, counter_number = NULL, assessed_at = NOW() WHERE id = ?");
$update->bind_param("i", $queue_id);

if ($update->execute()) goBack($counter_number, 'Queue marked as assessed and moved to Step 3: ' . $row['queue_number']);
goBack($counter_number, 'Failed to mark assessed. Error: ' . $conn->error);
?>
