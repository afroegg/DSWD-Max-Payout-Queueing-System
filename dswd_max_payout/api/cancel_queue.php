<?php
include('../auth/check.php');
include('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../staff/counter.php');
    exit;
}

$queue_id = intval($_POST['queue_id'] ?? 0);
$redirect = trim($_POST['redirect'] ?? '../staff/counter.php');

if ($queue_id > 0) {
    $stmt = $conn->prepare("UPDATE queue_entries SET status = 'cancelled', workflow_status = 'CANCELLED', table_number = NULL, counter_number = NULL WHERE id = ?");
    $stmt->bind_param('i', $queue_id);
    $stmt->execute();
}

header('Location: ' . $redirect);
exit;
?>
