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
        'MEDI' => 'Medical Assistance',
        'FNRL' => 'Funeral Assistance',
        'EDUC' => 'Educational Assistance',
        'TRAN' => 'Transportation Assistance',
        'MTRL' => 'Material Assistance',
        'FDAS' => 'Food Assistance',
        'CRAS' => 'Cash Relief Assistance'
    ];
}
function nextPrefixedNumber($conn, $prefix, $excludeId = 0) {
    $like = $prefix . '-%';
    $start = strlen($prefix) + 2;
    if ($excludeId > 0) {
        $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(queue_number, ?) AS UNSIGNED)) AS last_number FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND queue_number LIKE ? AND id != ?");
        $stmt->bind_param('isi', $start, $like, $excludeId);
    } else {
        $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(queue_number, ?) AS UNSIGNED)) AS last_number FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND queue_number LIKE ?");
        $stmt->bind_param('is', $start, $like);
    }
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

$check = $conn->prepare("SELECT id FROM queue_entries WHERE beneficiary_id = ? AND DATE(transaction_date) = CURDATE() AND (workflow_status IS NULL OR workflow_status != 'CANCELLED') ORDER BY id DESC LIMIT 1");
$check->bind_param('i', $beneficiary_id);
$check->execute();
$checkResult = $check->get_result();
if ($checkResult && $checkResult->num_rows > 0) goBack();

$queue_number = nextPrefixedNumber($conn, $prefix);
$program_type = $map[$prefix];
$getB = $conn->prepare("SELECT beneficiary_code FROM beneficiaries WHERE id=? LIMIT 1");
$getB->bind_param('i', $beneficiary_id);
$getB->execute();
$b = $getB->get_result()->fetch_assoc();
$currentCode = $b['beneficiary_code'] ?? '';
if ($currentCode === '' || preg_match('/^(PAL|MEDI|FNRL|EDUC|TRAN|MTRL|FDAS|CRAS|PRIO)-/', $currentCode)) {
    $newCode = nextBeneficiaryCode($conn, $prefix);
    $up = $conn->prepare("UPDATE beneficiaries SET beneficiary_code=?, program_type=? WHERE id=?");
    $up->bind_param('ssi', $newCode, $program_type, $beneficiary_id);
    $up->execute();
} else {
    $up = $conn->prepare("UPDATE beneficiaries SET program_type=? WHERE id=?");
    $up->bind_param('si', $program_type, $beneficiary_id);
    $up->execute();
}

$insert = $conn->prepare("INSERT INTO queue_entries (queue_number, queue_type, beneficiary_id, transaction_date, status, workflow_status, table_number, counter_number, called_at, assessed_at, paid_at) VALUES (?, 'regular', ?, CURDATE(), 'waiting', 'WAITING_STEP_2', NULL, NULL, NULL, NULL, NULL)");
$insert->bind_param('si', $queue_number, $beneficiary_id);
$insert->execute();

goBack();
?>
