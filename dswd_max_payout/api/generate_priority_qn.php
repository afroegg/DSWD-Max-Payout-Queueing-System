<?php
include('../auth/check.php');
include('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../staff/verifier.php');
    exit;
}

$beneficiary_id = intval($_POST['beneficiary_id'] ?? 0);
if ($beneficiary_id <= 0) {
    header('Location: ../staff/verifier.php');
    exit;
}

$eligibility = $conn->prepare('SELECT eligibility_status FROM eligibility_forms WHERE beneficiary_id = ? LIMIT 1');
$eligibility->bind_param('i', $beneficiary_id);
$eligibility->execute();
$eligibilityResult = $eligibility->get_result();

if (!$eligibilityResult || $eligibilityResult->num_rows === 0) {
    header('Location: ../staff/eligibility_form.php?beneficiary_id=' . $beneficiary_id);
    exit;
}

$eligibilityRow = $eligibilityResult->fetch_assoc();
if ($eligibilityRow['eligibility_status'] !== 'Eligible') {
    header('Location: ../staff/eligibility_form.php?beneficiary_id=' . $beneficiary_id);
    exit;
}

$check = $conn->prepare("SELECT id, queue_number FROM queue_entries WHERE beneficiary_id = ? AND DATE(transaction_date) = CURDATE() AND (workflow_status IS NULL OR workflow_status != 'CANCELLED') ORDER BY id DESC LIMIT 1");
$check->bind_param('i', $beneficiary_id);
$check->execute();
$checkResult = $check->get_result();

if ($checkResult && $checkResult->num_rows > 0) {
    header('Location: ../staff/verifier.php');
    exit;
}

$getLast = $conn->prepare("SELECT MAX(CAST(SUBSTRING(queue_number, 6) AS UNSIGNED)) AS last_number FROM queue_entries WHERE DATE(transaction_date) = CURDATE() AND queue_type = 'priority' AND queue_number LIKE 'PRIO-%'");
$getLast->execute();
$lastResult = $getLast->get_result();

$nextNumber = 1;
if ($lastResult && $lastResult->num_rows > 0) {
    $lastRow = $lastResult->fetch_assoc();
    if ($lastRow['last_number'] !== null) {
        $nextNumber = intval($lastRow['last_number']) + 1;
    }
}

$queue_number = 'PRIO-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
$insert = $conn->prepare("INSERT INTO queue_entries (queue_number, queue_type, beneficiary_id, transaction_date, status, workflow_status, table_number, called_at, assessed_at, paid_at) VALUES (?, 'priority', ?, CURDATE(), 'waiting', 'WAITING_STEP_2', NULL, NULL, NULL, NULL)");
$insert->bind_param('si', $queue_number, $beneficiary_id);
$insert->execute();

header('Location: ../staff/verifier.php');
exit;
?>
