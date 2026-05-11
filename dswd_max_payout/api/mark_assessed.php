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

$update = $conn->prepare("
    UPDATE queue_entries
    SET status = 'assessed'
    WHERE id = ?
");

$update->bind_param("i", $queue_id);

if ($update->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Queue marked as assessed'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to mark assessed'
    ]);
}
?>
