<?php
include('../auth/check.php');
include('../config/db.php');

date_default_timezone_set('Asia/Manila');

function safeRedirect() {
    $redirect = $_POST['redirect'] ?? '';
    if ($redirect !== '' && strpos($redirect, '../staff/') === 0) {
        header('Location: ' . $redirect);
        exit;
    }
    if (!empty($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
    header('Location: ../staff/verifier.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') safeRedirect();

$queue_id = intval($_POST['queue_id'] ?? 0);
$counter_number = intval($_POST['counter_number'] ?? 0);
if ($queue_id <= 0) safeRedirect();

$check = $conn->prepare("SELECT id, workflow_status FROM queue_entries WHERE id = ? AND DATE(transaction_date) = CURDATE() LIMIT 1");
$check->bind_param('i', $queue_id);
$check->execute();
$result = $check->get_result();
if (!$result || $result->num_rows === 0) safeRedirect();

$currentStatus = $result->fetch_assoc()['workflow_status'];

if ($currentStatus === 'WAITING_STEP_2') {
    $newStatus = 'CALLED_STEP_2';
    if ($counter_number < 1 || $counter_number > 5) safeRedirect();
} elseif ($currentStatus === 'WAITING_STEP_3') {
    $newStatus = 'CALLED_STEP_3';
    if ($counter_number < 6 || $counter_number > 10) safeRedirect();
} else {
    safeRedirect();
}

$update = $conn->prepare("UPDATE queue_entries SET status = 'serving', workflow_status = ?, counter_number = ?, table_number = ?, called_at = NOW() WHERE id = ?");
$update->bind_param('siii', $newStatus, $counter_number, $counter_number, $queue_id);
$update->execute();

safeRedirect();
?>
