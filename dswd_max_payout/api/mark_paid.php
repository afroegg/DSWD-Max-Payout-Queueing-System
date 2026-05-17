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

$check = $conn->prepare("SELECT id, beneficiary_id, workflow_status FROM queue_entries WHERE id = ? AND DATE(transaction_date) = CURDATE() LIMIT 1");
$check->bind_param('i', $queue_id);
$check->execute();
$result = $check->get_result();
if (!$result || $result->num_rows === 0) goBack($counter_number);
$row = $result->fetch_assoc();

if ($row['workflow_status'] !== 'CALLED_STEP_3') goBack($counter_number);

$formCheck = $conn->prepare("SELECT id FROM eligibility_forms WHERE queue_entry_id = ? AND beneficiary_id = ? AND eligibility_status = 'Eligible' AND form_locked = 1 AND approved_cash_amount > 0 LIMIT 1");
$formCheck->bind_param('ii', $queue_id, $row['beneficiary_id']);
$formCheck->execute();
$formResult = $formCheck->get_result();
if (!$formResult || $formResult->num_rows === 0) goBack($counter_number);

$update = $conn->prepare("UPDATE queue_entries SET status = 'paid', workflow_status = 'PAID', paid_at = NOW(), released_at = NOW(), table_number = NULL, counter_number = NULL WHERE id = ?");
$update->bind_param('i', $queue_id);
$update->execute();

goBack($counter_number);
?>
