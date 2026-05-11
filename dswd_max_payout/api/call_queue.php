<?php
header('Content-Type: application/json');
include('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' =>
 false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

$queue_id = $_POST['queue_id'] ?? null;
$counter_number = $_POST['counter_number'] ?? 1;

if (!$queue_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Queue ID is required'
    ]);
    exit;
}

$today = date('Y-m-d');

$check = $conn->prepare("SELECT id, queue_number FROM queue_entries WHERE id = ? LIMIT 1");
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

$update = $conn->prepare("
    UPDATE queue_entries
    SET status = 'called',
        counter_number = ?,
        called_at = NOW()
    WHERE id = ?
");

$update->bind_param("ii", $counter_number, $queue_id);

if ($update->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Queue called successfully',
        'queue_number' => $row['queue_number']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to call queue'
    ]);
}
?>
