<?php
include('../config/db.php');

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

$today = date('Y-m-d');

/* COUNTS */
$waiting = 0;
$serving = 0;
$released = 0;
$currentServing = null;
$queue = [];
$history = [];

/* WAITING COUNT */
$waitingStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM queue_entries
    WHERE transaction_date = ? AND status = 'waiting'
");
$waitingStmt->bind_param("s", $today);
$waitingStmt->execute();
$waitingResult = $waitingStmt->get_result();
if ($waitingRow = $waitingResult->fetch_assoc()) {
    $waiting = (int)$waitingRow['total'];
}

/* SERVING COUNT */
$servingStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM queue_entries
    WHERE transaction_date = ? AND status = 'serving'
");
$servingStmt->bind_param("s", $today);
$servingStmt->execute();
$servingResult = $servingStmt->get_result();
if ($servingRow = $servingResult->fetch_assoc()) {
    $serving = (int)$servingRow['total'];
}

/* RELEASED COUNT */
$releasedStmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM payouts
    WHERE payout_date = ? AND status = 'released'
");
$releasedStmt->bind_param("s", $today);
$releasedStmt->execute();
$releasedResult = $releasedStmt->get_result();
if ($releasedRow = $releasedResult->fetch_assoc()) {
    $released = (int)$releasedRow['total'];
}

/* CURRENT SERVING QUEUE NUMBER */
$currentServingStmt = $conn->prepare("
    SELECT queue_number
    FROM queue_entries
    WHERE transaction_date = ? AND status = 'serving'
    ORDER BY id ASC
    LIMIT 1
");
$currentServingStmt->bind_param("s", $today);
$currentServingStmt->execute();
$currentServingResult = $currentServingStmt->get_result();
if ($currentServingResult && $currentServingResult->num_rows > 0) {
    $currentServing = $currentServingResult->fetch_assoc()['queue_number'];
}

/* QUEUE MONITORING: WAITING ONLY */
$queueStmt = $conn->prepare("
    SELECT
        q.id,
        q.queue_number,
        b.first_name,
        b.last_name,
        b.program_type,
        q.status
    FROM queue_entries q
    INNER JOIN beneficiaries b ON q.beneficiary_id = b.id
    WHERE q.transaction_date = ?
      AND q.status = 'waiting'
    ORDER BY q.id ASC
");
$queueStmt->bind_param("s", $today);
$queueStmt->execute();
$queueResult = $queueStmt->get_result();

while ($row = $queueResult->fetch_assoc()) {
    $queue[] = [
        "id" => (int)$row["id"],
        "queue_number" => $row["queue_number"],
        "first_name" => $row["first_name"],
        "last_name" => $row["last_name"],
        "program_type" => $row["program_type"],
        "status" => $row["status"]
    ];
}

/* PAYOUT HISTORY */
$historyStmt = $conn->prepare("
    SELECT
        q.queue_number,
        b.first_name,
        b.last_name,
        p.amount,
        p.payout_batch,
        p.released_at
    FROM payouts p
    INNER JOIN queue_entries q ON p.queue_entry_id = q.id
    INNER JOIN beneficiaries b ON q.beneficiary_id = b.id
    WHERE p.status = 'released'
    ORDER BY p.released_at DESC, p.id DESC
");
$historyStmt->execute();
$historyResult = $historyStmt->get_result();

while ($row = $historyResult->fetch_assoc()) {
    $history[] = [
        "queue_number" => $row["queue_number"],
        "first_name" => $row["first_name"],
        "last_name" => $row["last_name"],
        "amount" => (float)$row["amount"],
        "payout_batch" => $row["payout_batch"],
        "released_at" => $row["released_at"]
    ];
}

echo json_encode([
    "waiting" => $waiting,
    "serving" => $serving,
    "released" => $released,
    "currentServing" => $currentServing,
    "queue" => $queue,
    "history" => $history
]);
?>
