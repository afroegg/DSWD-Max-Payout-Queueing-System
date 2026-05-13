<?php
include('../auth/check.php');
include('../config/db.php');

date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../staff/verifier.php");
    exit;
}

$beneficiary_id = intval($_POST['beneficiary_id'] ?? 0);

if ($beneficiary_id <= 0) {
    echo "<script>alert('Invalid beneficiary selected.'); window.location.href = '../staff/verifier.php';</script>";
    exit;
}

$check = $conn->prepare("SELECT id, queue_number FROM queue_entries WHERE beneficiary_id = ? AND DATE(transaction_date) = CURDATE() AND (workflow_status IS NULL OR workflow_status != 'CANCELLED') ORDER BY id DESC LIMIT 1");
$check->bind_param("i", $beneficiary_id);
$check->execute();
$checkResult = $check->get_result();

if ($checkResult && $checkResult->num_rows > 0) {
    $existing = $checkResult->fetch_assoc();
    $existingQueue = addslashes($existing['queue_number']);
    echo "<script>alert('This beneficiary already has a queue today. Queue No: {$existingQueue}'); window.location.href = '../staff/verifier.php';</script>";
    exit;
}

$getLast = $conn->prepare("SELECT MAX(CAST(SUBSTRING(queue_number, 5) AS UNSIGNED)) AS last_number FROM queue_entries WHERE DATE(transaction_date) = CURDATE() AND queue_type = 'regular' AND queue_number LIKE 'PAL-%'");
$getLast->execute();
$lastResult = $getLast->get_result();

$nextNumber = 1;
if ($lastResult && $lastResult->num_rows > 0) {
    $lastRow = $lastResult->fetch_assoc();
    if ($lastRow['last_number'] !== null) {
        $nextNumber = intval($lastRow['last_number']) + 1;
    }
}

$queue_number = 'PAL-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

$insert = $conn->prepare("INSERT INTO queue_entries (queue_number, queue_type, beneficiary_id, transaction_date, status, workflow_status, table_number, called_at, assessed_at, paid_at) VALUES (?, 'regular', ?, CURDATE(), 'waiting', 'WAITING_STEP_2', NULL, NULL, NULL, NULL)");
$insert->bind_param("si", $queue_number, $beneficiary_id);

if ($insert->execute()) {
    echo "<script>alert('Regular queue number generated: {$queue_number}'); window.location.href = '../staff/verifier.php';</script>";
    exit;
}

$error = addslashes($conn->error);
echo "<script>alert('Failed to generate regular queue number. Error: {$error}'); window.location.href = '../staff/verifier.php';</script>";
exit;
?>
