<?php
include('../auth/check.php');
include('../config/db.php');

date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../staff/verifier.php");
    exit;
}

$beneficiary_id = intval($_POST['beneficiary_id'] ?? 0);
$today = date('Y-m-d');

if ($beneficiary_id <= 0) {
    echo "<script>
        alert('Invalid beneficiary selected.');
        window.location.href = '../staff/verifier.php';
    </script>";
    exit;
}

/* Find current queue today */
$getCurrent = $conn->prepare("
    SELECT id, queue_type, workflow_status
    FROM queue_entries
    WHERE beneficiary_id = ?
      AND transaction_date = ?
      AND workflow_status != 'CANCELLED'
    ORDER BY id DESC
    LIMIT 1
");
$getCurrent->bind_param("is", $beneficiary_id, $today);
$getCurrent->execute();
$currentResult = $getCurrent->get_result();

if (!$currentResult || $currentResult->num_rows === 0) {
    echo "<script>
        alert('No existing queue number found for this beneficiary today.');
        window.location.href = '../staff/verifier.php';
    </script>";
    exit;
}

$current = $currentResult->fetch_assoc();
$queue_id = intval($current['id']);
$queue_type = $current['queue_type'];
$workflow_status = $current['workflow_status'];

if ($workflow_status === 'PAID') {
    echo "<script>
        alert('This queue is already paid and cannot be regenerated.');
        window.location.href = '../staff/verifier.php';
    </script>";
    exit;
}

if ($queue_type !== 'priority') {
    $queue_type = 'regular';
}

/* Generate new queue number using same queue type */
if ($queue_type === 'priority') {
    $prefix = 'PRIO-';
    $substringStart = 6;
} else {
    $prefix = 'PAL-';
    $substringStart = 5;
}

$getLast = $conn->prepare("
    SELECT queue_number
    FROM queue_entries
    WHERE transaction_date = ?
      AND queue_type = ?
      AND queue_number LIKE CONCAT(?, '%')
      AND id != ?
    ORDER BY CAST(SUBSTRING(queue_number, ?) AS UNSIGNED) DESC
    LIMIT 1
");
$getLast->bind_param("sssii", $today, $queue_type, $prefix, $queue_id, $substringStart);
$getLast->execute();
$lastResult = $getLast->get_result();

$nextNumber = 1;

if ($lastResult && $lastResult->num_rows > 0) {
    $lastRow = $lastResult->fetch_assoc();
    $lastQueue = $lastRow['queue_number'];
    $lastNum = intval(str_replace($prefix, '', $lastQueue));
    $nextNumber = $lastNum + 1;
}

$new_queue_number = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

/* Update existing queue entry */
$update = $conn->prepare("
    UPDATE queue_entries
    SET
        queue_number = ?,
        status = 'waiting',
        workflow_status = 'WAITING_STEP_2',
        table_number = NULL,
        called_at = NULL,
        assessed_at = NULL,
        paid_at = NULL
    WHERE id = ?
");

$update->bind_param("si", $new_queue_number, $queue_id);

if ($update->execute()) {
    echo "<script>
        alert('Queue number regenerated: {$new_queue_number}');
        window.location.href = '../staff/verifier.php';
    </script>";
    exit;
} else {
    echo "<script>
        alert('Failed to regenerate queue number.');
        window.location.href = '../staff/verifier.php';
    </script>";
    exit;
}
?>
