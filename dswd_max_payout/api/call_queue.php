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

function nextCounter($conn, $min, $max) {
    $used = [];
    $stmt = $conn->prepare("SELECT counter_number FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND counter_number BETWEEN ? AND ? AND workflow_status IN ('CALLED_STEP_2','CALLED_STEP_3') ORDER BY called_at DESC");
    $stmt->bind_param('ii', $min, $max);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $used[intval($r['counter_number'])] = true;
    for ($i=$min; $i<=$max; $i++) if (!isset($used[$i])) return $i;

    $stmt = $conn->prepare("SELECT counter_number FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND counter_number BETWEEN ? AND ? AND workflow_status IN ('CALLED_STEP_2','CALLED_STEP_3') GROUP BY counter_number ORDER BY MAX(called_at) ASC LIMIT 1");
    $stmt->bind_param('ii', $min, $max);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) return intval($res->fetch_assoc()['counter_number']);
    return $min;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') safeRedirect();
$queue_id = intval($_POST['queue_id'] ?? 0);
if ($queue_id <= 0) safeRedirect();

$check = $conn->prepare("SELECT id, workflow_status FROM queue_entries WHERE id = ? AND DATE(transaction_date) = CURDATE() LIMIT 1");
$check->bind_param('i', $queue_id);
$check->execute();
$result = $check->get_result();
if (!$result || $result->num_rows === 0) safeRedirect();

$currentStatus = $result->fetch_assoc()['workflow_status'];
if ($currentStatus === 'WAITING_STEP_2') {
    $newStatus = 'CALLED_STEP_2';
    $counter_number = nextCounter($conn, 1, 5);
} elseif ($currentStatus === 'WAITING_STEP_3') {
    $newStatus = 'CALLED_STEP_3';
    $counter_number = nextCounter($conn, 6, 10);
} else {
    safeRedirect();
}

$update = $conn->prepare("UPDATE queue_entries SET status = 'serving', workflow_status = ?, counter_number = ?, table_number = ?, called_at = NOW() WHERE id = ?");
$update->bind_param('siii', $newStatus, $counter_number, $counter_number, $queue_id);
$update->execute();

safeRedirect();
?>
