<?php
include('../config/db.php');

date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit;
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$contact_number = trim($_POST['contact_number'] ?? '');
$program_type = trim($_POST['program_type'] ?? '');
$sms_opt_in = isset($_POST['sms_opt_in']) ? 1 : 0;

if ($first_name === '' || $last_name === '' || $contact_number === '' || $program_type === '') {
    echo "<script>
        alert('Please complete all required fields.');
        window.history.back();
    </script>";
    exit;
}

/* DUPLICATE CHECK */
$dup = $conn->prepare("
    SELECT q.id, q.queue_number, q.status
    FROM queue_entries q
    INNER JOIN beneficiaries b ON q.beneficiary_id = b.id
    WHERE q.transaction_date = ?
      AND q.status IN ('waiting', 'serving')
      AND LOWER(TRIM(b.first_name)) = LOWER(TRIM(?))
      AND LOWER(TRIM(b.last_name)) = LOWER(TRIM(?))
      AND TRIM(b.contact_number) = TRIM(?)
    LIMIT 1
");
$dup->bind_param("ssss", $today, $first_name, $last_name, $contact_number);
$dup->execute();
$dup_result = $dup->get_result();

if ($dup_result->num_rows > 0) {
    $existing = $dup_result->fetch_assoc();
    $existingQueue = $existing['queue_number'];
    $existingStatus = strtoupper($existing['status']);

    echo "<script>
        alert('You already have an active queue today.\\nQueue No: {$existingQueue}\\nStatus: {$existingStatus}');
        window.location.href = 'register.php';
    </script>";
    exit;
}

/* SAVE BENEFICIARY */
$beneficiary = $conn->prepare("
    INSERT INTO beneficiaries (first_name, last_name, contact_number, program_type, sms_opt_in)
    VALUES (?, ?, ?, ?, ?)
");
$beneficiary->bind_param("ssssi", $first_name, $last_name, $contact_number, $program_type, $sms_opt_in);
$beneficiary->execute();
$beneficiary_id = $beneficiary->insert_id;

/* GENERATE QUEUE NUMBER */
$countStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM queue_entries
    WHERE transaction_date = ?
");
$countStmt->bind_param("s", $today);
$countStmt->execute();
$countResult = $countStmt->get_result()->fetch_assoc();
$nextNumber = str_pad($countResult['total'] + 1, 5, "0", STR_PAD_LEFT);
$queue_number = "MPO-" . $nextNumber;

/* INSERT QUEUE ENTRY */
$queue = $conn->prepare("
    INSERT INTO queue_entries (queue_number, beneficiary_id, transaction_date, status)
    VALUES (?, ?, ?, 'waiting')
");
$queue->bind_param("sis", $queue_number, $beneficiary_id, $today);
$queue->execute();

echo "<script>
    alert('Registration successful. Your queue number is {$queue_number}.');
    window.location.href = 'success.php?queue_number=" . urlencode($queue_number) . "';
</script>";
exit;
?>
