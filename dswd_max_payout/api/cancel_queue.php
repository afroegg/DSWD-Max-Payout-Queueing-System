<?php
include('../auth/check.php');
include('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../staff/counter.php');
    exit;
}

$queue_id = intval($_POST['queue_id'] ?? 0);
$redirect = trim($_POST['redirect'] ?? '../staff/counter.php');

if ($queue_id <= 0) {
    echo "<script>alert('Invalid queue selected.'); window.location.href='{$redirect}';</script>";
    exit;
}

$stmt = $conn->prepare("
    UPDATE queue_entries
    SET
        status = 'cancelled',
        workflow_status = 'CANCELLED',
        table_number = NULL,
        counter_number = NULL
    WHERE id = ?
");
$stmt->bind_param('i', $queue_id);

if ($stmt->execute()) {
    echo "<script>alert('Queue has been cancelled. Beneficiary record was not deleted.'); window.location.href='{$redirect}';</script>";
    exit;
}

$error = addslashes($conn->error);
echo "<script>alert('Failed to cancel queue. Error: {$error}'); window.location.href='{$redirect}';</script>";
exit;
?>
