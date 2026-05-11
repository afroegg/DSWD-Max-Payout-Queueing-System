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

/* Check if beneficiary already has a queue today */
$check = $conn->prepare("
    SELECT id, queue_number, workflow_status
    FROM queue_entries
    WHERE beneficiary_id = ?
      AND transaction_date = ?
      AND workflow_status != 'CANCELLED'
    ORDER BY id DESC
    LIMIT 1
");
$check->bind_param("is", $beneficiary_id, $today);
$check->execute();
$checkResult = $check->get_result();

if ($checkResult && $checkResult->num_rows > 0) {
    $existing = $checkResult->fetch_assoc();

    echo "<script>
        alert('This beneficiary already has a queue today. Queue No: " . $existing['queue_number'] . "');
        window.location.href = '../staff/verifier.php';
    </script>";
    exit;
}

/* Generate next PRIO queue number */
$getLast = $conn->prepare("
    SELECT queue_number
    FROM queue_entries
    WHERE transaction_date = ?
      AND queue_type = 'priority'
      AND queue_number LIKE 'PRIO-%'
    ORDER BY CAST(SUBSTRING(queue_number, 6) AS UNSIGNED) DESC
    LIMIT 1
");
$getLast->bind_param("s", $today);
$getLast->execute();
$lastResult = $getLast->get_result();

$nextNumber = 1;

if ($lastResult && $lastResult->num_rows > 0) {
    $lastRow = $lastResult->fetch_assoc();
    $lastQueue = $lastRow['queue_number'];
    $lastNum = intval(str_replace('PRIO-', '', $lastQueue));
    $nextNumber = $lastNum + 1;
}

$queue_number = 'PRIO-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

/* Insert queue entry */
$insert = $conn->prepare("
    INSERT INTO queue_entries (
        queue_number,
        queue_type,
        beneficiary_id,
        transaction_date,
        status,
        workflow_status,
        table_number,
        called_at,
        assessed_at,
        paid_at
    )
    VALUES (
        ?,
        'priority',
        ?,
        ?,
        'waiting',
        'WAITING_STEP_2',
        NULL,
        NULL,
        NULL,
        NULL
    )
");

$insert->bind_param("sis", $queue_number, $beneficiary_id, $today);

if ($insert->execute()) {
    echo "<script>
        alert('Priority queue number generated: {$queue_number}');
        window.location.href = '../staff/verifier.php';
    </script>";
    exit;
} else {
    echo "<script>
        alert('Failed to generate priority queue number.');
        window.location.href = '../staff/verifier.php';
    </script>";
    exit;
}
?>
