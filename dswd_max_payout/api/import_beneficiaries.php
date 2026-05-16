<?php
include('../auth/check.php');
include('../config/db.php');

header('Content-Type: application/json');

define('MAX_IMPORT_ROWS', 5000);

function respond($success, $message, $extra = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit;
}

function cleanHeader($value) {
    $value = strtolower(trim($value));
    $value = str_replace(["\xEF\xBB\xBF", ' ', '-', '.', '/'], ['', '_', '_', '_', '_'], $value);
    return preg_replace('/[^a-z0-9_]/', '', $value);
}

function valueFrom($row, $keys, $default = '') {
    foreach ($keys as $key) {
        $cleanKey = cleanHeader($key);
        if (isset($row[$cleanKey]) && trim($row[$cleanKey]) !== '') {
            return trim($row[$cleanKey]);
        }
    }
    return $default;
}

function parseBirthday($birthday, $month, $day, $year) {
    $month = intval($month);
    $day = intval($day);
    $year = intval($year);

    if ($month > 0 && $day > 0 && $year > 0) {
        return [$month, $day, $year];
    }

    $birthday = trim($birthday);
    if ($birthday === '') return [0, 0, 0];

    $birthday = str_replace(['-', '.'], '/', $birthday);
    $parts = explode('/', $birthday);

    if (count($parts) === 3) {
        $p1 = intval($parts[0]);
        $p2 = intval($parts[1]);
        $p3 = intval($parts[2]);

        if ($p1 > 1900) {
            return [$p2, $p3, $p1];
        }

        return [$p1, $p2, $p3];
    }

    return [0, 0, 0];
}

function calculateAge($month, $day, $year) {
    if ($month <= 0 || $day <= 0 || $year <= 0) return 0;
    $birth = DateTime::createFromFormat('!Y-n-j', $year . '-' . $month . '-' . $day);
    if (!$birth) return 0;
    $today = new DateTime('today');
    return intval($birth->diff($today)->y);
}

function normalizeSex($sex) {
    $sex = strtolower(trim($sex));
    if (in_array($sex, ['m', 'male', 'lalaki'])) return 'Male';
    if (in_array($sex, ['f', 'female', 'babae'])) return 'Female';
    return '';
}

function normalizePwd($value) {
    $value = strtolower(trim($value));
    return in_array($value, ['1', 'yes', 'y', 'true', 'pwd', 'oo']) ? 1 : 0;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    respond(false, 'No file uploaded or upload failed.');
}

$file = $_FILES['import_file'];
$filename = $file['name'];
$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

if ($extension !== 'csv') {
    respond(false, 'Please upload a CSV file. For Excel, save/export it as CSV first.');
}

$handle = fopen($file['tmp_name'], 'r');
if (!$handle) {
    respond(false, 'Unable to read uploaded file.');
}

$headers = fgetcsv($handle);
if (!$headers) {
    respond(false, 'CSV file is empty.');
}

$cleanHeaders = array_map('cleanHeader', $headers);

$getLast = $conn->query("SELECT MAX(CAST(SUBSTRING(beneficiary_code, 5) AS UNSIGNED)) AS last_number FROM beneficiaries WHERE beneficiary_code LIKE 'PAL-%'");
$nextNumber = 1;
if ($getLast && $getLast->num_rows > 0) {
    $last = $getLast->fetch_assoc();
    if ($last['last_number'] !== null) $nextNumber = intval($last['last_number']) + 1;
}

$inserted = 0;
$duplicates = 0;
$failed = 0;
$rowNumber = 1;
$errors = [];

$conn->begin_transaction();

