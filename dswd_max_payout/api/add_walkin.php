<?php
include('../config/db.php');

date_default_timezone_set('Asia/Manila');

$source = trim($_POST['source'] ?? 'admin');
$is_kiosk = ($source === 'kiosk');
$backPage = $is_kiosk ? '../kiosk/index.php' : '../staff/register_walkin.php';

if (!$is_kiosk) include('../auth/check.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: {$backPage}"); exit; }

$first_name = trim($_POST['first_name'] ?? '');
$middle_name = trim($_POST['middle_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$ext_name = trim($_POST['ext_name'] ?? '');
$contact_number = trim($_POST['contact_number'] ?? '');
$birthday_month = intval($_POST['birthday_month'] ?? 0);
$birthday_day = intval($_POST['birthday_day'] ?? 0);
$birthday_year = intval($_POST['birthday_year'] ?? 0);
$age = intval($_POST['age'] ?? 0);
$sex = trim($_POST['sex'] ?? '');
$region = trim($_POST['region'] ?? '');
$province = trim($_POST['province'] ?? '');
$city_municipality = trim($_POST['city_municipality'] ?? '');
$barangay = trim($_POST['barangay'] ?? '');
$lgu = trim($_POST['lgu'] ?? '');
$national_id = trim($_POST['national_id'] ?? '');
$household_id = trim($_POST['household_id'] ?? '');
$program_type = trim($_POST['program_type'] ?? '');
$sms_opt_in = intval($_POST['sms_opt_in'] ?? 0); // PWD flag
$is_pregnant = intval($_POST['is_pregnant'] ?? 0);
if ($sex !== 'Female') $is_pregnant = 0;

function backTo($url) { header('Location: ' . $url); exit; }
function kioskDone($code, $queue, $type, $duplicate = 0) {
    header('Location: ../kiosk/index.php?success=1&code=' . urlencode($code) . '&queue=' . urlencode($queue) . '&type=' . urlencode($type) . '&duplicate=' . intval($duplicate));
    exit;
}

function getNextBeneficiaryCode($conn) {
    $res = $conn->query("SELECT MAX(CAST(SUBSTRING(beneficiary_code, 5) AS UNSIGNED)) AS last_number FROM beneficiaries WHERE beneficiary_code LIKE 'PAL-%'");
    $n = 1;
    if ($res && $res->num_rows > 0) {
        $r = $res->fetch_assoc();
        if ($r['last_number'] !== null) $n = intval($r['last_number']) + 1;
    }
    return 'PAL-' . str_pad($n, 5, '0', STR_PAD_LEFT);
}

function createQueue($conn, $beneficiary_id, $age, $is_pwd, $is_pregnant) {
    $priority = ($is_pwd == 1 || $is_pregnant == 1 || intval($age) >= 60);
    $queue_type = $priority ? 'priority' : 'regular';
    $prefix = $priority ? 'PRIO-' : 'PAL-';
    $start = $priority ? 6 : 5;

    $check = $conn->prepare("SELECT queue_number, queue_type FROM queue_entries WHERE beneficiary_id=? AND DATE(transaction_date)=CURDATE() AND (workflow_status IS NULL OR workflow_status!='CANCELLED') ORDER BY id DESC LIMIT 1");
    $check->bind_param('i', $beneficiary_id);
    $check->execute();
    $active = $check->get_result();
    if ($active && $active->num_rows > 0) {
        $row = $active->fetch_assoc();
        return [$row['queue_number'], $row['queue_type']];
    }

    $last = $conn->prepare("SELECT MAX(CAST(SUBSTRING(queue_number, ?) AS UNSIGNED)) AS last_number FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND queue_type=? AND queue_number LIKE CONCAT(?, '%')");
    $last->bind_param('iss', $start, $queue_type, $prefix);
    $last->execute();
    $res = $last->get_result();
    $next = 1;
    if ($res && $res->num_rows > 0) {
        $r = $res->fetch_assoc();
        if ($r['last_number'] !== null) $next = intval($r['last_number']) + 1;
    }

    $queue_number = $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    $ins = $conn->prepare("INSERT INTO queue_entries (queue_number, queue_type, beneficiary_id, transaction_date, status, workflow_status, table_number, called_at, assessed_at, paid_at) VALUES (?, ?, ?, CURDATE(), 'waiting', 'WAITING_STEP_2', NULL, NULL, NULL, NULL)");
    $ins->bind_param('ssi', $queue_number, $queue_type, $beneficiary_id);
    $ins->execute();
    return [$queue_number, $queue_type];
}

if ($first_name==='' || $last_name==='' || $birthday_month<=0 || $birthday_day<=0 || $birthday_year<=0 || $age<0 || $sex==='' || $region==='' || $province==='' || $city_municipality==='' || $barangay==='' || $lgu==='' || $program_type==='') backTo($backPage);
if (!in_array($sex, ['Male','Female'])) backTo($backPage);

if ($contact_number !== '') {
    $dup = $conn->prepare("SELECT id, beneficiary_code, age, sms_opt_in, is_pregnant FROM beneficiaries WHERE LOWER(TRIM(first_name))=LOWER(TRIM(?)) AND LOWER(TRIM(last_name))=LOWER(TRIM(?)) AND TRIM(contact_number)=TRIM(?) LIMIT 1");
    $dup->bind_param('sss', $first_name, $last_name, $contact_number);
} else {
    $dup = $conn->prepare("SELECT id, beneficiary_code, age, sms_opt_in, is_pregnant FROM beneficiaries WHERE LOWER(TRIM(first_name))=LOWER(TRIM(?)) AND LOWER(TRIM(last_name))=LOWER(TRIM(?)) AND birthday_month=? AND birthday_day=? AND birthday_year=? LIMIT 1");
    $dup->bind_param('ssiii', $first_name, $last_name, $birthday_month, $birthday_day, $birthday_year);
}
$dup->execute();
$dupResult = $dup->get_result();

if ($dupResult && $dupResult->num_rows > 0) {
    $existing = $dupResult->fetch_assoc();
    if ($is_kiosk) {
        [$qnum, $qtype] = createQueue($conn, intval($existing['id']), intval($existing['age']), intval($existing['sms_opt_in']), intval($existing['is_pregnant']));
        kioskDone($existing['beneficiary_code'] ?? 'Existing Record', $qnum, $qtype, 1);
    }
    backTo('../staff/verifier.php');
}

$beneficiary_code = getNextBeneficiaryCode($conn);
$insert = $conn->prepare("INSERT INTO beneficiaries (beneficiary_code, first_name, middle_name, last_name, ext_name, contact_number, birthday_month, birthday_day, birthday_year, age, sex, lgu, national_id, household_id, program_type, region, province, city_municipality, barangay, sms_opt_in, is_pregnant) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$insert->bind_param('ssssssiiiisssssssssii', $beneficiary_code, $first_name, $middle_name, $last_name, $ext_name, $contact_number, $birthday_month, $birthday_day, $birthday_year, $age, $sex, $lgu, $national_id, $household_id, $program_type, $region, $province, $city_municipality, $barangay, $sms_opt_in, $is_pregnant);

if ($insert->execute()) {
    $beneficiary_id = $conn->insert_id;
    if ($is_kiosk) {
        [$qnum, $qtype] = createQueue($conn, $beneficiary_id, $age, $sms_opt_in, $is_pregnant);
        kioskDone($beneficiary_code, $qnum, $qtype, 0);
    }
    backTo('../staff/verifier.php');
}

backTo($backPage);
?>
