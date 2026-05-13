<?php
header('Content-Type: application/json');
include('../config/db.php');

date_default_timezone_set('Asia/Manila');

function getCalledQueues($conn, $workflow_status) {
    $items = [];

    $query = $conn->prepare("SELECT queue_number, counter_number, called_at FROM queue_entries WHERE DATE(transaction_date) = CURDATE() AND workflow_status = ? ORDER BY counter_number ASC, called_at ASC, id ASC");
    $query->bind_param('s', $workflow_status);
    $query->execute();
    $result = $query->get_result();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'queue_number' => $row['queue_number'],
                'counter_number' => intval($row['counter_number']),
                'called_at' => $row['called_at']
            ];
        }
    }

    return $items;
}

$response = [
    'success' => true,
    'assessment' => getCalledQueues($conn, 'CALLED_STEP_2'),
    'release' => getCalledQueues($conn, 'CALLED_STEP_3'),
    'updated_at' => date('h:i:s A')
];

echo json_encode($response);
exit;
?>