try {
    while (($data = fgetcsv($handle)) !== false) {
        $rowNumber++;
        if ($rowNumber > MAX_IMPORT_ROWS + 1) {
            $errors[] = 'Import limit reached. Maximum rows: ' . MAX_IMPORT_ROWS;
            break;
        }

        if (count(array_filter($data, function($v) { return trim($v) !== ''; })) === 0) {
            continue;
        }

        $row = [];
        foreach ($cleanHeaders as $index => $header) {
            $row[$header] = $data[$index] ?? '';
        }

        $first_name = valueFrom($row, ['first_name', 'firstname', 'given_name']);
        $middle_name = valueFrom($row, ['middle_name', 'middlename', 'middle_initial', 'mi']);
        $last_name = valueFrom($row, ['last_name', 'lastname', 'surname', 'family_name']);
        $ext_name = valueFrom($row, ['ext_name', 'suffix', 'extension']);
        $contact_number = valueFrom($row, ['contact_number', 'contact_no', 'mobile_number', 'phone']);
        $birthday = valueFrom($row, ['birthday', 'birthdate', 'date_of_birth', 'dob']);
        $birthday_month = valueFrom($row, ['birthday_month', 'birth_month', 'month']);
        $birthday_day = valueFrom($row, ['birthday_day', 'birth_day', 'day']);
        $birthday_year = valueFrom($row, ['birthday_year', 'birth_year', 'year']);
        [$birthday_month, $birthday_day, $birthday_year] = parseBirthday($birthday, $birthday_month, $birthday_day, $birthday_year);

        $age = intval(valueFrom($row, ['age'], '0'));
        if ($age <= 0) $age = calculateAge($birthday_month, $birthday_day, $birthday_year);

        $sex = normalizeSex(valueFrom($row, ['sex', 'gender']));
        $region = valueFrom($row, ['region'], 'Region IV-A');
        $province = valueFrom($row, ['province'], 'Cavite');
        $city_municipality = valueFrom($row, ['city_municipality', 'city', 'municipality', 'city_municipal']);
        $barangay = valueFrom($row, ['barangay', 'brgy']);
        $lgu = valueFrom($row, ['lgu'], $city_municipality);
        $national_id = valueFrom($row, ['national_id', 'id_presented', 'id_type', 'id']);
        $household_id = valueFrom($row, ['household_id', 'household']);
        $program_type = valueFrom($row, ['program_type', 'program', 'assistance_type'], 'AICS');
        $sms_opt_in = normalizePwd(valueFrom($row, ['pwd', 'is_pwd', 'sms_opt_in'], '0'));

        if ($first_name === '' || $last_name === '' || $birthday_month <= 0 || $birthday_day <= 0 || $birthday_year <= 0 || $age < 0 || $sex === '' || $city_municipality === '' || $barangay === '' || $lgu === '' || $program_type === '') {
            $failed++;
            $errors[] = "Row {$rowNumber}: missing or invalid required fields.";
            continue;
        }

        if ($contact_number !== '') {
            $dup = $conn->prepare("SELECT id FROM beneficiaries WHERE LOWER(TRIM(first_name))=LOWER(TRIM(?)) AND LOWER(TRIM(last_name))=LOWER(TRIM(?)) AND TRIM(contact_number)=TRIM(?) LIMIT 1");
            $dup->bind_param('sss', $first_name, $last_name, $contact_number);
        } else {
            $dup = $conn->prepare("SELECT id FROM beneficiaries WHERE LOWER(TRIM(first_name))=LOWER(TRIM(?)) AND LOWER(TRIM(last_name))=LOWER(TRIM(?)) AND birthday_month=? AND birthday_day=? AND birthday_year=? LIMIT 1");
            $dup->bind_param('ssiii', $first_name, $last_name, $birthday_month, $birthday_day, $birthday_year);
        }

        $dup->execute();
        if ($dup->get_result()->num_rows > 0) {
            $duplicates++;
            continue;
        }

        $beneficiary_code = 'PAL-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        $nextNumber++;

        $insert = $conn->prepare("
            INSERT INTO beneficiaries (
                beneficiary_code, first_name, middle_name, last_name, ext_name, contact_number,
                birthday_month, birthday_day, birthday_year, age, sex, lgu, national_id,
                household_id, program_type, region, province, city_municipality, barangay, sms_opt_in
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $insert->bind_param(
            'ssssssiiiisssssssssi',
            $beneficiary_code, $first_name, $middle_name, $last_name, $ext_name, $contact_number,
            $birthday_month, $birthday_day, $birthday_year, $age, $sex, $lgu, $national_id,
            $household_id, $program_type, $region, $province, $city_municipality, $barangay, $sms_opt_in
        );

        if ($insert->execute()) {
            $inserted++;
        } else {
            $failed++;
            $errors[] = "Row {$rowNumber}: failed to insert.";
        }
    }

    fclose($handle);
    $conn->commit();

    respond(true, 'Import finished.', [
        'inserted' => $inserted,
        'duplicates' => $duplicates,
        'failed' => $failed,
        'errors' => array_slice($errors, 0, 10)
    ]);
} catch (Exception $e) {
    fclose($handle);
    $conn->rollback();
    respond(false, 'Import failed: ' . $e->getMessage());
}
?>
