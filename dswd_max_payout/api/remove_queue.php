<?php
include('../auth/check.php');
include('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../staff/dashboard.php");
    exit;
}

$queue_id = isset($_POST['queue_id']) ? intval($_POST['queue_id']) : 0;

if ($queue_id <= 0) {
    header("Location: ../staff/dashboard.php");
    exit;
}

/* only allow removing waiting queues */
$stmt = $conn->prepare("
    UPDATE queue_entries
    SET status = 'cancelled'
    WHERE id = ? AND status = 'waiting'
");
$stmt->bind_param("i", $queue_id);
$stmt->execute();

header("Location: ../staff/dashboard.php");
exit;
?>
