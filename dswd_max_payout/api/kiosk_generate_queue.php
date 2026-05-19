<?php
include('../config/db.php');

date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');

function respond($ok, $data = []) {
    echo json_encode(array_merge(['success' => $ok], $data));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, ['message' => 'Invalid request.']);
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) $input = $_POST;

$category = strtolower(trim($input['category'] ?? 'regular'));
$program_type = trim($input['program_type'] ?? 'AICS');
$allowedCategories = ['regular', 'priority', 'pwd', 'senior', 'pregnant', '4ps'];
if (!in_array($category, $allowedCategories, true)) $category = 'regular';
if ($program_type === '') $program_type = 'AICS';

$isPriority = in_array($category, ['priority', 'pwd', 'senior', 'pregnant'], true);
$queueType = $isPriority ? 'priority' : 'regular';
$categoryLabelMap = [
    'regular' => 'Regular',
    'priority' => 'Priority',
    'pwd' => 'PWD / Priority',
    'senior' => 'Senior Citizen / Priority',
    'pregnant' => 'Pregnant / Priority',
    '4ps' => '4Ps Beneficiary'
];
$categoryLabel = $categoryLabelMap[$category] ?? 'Regular';

function getNextBeneficiaryCode($conn) {
    $res = $conn->query("SELECT MAX(CAST(SUBSTRING(beneficiary_code, 5) AS UNSIGNED)) AS last_number FROM beneficiaries WHERE beneficiary_code LIKE 'PAL-%'");
    $n = 1;
    if ($res && $res->num_rows > 0) {
        $r = $res->fetch_assoc();
        if ($r['last_number'] !== null) $n = intval($r['last_number']) + 1;
    }
    return 'PAL-' . str_pad($n, 5, '0', STR_PAD_LEFT);
}

function getNextQueueNumber($conn, $queueType) {
    if ($queueType === 'priority') {
        $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(queue_number, 6) AS UNSIGNED)) AS last_number FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND queue_type='priority' AND queue_number LIKE 'PRIO-%'");
        $prefix = 'PRIO-';
    } else {
        $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(queue_number, 5) AS UNSIGNED)) AS last_number FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND queue_type='regular' AND queue_number LIKE 'PAL-%'");
        $prefix = 'PAL-';
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $next = 1;
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['last_number'] !== null) $next = intval($row['last_number']) + 1;
    }
    return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
}

$beneficiary_code = getNextBeneficiaryCode($conn);
$first_name = $categoryLabel;
$middle_name = '';
$last_name = 'KIOSK';
$ext_name = '';
$contact_number = '';
$birthday_month = 1;
$birthday_day = 1;
$birthday_year = 2000;
$age = ($category === 'senior') ? 60 : 0;
$sex = 'Male';
$lgu = 'MIMAROPA';
$national_id = 'None';
$household_id = '';
$region = 'MIMAROPA';
$province = 'MIMAROPA';
$city_municipality = 'MIMAROPA';
$barangay = 'MIMAROPA';
$sms_opt_in = ($category === 'pwd') ? 1 : 0;
$is_pregnant = ($category === 'pregnant') ? 1 : 0;
if ($is_pregnant) $sex = 'Female';

$conn->begin_transaction();
try {
    $insert = $conn->prepare("INSERT INTO beneficiaries (beneficiary_code, first_name, middle_name, last_name, ext_name, contact_number, birthday_month, birthday_day, birthday_year, age, sex, lgu, national_id, household_id, program_type, region, province, city_municipality, barangay, sms_opt_in, is_pregnant) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insert->bind_param('ssssssiiiisssssssssii', $beneficiary_code, $first_name, $middle_name, $last_name, $ext_name, $contact_number, $birthday_month, $birthday_day, $birthday_year, $age, $sex, $lgu, $national_id, $household_id, $program_type, $region, $province, $city_municipality, $barangay, $sms_opt_in, $is_pregnant);
    if (!$insert->execute()) throw new Exception('Unable to save beneficiary.');
    $beneficiary_id = $conn->insert_id;

    $queue_number = getNextQueueNumber($conn, $queueType);
    $queue = $conn->prepare("INSERT INTO queue_entries (queue_number, queue_type, beneficiary_id, transaction_date, status, workflow_status, table_number, counter_number, called_at, assessed_at, paid_at) VALUES (?, ?, ?, CURDATE(), 'waiting', 'WAITING_STEP_2', NULL, NULL, NULL, NULL, NULL)");
    $queue->bind_param('ssi', $queue_number, $queueType, $beneficiary_id);
    if (!$queue->execute()) throw new Exception('Unable to create queue.');

    $conn->commit();
    respond(true, [
        'queue_number' => $queue_number,
        'queue_type' => $queueType,
        'category' => $categoryLabel,
        'program_type' => $program_type,
        'beneficiary_code' => $beneficiary_code,
        'region' => 'MIMAROPA',
        'date_time' => date('M d, Y h:i A')
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    respond(false, ['message' => $e->getMessage()]);
}
?>
