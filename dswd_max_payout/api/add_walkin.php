<?php
include('../config/db.php');

date_default_timezone_set('Asia/Manila');

$source = trim($_POST['source'] ?? 'admin');
$is_kiosk = ($source === 'kiosk');
$backPage = $is_kiosk ? '../kiosk/index.php' : '../staff/register_walkin.php';

if (!$is_kiosk) include('../auth/check.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: {$backPage}"); exit; }

function normalizeContactNumber($value) {
    $value = trim((string)$value);
    if ($value === '') return '';
    $value = preg_replace('/[^0-9+]/', '', $value);
    if (preg_match('/^09\d{9}$/', $value)) return $value;
    if (preg_match('/^\+639\d{9}$/', $value)) return $value;
    if (preg_match('/^639\d{9}$/', $value)) return '+' . $value;
    return false;
}

function contactVariants($value) {
    $value = normalizeContactNumber($value);
    if ($value === false || $value === '') return [];
    if (preg_match('/^09\d{9}$/', $value)) return [$value, '+63' . substr($value, 1), '63' . substr($value, 1)];
    if (preg_match('/^\+639\d{9}$/', $value)) return [$value, '0' . substr($value, 3), substr($value, 1)];
    return [$value];
}

function normalizeDswdHouseholdId($value) {
    $value = strtoupper(trim((string)$value));
    if ($value === '') return '';
    $digits = preg_replace('/\D/', '', $value);
    if ($digits === '' || strlen($digits) > 10) return false;
    return 'HH-' . str_pad($digits, 10, '0', STR_PAD_LEFT);
}

function backTo($url) { header('Location: ' . $url); exit; }
function kioskDuplicate($reason, $code = '') {
    header('Location: ../kiosk/index.php?duplicate=1&reason=' . urlencode($reason) . '&code=' . urlencode($code));
    exit;
}
function kioskRegistered($code) {
    header('Location: ../kiosk/index.php?registered=1&code=' . urlencode($code));
    exit;
}

$first_name = trim($_POST['first_name'] ?? '');
$middle_name = trim($_POST['middle_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$ext_name = trim($_POST['ext_name'] ?? '');
$contact_number = normalizeContactNumber($_POST['contact_number'] ?? '');
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
$household_id = normalizeDswdHouseholdId($_POST['household_id'] ?? '');
$program_type = trim($_POST['program_type'] ?? '');
$sms_opt_in = intval($_POST['sms_opt_in'] ?? 0);
$is_pregnant = intval($_POST['is_pregnant'] ?? 0);
if ($sex !== 'Female') $is_pregnant = 0;

function getNextBeneficiaryCode($conn) {
    $res = $conn->query("SELECT MAX(CAST(SUBSTRING(beneficiary_code, 5) AS UNSIGNED)) AS last_number FROM beneficiaries WHERE beneficiary_code LIKE 'PAL-%'");
    $n = 1;
    if ($res && $res->num_rows > 0) {
        $r = $res->fetch_assoc();
        if ($r['last_number'] !== null) $n = intval($r['last_number']) + 1;
    }
    return 'PAL-' . str_pad($n, 5, '0', STR_PAD_LEFT);
}

if ($contact_number === false || $household_id === false) backTo($backPage);
if ($first_name==='' || $last_name==='' || $birthday_month<=0 || $birthday_day<=0 || $birthday_year<=0 || $age<0 || $sex==='' || $region==='' || $province==='' || $city_municipality==='' || $barangay==='' || $lgu==='' || $program_type==='') backTo($backPage);
if (!in_array($sex, ['Male','Female'])) backTo($backPage);

$dupResult = null;
if ($contact_number !== '') {
    $variants = contactVariants($contact_number);
    $c1 = $variants[0] ?? $contact_number;
    $c2 = $variants[1] ?? $contact_number;
    $c3 = $variants[2] ?? $contact_number;
    $dup = $conn->prepare("SELECT id, beneficiary_code FROM beneficiaries WHERE TRIM(contact_number) IN (?, ?, ?) LIMIT 1");
    $dup->bind_param('sss', $c1, $c2, $c3);
    $dup->execute();
    $dupResult = $dup->get_result();
}
if ((!$dupResult || $dupResult->num_rows === 0) && $household_id !== '') {
    $dup = $conn->prepare("SELECT id, beneficiary_code FROM beneficiaries WHERE TRIM(household_id)=TRIM(?) LIMIT 1");
    $dup->bind_param('s', $household_id);
    $dup->execute();
    $dupResult = $dup->get_result();
}
if (!$dupResult || $dupResult->num_rows === 0) {
    $dup = $conn->prepare("SELECT id, beneficiary_code FROM beneficiaries WHERE LOWER(TRIM(first_name))=LOWER(TRIM(?)) AND LOWER(TRIM(last_name))=LOWER(TRIM(?)) AND birthday_month=? AND birthday_day=? AND birthday_year=? LIMIT 1");
    $dup->bind_param('ssiii', $first_name, $last_name, $birthday_month, $birthday_day, $birthday_year);
    $dup->execute();
    $dupResult = $dup->get_result();
}
if ($dupResult && $dupResult->num_rows > 0) {
    $existing = $dupResult->fetch_assoc();
    if ($is_kiosk) kioskDuplicate('Duplicate beneficiary already exists in verifier.', $existing['beneficiary_code'] ?? '');
    backTo('../staff/verifier.php');
}

$beneficiary_code = getNextBeneficiaryCode($conn);
$insert = $conn->prepare("INSERT INTO beneficiaries (beneficiary_code, first_name, middle_name, last_name, ext_name, contact_number, birthday_month, birthday_day, birthday_year, age, sex, lgu, national_id, household_id, program_type, region, province, city_municipality, barangay, sms_opt_in, is_pregnant) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$insert->bind_param('ssssssiiiisssssssssii', $beneficiary_code, $first_name, $middle_name, $last_name, $ext_name, $contact_number, $birthday_month, $birthday_day, $birthday_year, $age, $sex, $lgu, $national_id, $household_id, $program_type, $region, $province, $city_municipality, $barangay, $sms_opt_in, $is_pregnant);

if ($insert->execute()) {
    if ($is_kiosk) kioskRegistered($beneficiary_code);
    backTo('../staff/verifier.php');
}

backTo($backPage);
?>
