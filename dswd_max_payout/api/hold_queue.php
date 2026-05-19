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
if ($queue_id <= 0) safeRedirect();

$stmt = $conn->prepare("UPDATE queue_entries SET status='held', workflow_status='HELD', counter_number=NULL, table_number=NULL WHERE id=? AND DATE(transaction_date)=CURDATE() AND workflow_status IN ('WAITING_STEP_2','CALLED_STEP_2','WAITING_STEP_3','CALLED_STEP_3')");
$stmt->bind_param('i', $queue_id);
$stmt->execute();

safeRedirect();
?>
