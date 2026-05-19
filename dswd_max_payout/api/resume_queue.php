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
    header('Location: ../staff/assessment_screen.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') safeRedirect();
$queue_id = intval($_POST['queue_id'] ?? 0);
$resume_to = $_POST['resume_to'] ?? 'WAITING_STEP_2';

if ($queue_id <= 0) safeRedirect();
if (!in_array($resume_to, ['WAITING_STEP_2', 'WAITING_STEP_3'])) $resume_to = 'WAITING_STEP_2';

$stmt = $conn->prepare("UPDATE queue_entries SET status='waiting', workflow_status=?, counter_number=NULL, table_number=NULL WHERE id=? AND DATE(transaction_date)=CURDATE() AND workflow_status='HELD'");
$stmt->bind_param('si', $resume_to, $queue_id);
$stmt->execute();

safeRedirect();
?>
