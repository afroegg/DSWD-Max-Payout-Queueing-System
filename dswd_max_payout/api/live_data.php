<?php
include('../config/db.php');

header('Content-Type: application/json');

$today = date('Y-m-d');

/* COUNTS */
$waiting = $conn->query("SELECT COUNT(*) as total FROM queue_entries WHERE transaction_date='$today' AND status='waiting'")->fetch_assoc()['total'];
$serving = $conn->query("SELECT COUNT(*) as total FROM queue_entries WHERE transaction_date='$today' AND status='serving'")->fetch_assoc()['total'];
$released = $conn->query("SELECT COUNT(*) as total FROM queue_entries WHERE transaction_date='$today' AND status='released'")->fetch_assoc()['total'];

/* QUEUE */
$queue = [];
$q = $conn->query("
    SELECT q.queue_number, b.first_name, b.last_name, b.program_type, q.status
    FROM queue_entries q
    JOIN beneficiaries b ON q.beneficiary_id = b.id
    WHERE q.transaction_date='$today'
    ORDER BY q.id ASC
");

while ($row = $q->fetch_assoc()) {
    $queue[] = $row;
}

/* HISTORY */
$history = [];
$h = $conn->query("
    SELECT q.queue_number, b.first_name, b.last_name, p.amount, p.payout_batch
    FROM payouts p
    JOIN beneficiaries b ON p.beneficiary_id = b.id
    JOIN queue_entries q ON p.queue_entry_id = q.id
    ORDER BY p.released_at DESC
");

while ($row = $h->fetch_assoc()) {
    $history[] = $row;
}

echo json_encode([
    "waiting" => $waiting,
    "serving" => $serving,
    "released" => $released,
    "queue" => $queue,
    "history" => $history
]);