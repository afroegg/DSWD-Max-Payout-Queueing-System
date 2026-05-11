<?php
header('Content-Type: application/json');
include('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

$queue_id = $_POST['queue_id'] ?? null;

if (!$queue_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Queue ID is required'
    ]);
    exit;
}

$check = $conn->prepare("SELECT status FROM queue_entries WHERE id = ? LIMIT 1");
$check->bind_param("i", $queue_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Queue not found'
    ]);
    exit;
}

$row = $result->fetch_assoc();
$currentStatus = $row['status'];

$previousStatus = 'waiting';

if ($currentStatus === 'paid') {
    $previousStatus = 'assessed';
} elseif ($currentStatus === 'assessed') {
    $previousStatus = 'called';
} elseif ($currentStatus === 'called') {
    $previousStatus = 'waiting';
}

$update = $conn->prepare("
    UPDATE queue_entries
    SET status = ?
    WHERE id = ?
");

$update->bind_param("si", $previousStatus, $queue_id);

if ($update->execute()) {
    echo json_encode([
        'success' => true,
'message' => 'Queue reverted successfully',
        'status' => $previousStatus
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to revert queue'
    ]);
}
?>
