<?php
include('../auth/check.php');
include('../config/db.php');

date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../staff/verifier.php");
    exit;
}

$beneficiary_id = intval($_POST['beneficiary_id'] ?? 0);

if ($beneficiary_id <= 0) {
    echo "<script>
        alert('Invalid beneficiary selected.');
        window.location.href = '../staff/verifier.php';
    </script>";
    exit;
}

/*
    Check if beneficiary already has an active queue today.
    Uses MySQL CURDATE() to avoid Render/PHP timezone mismatch.
*/
$check = $conn->prepare("
    SELECT id, queue_number, workflow_status
    FROM queue_entries
    WHERE beneficiary_id = ?
      AND DATE(transaction_date) = CURDATE()
      AND (
            workflow_status IS NULL
            OR workflow_status != 'CANCELLED'
          )
    ORDER BY id DESC
    LIMIT 1
");

$check->bind_param("i", $beneficiary_id);
$check->execute();
$checkResult = $check->get_result();

if ($checkResult && $checkResult->num_rows > 0) {
    $existing = $checkResult->fetch_assoc();
    $existingQueue = addslashes($existing['queue_number']);

    echo "<script>
        alert('This beneficiary already has a queue today. Queue No: {$existingQueue}');
        window.location.href = '../staff/verifier.php';
    </script>";
    exit;
}

/*
    Generate next PRIO queue number today.
    Example: PRIO-0001, PRIO-0002, PRIO-0003...
*/
$getLast = $conn->prepare("
    SELECT 
        MAX(CAST(SUBSTRING(queue_number, 6) AS UNSIGNED)) AS last_number
    FROM queue_entries
    WHERE DATE(transaction_date) = CURDATE()
      AND queue_type = 'priority'
      AND queue_number LIKE 'PRIO-%'
");

$getLast->execute();
$lastResult = $getLast->get_result();

$nextNumber = 1;

if ($lastResult && $lastResult->num_rows > 0) {
    $lastRow = $lastResult->fetch_assoc();

    if ($lastRow['last_number'] !== null) {
        $nextNumber = intval($lastRow['last_number']) + 1;
    }
}

$queue_number = 'PRIO-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

/*
    Insert priority queue.
    transaction_date uses CURDATE() from MySQL.
*/
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
        CURDATE(),
        'waiting',
        'WAITING_STEP_2',
        NULL,
        NULL,
        NULL,
        NULL
    )
");

$insert->bind_param("si", $queue_number, $beneficiary_id);

if ($insert->execute()) {
    echo "<script>
        alert('Priority queue number generated: {$queue_number}');
        window.location.href = '../staff/verifier.php';
    </script>";
    exit;
} else {
    $error = addslashes($conn->error);

    echo "<script>
        alert('Failed to generate priority queue number. Error: {$error}');
        window.location.href = '../staff/verifier.php';
    </script>";
    exit;
}
?>
