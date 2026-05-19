<?php
include('../auth/check.php');
include('../config/db.php');

date_default_timezone_set('Asia/Manila');

function goBack() {
    header('Location: ../staff/verifier.php');
    exit;
}
function assistanceMap() {
    return [
        'MEDI' => ['Medical Assistance','regular'],
        'FNRL' => ['Funeral Assistance','regular'],
        'EDUC' => ['Educational Assistance','regular'],
        'TRAN' => ['Transportation Assistance','regular'],
        'MTRL' => ['Material Assistance','regular'],
        'FDAS' => ['Food Assistance','regular'],
        'CRAS' => ['Cash Relief Assistance','regular'],
        'PRIO' => ['Priority Assistance','priority']
    ];
}
function nextPrefixedNumber($conn, $prefix, $excludeId = 0) {
    $like = $prefix . '-%';
    $start = strlen($prefix) + 2;
    $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(queue_number, ?) AS UNSIGNED)) AS last_number FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND queue_number LIKE ? AND id != ?");
    $stmt->bind_param('isi', $start, $like, $excludeId);
    $stmt->execute();
    $res = $stmt->get_result();
    $next = 1;
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if ($row['last_number'] !== null) $next = intval($row['last_number']) + 1;
    }
    return $prefix . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
}
function nextBeneficiaryCode($conn, $prefix) {
    $like = $prefix . '-%';
    $start = strlen($prefix) + 2;
    $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(beneficiary_code, ?) AS UNSIGNED)) AS last_number FROM beneficiaries WHERE beneficiary_code LIKE ?");
    $stmt->bind_param('is', $start, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    $next = 1;
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if ($row['last_number'] !== null) $next = intval($row['last_number']) + 1;
    }
    return $prefix . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') goBack();

$beneficiary_id = intval($_POST['beneficiary_id'] ?? 0);
$prefix = strtoupper(trim($_POST['assistance_prefix'] ?? $_POST['new_queue_type'] ?? ''));
$map = assistanceMap();
if ($beneficiary_id <= 0 || !isset($map[$prefix])) goBack();

$getCurrent = $conn->prepare("SELECT id, workflow_status FROM queue_entries WHERE beneficiary_id = ? AND DATE(transaction_date) = CURDATE() AND (workflow_status IS NULL OR workflow_status != 'CANCELLED') ORDER BY id DESC LIMIT 1");
$getCurrent->bind_param('i', $beneficiary_id);
$getCurrent->execute();
$currentResult = $getCurrent->get_result();
if (!$currentResult || $currentResult->num_rows === 0) goBack();
$current = $currentResult->fetch_assoc();
$queue_id = intval($current['id']);
if ($current['workflow_status'] === 'PAID') goBack();

$program_type = $map[$prefix][0];
$new_queue_type = $map[$prefix][1];
$new_queue_number = nextPrefixedNumber($conn, $prefix, $queue_id);
$newCode = nextBeneficiaryCode($conn, $prefix);
$upB = $conn->prepare("UPDATE beneficiaries SET beneficiary_code=?, program_type=? WHERE id=?");
$upB->bind_param('ssi', $newCode, $program_type, $beneficiary_id);
$upB->execute();

$update = $conn->prepare("UPDATE queue_entries SET queue_number = ?, queue_type = ?, status = 'waiting', workflow_status = 'WAITING_STEP_2', table_number = NULL, counter_number = NULL, called_at = NULL, assessed_at = NULL, paid_at = NULL WHERE id = ?");
$update->bind_param('ssi', $new_queue_number, $new_queue_type, $queue_id);
$update->execute();

goBack();
?>
