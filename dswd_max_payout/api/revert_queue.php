<?php
include('../auth/check.php');
include('../config/db.php');

date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../staff/counter.php");
    exit;
}

$queue_id = intval($_POST['queue_id'] ?? 0);
$counter_number = intval($_POST['counter_number'] ?? 1);

if ($counter_number <= 0) {
    $counter_number = 1;
}

function goBack($counter_number, $message) {
    $message = addslashes($message);
    echo "<script>
        alert('{$message}');
        window.location.href = '../staff/counter.php?counter={$counter_number}';
    </script>";
    exit;
}

if ($queue_id <= 0) {
    goBack($counter_number, 'Queue ID is required.');
}

$check = $conn->prepare("
    SELECT id, queue_number, workflow_status
    FROM queue_entries
    WHERE id = ?
      AND DATE(transaction_date) = CURDATE()
    LIMIT 1
");
$check->bind_param("i", $queue_id);
$check->execute();
$result = $check->get_result();

if (!$result || $result->num_rows === 0) {
    goBack($counter_number, 'Queue not found for today.');
}

$row = $result->fetch_assoc();
$currentStatus = $row['workflow_status'];

$tableNumberSql = "table_number = NULL, counter_number = NULL";
$newStatus = '';
$newOldStatus = 'waiting';

if ($currentStatus === 'CALLED_STEP_2') {
    $newStatus = 'WAITING_STEP_2';
    $newOldStatus = 'waiting';
} elseif ($currentStatus === 'WAITING_STEP_3') {
    $newStatus = 'CALLED_STEP_2';
    $newOldStatus = 'serving';
    $tableNumberSql = "table_number = ?, counter_number = ?";
} elseif ($currentStatus === 'CALLED_STEP_3') {
    $newStatus = 'WAITING_STEP_3';
    $newOldStatus = 'waiting';
} elseif ($currentStatus === 'PAID') {
    goBack($counter_number, 'Paid queues cannot be reverted.');
} else {
    goBack($counter_number, 'This queue cannot be reverted from its current status.');
}

if ($currentStatus === 'WAITING_STEP_3') {
    $update = $conn->prepare("
        UPDATE queue_entries
        SET
            status = ?,
            workflow_status = ?,
            table_number = ?,
            counter_number = ?,
            assessed_at = NULL,
            paid_at = NULL
        WHERE id = ?
    ");
    $update->bind_param("ssiii", $newOldStatus, $newStatus, $counter_number, $counter_number, $queue_id);
} else {
    $update = $conn->prepare("
        UPDATE queue_entries
        SET
            status = ?,
            workflow_status = ?,
            table_number = NULL,
            counter_number = NULL,
            paid_at = NULL
        WHERE id = ?
    ");
    $update->bind_param("ssi", $newOldStatus, $newStatus, $queue_id);
}

if ($update->execute()) {
    goBack($counter_number, 'Queue reverted successfully: ' . $row['queue_number']);
} else {
    goBack($counter_number, 'Failed to revert queue. Error: ' . $conn->error);
}
?>