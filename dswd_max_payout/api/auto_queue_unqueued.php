<?php
include('../auth/check.php');
include('../config/db.php');

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');
set_time_limit(180);

function respond($success, $message, $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

$palResult = $conn->query("SELECT MAX(CAST(SUBSTRING(queue_number, 5) AS UNSIGNED)) AS last_number FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND queue_type='regular' AND queue_number LIKE 'PAL-%'");
$prioResult = $conn->query("SELECT MAX(CAST(SUBSTRING(queue_number, 6) AS UNSIGNED)) AS last_number FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND queue_type='priority' AND queue_number LIKE 'PRIO-%'");

$palNext = 1;
$prioNext = 1;

if ($palResult && $palResult->num_rows > 0) {
    $row = $palResult->fetch_assoc();
    if ($row['last_number'] !== null) $palNext = intval($row['last_number']) + 1;
}

if ($prioResult && $prioResult->num_rows > 0) {
    $row = $prioResult->fetch_assoc();
    if ($row['last_number'] !== null) $prioNext = intval($row['last_number']) + 1;
}

$sql = "
    SELECT
        b.id,
        b.age,
        IFNULL(b.sms_opt_in, 0) AS is_pwd,
        IFNULL(b.is_pregnant, 0) AS is_pregnant
    FROM beneficiaries b
    WHERE NOT EXISTS (
        SELECT 1
        FROM queue_entries q
        WHERE q.beneficiary_id = b.id
          AND DATE(q.transaction_date) = CURDATE()
          AND (q.workflow_status IS NULL OR q.workflow_status != 'CANCELLED')
    )
    ORDER BY
        CASE
            WHEN IFNULL(b.sms_opt_in, 0)=1 THEN 0
            WHEN IFNULL(b.is_pregnant, 0)=1 THEN 1
            WHEN b.age >= 60 THEN 2
            ELSE 3
        END ASC,
        b.id ASC
";

$result = $conn->query($sql);

if (!$result) {
    respond(false, 'Auto queue failed: ' . $conn->error);
}

$insert = $conn->prepare("INSERT INTO queue_entries (queue_number, queue_type, beneficiary_id, transaction_date, status, workflow_status, table_number, called_at, assessed_at, paid_at) VALUES (?, ?, ?, CURDATE(), 'waiting', 'WAITING_STEP_2', NULL, NULL, NULL, NULL)");

$created = 0;
$regularCreated = 0;
$priorityCreated = 0;

$conn->begin_transaction();

try {
    while ($b = $result->fetch_assoc()) {
        $beneficiaryId = intval($b['id']);
        $age = intval($b['age']);
        $isPwd = intval($b['is_pwd']);
        $isPregnant = intval($b['is_pregnant']);

        if ($isPwd === 1 || $isPregnant === 1 || $age >= 60) {
            $queueType = 'priority';
            $queueNumber = 'PRIO-' . str_pad($prioNext, 4, '0', STR_PAD_LEFT);
            $prioNext++;
            $priorityCreated++;
        } else {
            $queueType = 'regular';
            $queueNumber = 'PAL-' . str_pad($palNext, 4, '0', STR_PAD_LEFT);
            $palNext++;
            $regularCreated++;
        }

        $insert->bind_param('ssi', $queueNumber, $queueType, $beneficiaryId);
        $insert->execute();
        $created++;
    }

    $conn->commit();
    respond(true, 'Auto queue assignment finished.', [
        'created' => $created,
        'regular_created' => $regularCreated,
        'priority_created' => $priorityCreated
    ]);
} catch (Exception $e) {
    $conn->rollback();
    respond(false, 'Auto queue failed: ' . $e->getMessage());
}
?>
