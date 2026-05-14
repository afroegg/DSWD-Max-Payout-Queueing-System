<?php
include('../config/db.php');

date_default_timezone_set('Asia/Manila');

$source = trim($_POST['source'] ?? 'admin');
$is_kiosk = ($source === 'kiosk');
$backPage = $is_kiosk ? '../kiosk/index.php' : '../staff/register_walkin.php';
$successPage = $is_kiosk ? '../kiosk/index.php' : '../staff/verifier.php';

if (!$is_kiosk) {
    include('../auth/check.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: {$backPage}");
    exit;
}

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
$sms_opt_in = intval($_POST['sms_opt_in'] ?? 0);

function redirectBack($message, $backPage) {
    $safeMessage = addslashes($message);
    echo "<script>
        alert('{$safeMessage}');
        window.location.href = '{$backPage}';
    </script>";
    exit;
}

if (
    $first_name === '' ||
    $last_name === '' ||
    $birthday_month <= 0 ||
    $birthday_day <= 0 ||
    $birthday_year <= 0 ||
    $age < 0 ||
    $sex === '' ||
    $region === '' ||
    $province === '' ||
    $city_municipality === '' ||
    $barangay === '' ||
    $lgu === '' ||
    $program_type === ''
) {
    redirectBack('Please complete all required fields.', $backPage);
}

if (!in_array($sex, ['Male', 'Female'])) {
    redirectBack('Invalid sex selected.', $backPage);
}

if ($birthday_month < 1 || $birthday_month > 12 || $birthday_day < 1 || $birthday_day > 31) {
    redirectBack('Invalid birthday.', $backPage);
}

if ($contact_number !== '') {
    $dup = $conn->prepare("
        SELECT id, beneficiary_code
        FROM beneficiaries
        WHERE LOWER(TRIM(first_name)) = LOWER(TRIM(?))
          AND LOWER(TRIM(last_name)) = LOWER(TRIM(?))
          AND TRIM(contact_number) = TRIM(?)
        LIMIT 1
    ");
    $dup->bind_param("sss", $first_name, $last_name, $contact_number);
} else {
    $dup = $conn->prepare("
        SELECT id, beneficiary_code
        FROM beneficiaries
        WHERE LOWER(TRIM(first_name)) = LOWER(TRIM(?))
          AND LOWER(TRIM(last_name)) = LOWER(TRIM(?))
          AND birthday_month = ?
          AND birthday_day = ?
          AND birthday_year = ?
        LIMIT 1
    ");
    $dup->bind_param("ssiii", $first_name, $last_name, $birthday_month, $birthday_day, $birthday_year);
}

$dup->execute();
$dupResult = $dup->get_result();

if ($dupResult && $dupResult->num_rows > 0) {
    $existing = $dupResult->fetch_assoc();
    $code = urlencode($existing['beneficiary_code'] ?? 'Existing Record');

    if ($is_kiosk) {
        header("Location: ../kiosk/index.php?success=1&duplicate=1&code={$code}");
        exit;
    }

    redirectBack('Duplicate beneficiary record found. Existing Code: ' . ($existing['beneficiary_code'] ?? 'Existing Record'), '../staff/verifier.php');
}

$getLast = $conn->query("
    SELECT MAX(CAST(SUBSTRING(beneficiary_code, 5) AS UNSIGNED)) AS last_number
    FROM beneficiaries
    WHERE beneficiary_code LIKE 'PAL-%'
");

$nextNumber = 1;

if ($getLast && $getLast->num_rows > 0) {
    $row = $getLast->fetch_assoc();
    if ($row['last_number'] !== null) {
        $nextNumber = intval($row['last_number']) + 1;
    }
}

$beneficiary_code = 'PAL-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

$insert = $conn->prepare("
    INSERT INTO beneficiaries (
        beneficiary_code,
        first_name,
        middle_name,
        last_name,
        ext_name,
        contact_number,
        birthday_month,
        birthday_day,
        birthday_year,
        age,
        sex,
        lgu,
        national_id,
        household_id,
        program_type,
        region,
        province,
        city_municipality,
        barangay,
        sms_opt_in
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$insert->bind_param(
    "ssssssiiiisssssssssi",
    $beneficiary_code,
    $first_name,
    $middle_name,
    $last_name,
    $ext_name,
    $contact_number,
    $birthday_month,
    $birthday_day,
    $birthday_year,
    $age,
    $sex,
    $lgu,
    $national_id,
    $household_id,
    $program_type,
    $region,
    $province,
    $city_municipality,
    $barangay,
    $sms_opt_in
);

if ($insert->execute()) {
    $code = urlencode($beneficiary_code);

    if ($is_kiosk) {
        header("Location: ../kiosk/index.php?success=1&code={$code}");
        exit;
    }

    echo "<script>
        alert('Beneficiary record saved successfully. Code: {$beneficiary_code}');
        window.location.href = '../staff/verifier.php';
    </script>";
    exit;
}

$error = addslashes($conn->error);
redirectBack('Failed to save beneficiary record. Error: ' . $error, $backPage);
?>
