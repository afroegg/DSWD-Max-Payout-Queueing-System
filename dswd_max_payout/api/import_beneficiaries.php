<?php
include('../auth/check.php');
include('../config/db.php');

header('Content-Type: application/json');
set_time_limit(240);
ini_set('memory_limit', '256M');
define('MAX_IMPORT_ROWS', 5000);
define('BATCH_SIZE', 250);

function respond($success, $message, $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

function cleanHeader($value) {
    $value = strtolower(trim((string)$value));
    $value = str_replace(["\xEF\xBB\xBF", ' ', '-', '.', '/'], ['', '_', '_', '_', '_'], $value);
    return preg_replace('/[^a-z0-9_]/', '', $value);
}

function valueFrom($row, $keys, $default = '') {
    foreach ($keys as $key) {
        $key = cleanHeader($key);
        if (isset($row[$key]) && trim((string)$row[$key]) !== '') return trim((string)$row[$key]);
    }
    return $default;
}

function yesNo($value) {
    $value = strtolower(trim((string)$value));
    return in_array($value, ['1','yes','y','true','oo','pwd','pregnant','buntis']) ? 1 : 0;
}

function parseBirthday($birthday, $month, $day, $year) {
    $month = intval($month); $day = intval($day); $year = intval($year);
    if ($month > 0 && $day > 0 && $year > 0) return [$month, $day, $year];
    $birthday = trim((string)$birthday);
    if ($birthday === '') return [0, 0, 0];
    if (is_numeric($birthday) && intval($birthday) > 20000) {
        $unix = (intval($birthday) - 25569) * 86400;
        return [intval(gmdate('n', $unix)), intval(gmdate('j', $unix)), intval(gmdate('Y', $unix))];
    }
    $birthday = str_replace(['-', '.'], '/', $birthday);
    $parts = explode('/', $birthday);
    if (count($parts) === 3) {
        $p1 = intval($parts[0]); $p2 = intval($parts[1]); $p3 = intval($parts[2]);
        if ($p1 > 1900) return [$p2, $p3, $p1];
        return [$p1, $p2, $p3];
    }
    return [0, 0, 0];
}

function calculateAge($month, $day, $year) {
    if ($month <= 0 || $day <= 0 || $year <= 0) return 0;
    $birth = DateTime::createFromFormat('!Y-n-j', $year . '-' . $month . '-' . $day);
    if (!$birth) return 0;
    return intval($birth->diff(new DateTime('today'))->y);
}

function normalizeSex($sex) {
    $sex = strtolower(trim((string)$sex));
    if (in_array($sex, ['m','male','lalaki'])) return 'Male';
    if (in_array($sex, ['f','female','babae'])) return 'Female';
    return '';
}

function duplicateKeys($first, $last, $contact, $month, $day, $year) {
    $first = strtolower(trim($first));
    $last = strtolower(trim($last));
    $contact = trim($contact);
    $keys = [];
    if ($contact !== '') $keys[] = 'contact|' . $first . '|' . $last . '|' . $contact;
    $keys[] = 'bday|' . $first . '|' . $last . '|' . intval($month) . '|' . intval($day) . '|' . intval($year);
    return $keys;
}

function colIndex($cellRef) {
    preg_match('/[A-Z]+/', strtoupper($cellRef), $m);
    $letters = $m[0] ?? 'A';
    $index = 0;
    for ($i = 0; $i < strlen($letters); $i++) $index = $index * 26 + (ord($letters[$i]) - 64);
    return $index - 1;
}

function xmlObject($xml, $message) {
    libxml_use_internal_errors(true);
    $obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_COMPACT | LIBXML_PARSEHUGE);
    if (!$obj) respond(false, $message);
    return $obj;
}

function readXlsxRows($path) {
    if (!class_exists('ZipArchive')) respond(false, 'Excel import requires PHP ZipArchive. CSV import is faster and recommended.');
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) respond(false, 'Unable to open Excel file. Upload valid .xlsx or CSV.');
    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $shared = xmlObject($sharedXml, 'Invalid Excel shared strings. Export as CSV.');
        foreach ($shared->si as $si) {
            $text = '';
            if (isset($si->t)) $text .= (string)$si->t;
            if (isset($si->r)) foreach ($si->r as $run) $text .= (string)$run->t;
            $sharedStrings[] = $text;
        }
    }
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetXml === false) respond(false, 'Unable to read first worksheet. Export the file as CSV.');
    $sheet = xmlObject($sheetXml, 'Invalid worksheet XML. Export as CSV.');
    $rows = [];
    foreach ($sheet->sheetData->row as $rowNode) {
        $row = [];
        foreach ($rowNode->c as $cell) {
            $attrs = $cell->attributes();
            $index = colIndex((string)$attrs['r']);
            $type = (string)$attrs['t'];
            if ($type === 's') $value = $sharedStrings[intval((string)$cell->v)] ?? '';
            elseif ($type === 'inlineStr') $value = (string)$cell->is->t;
            elseif ($type === 'b') $value = ((string)$cell->v) === '1' ? 'Yes' : 'No';
            else $value = isset($cell->v) ? (string)$cell->v : '';
            $row[$index] = $value;
        }
        if (!empty($row)) {
            ksort($row);
            $max = max(array_keys($row));
            $full = [];
            for ($i = 0; $i <= $max; $i++) $full[] = $row[$i] ?? '';
            $rows[] = $full;
        }
    }
    $zip->close();
    return $rows;
}

function buildRecord($row, $rowNumber, &$failed, &$errors) {
    $first_name = valueFrom($row, ['first_name','firstname','given_name']);
    $middle_name = valueFrom($row, ['middle_name','middlename','middle_initial','mi']);
    $last_name = valueFrom($row, ['last_name','lastname','surname','family_name']);
    $ext_name = valueFrom($row, ['ext_name','suffix','extension']);
    $contact_number = valueFrom($row, ['contact_number','contact_no','mobile_number','phone']);
    $birthday = valueFrom($row, ['birthday','birthdate','date_of_birth','dob']);
    [$birthday_month, $birthday_day, $birthday_year] = parseBirthday($birthday, valueFrom($row, ['birthday_month','birth_month','month']), valueFrom($row, ['birthday_day','birth_day','day']), valueFrom($row, ['birthday_year','birth_year','year']));
    $age = intval(valueFrom($row, ['age'], '0'));
    if ($age <= 0) $age = calculateAge($birthday_month, $birthday_day, $birthday_year);
    $sex = normalizeSex(valueFrom($row, ['sex','gender']));
    $region = valueFrom($row, ['region'], 'Region IV-A');
    $province = valueFrom($row, ['province'], 'Cavite');
    $city_municipality = valueFrom($row, ['city_municipality','city','municipality','city_municipal']);
    $barangay = valueFrom($row, ['barangay','brgy']);
    $lgu = valueFrom($row, ['lgu'], $city_municipality);
    $national_id = valueFrom($row, ['national_id','id_presented','id_type','id']);
    $household_id = valueFrom($row, ['household_id','household']);
    $program_type = valueFrom($row, ['program_type','program','assistance_type'], 'AICS');
    $is_pwd = yesNo(valueFrom($row, ['pwd','is_pwd','sms_opt_in'], '0'));
    $is_pregnant = yesNo(valueFrom($row, ['pregnant','is_pregnant','pregnancy_status'], '0'));
    if ($sex !== 'Female') $is_pregnant = 0;

    if ($first_name === '' || $last_name === '' || $birthday_month <= 0 || $birthday_day <= 0 || $birthday_year <= 0 || $sex === '' || $city_municipality === '' || $barangay === '' || $lgu === '') {
        $failed++;
        if (count($errors) < 10) $errors[] = "Row {$rowNumber}: missing or invalid required fields.";
        return null;
    }
    return [$first_name,$middle_name,$last_name,$ext_name,$contact_number,$birthday_month,$birthday_day,$birthday_year,$age,$sex,$lgu,$national_id,$household_id,$program_type,$region,$province,$city_municipality,$barangay,$is_pwd,$is_pregnant];
}

function sqlValue($conn, $value) {
    if ($value === null) return 'NULL';
    return "'" . $conn->real_escape_string((string)$value) . "'";
}

function insertBatch($conn, $records, &$inserted, &$failed, &$errors) {
    if (count($records) === 0) return;
    $values = [];
    foreach ($records as $r) {
        $rowValues = [];
        foreach ($r as $v) $rowValues[] = sqlValue($conn, $v);
        $values[] = '(' . implode(',', $rowValues) . ')';
    }
    $sql = "INSERT INTO beneficiaries (beneficiary_code, first_name, middle_name, last_name, ext_name, contact_number, birthday_month, birthday_day, birthday_year, age, sex, lgu, national_id, household_id, program_type, region, province, city_municipality, barangay, sms_opt_in, is_pregnant) VALUES " . implode(',', $values);
    if ($conn->query($sql)) $inserted += count($records);
    else { $failed += count($records); if (count($errors) < 10) $errors[] = 'Batch insert failed: ' . $conn->error; }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(false, 'Invalid request method.');
if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) respond(false, 'No file uploaded or upload failed.');

$file = $_FILES['import_file'];
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($extension, ['csv','xlsx'])) respond(false, 'Please upload CSV or Excel .xlsx file.');

$getLast = $conn->query("SELECT MAX(CAST(SUBSTRING(beneficiary_code, 5) AS UNSIGNED)) AS last_number FROM beneficiaries WHERE beneficiary_code LIKE 'PAL-%'");
$nextNumber = 1;
if ($getLast && $getLast->num_rows > 0) { $last = $getLast->fetch_assoc(); if ($last['last_number'] !== null) $nextNumber = intval($last['last_number']) + 1; }

$existingKeys = [];
$existing = $conn->query("SELECT first_name,last_name,contact_number,birthday_month,birthday_day,birthday_year FROM beneficiaries");
if ($existing) while ($e = $existing->fetch_assoc()) foreach (duplicateKeys($e['first_name'],$e['last_name'],$e['contact_number'],$e['birthday_month'],$e['birthday_day'],$e['birthday_year']) as $key) $existingKeys[$key] = true;

$inserted = 0; $duplicates = 0; $failed = 0; $rowNumber = 1; $errors = [];
$seenImportKeys = []; $batch = [];
$conn->begin_transaction();

try {
    if ($extension === 'csv') {
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) respond(false, 'Unable to read uploaded CSV file.');
        $headers = fgetcsv($handle);
        if (!$headers) respond(false, 'CSV header row is missing.');
        $cleanHeaders = array_map('cleanHeader', $headers);
        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if ($rowNumber > MAX_IMPORT_ROWS + 1) { $errors[] = 'Import limit reached. Maximum rows: ' . MAX_IMPORT_ROWS; break; }
            if (count(array_filter($data, fn($v) => trim((string)$v) !== '')) === 0) continue;
            $row = [];
            foreach ($cleanHeaders as $i => $header) $row[$header] = $data[$i] ?? '';
            $r = buildRecord($row, $rowNumber, $failed, $errors);
            if (!$r) continue;
            $isDuplicate = false;
            foreach (duplicateKeys($r[0], $r[2], $r[4], $r[5], $r[6], $r[7]) as $key) if (isset($existingKeys[$key]) || isset($seenImportKeys[$key])) { $isDuplicate = true; break; }
            if ($isDuplicate) { $duplicates++; continue; }
            foreach (duplicateKeys($r[0], $r[2], $r[4], $r[5], $r[6], $r[7]) as $key) $seenImportKeys[$key] = true;
            array_unshift($r, 'PAL-' . str_pad($nextNumber++, 5, '0', STR_PAD_LEFT));
            $batch[] = $r;
            if (count($batch) >= BATCH_SIZE) { insertBatch($conn, $batch, $inserted, $failed, $errors); $batch = []; }
        }
        fclose($handle);
    } else {
        $allRows = readXlsxRows($file['tmp_name']);
        if (count($allRows) < 1) respond(false, 'Uploaded file is empty.');
        $cleanHeaders = array_map('cleanHeader', array_shift($allRows));
        foreach ($allRows as $data) {
            $rowNumber++;
            if ($rowNumber > MAX_IMPORT_ROWS + 1) { $errors[] = 'Import limit reached. Maximum rows: ' . MAX_IMPORT_ROWS; break; }
            if (count(array_filter($data, fn($v) => trim((string)$v) !== '')) === 0) continue;
            $row = [];
            foreach ($cleanHeaders as $i => $header) $row[$header] = $data[$i] ?? '';
            $r = buildRecord($row, $rowNumber, $failed, $errors);
            if (!$r) continue;
            $isDuplicate = false;
            foreach (duplicateKeys($r[0], $r[2], $r[4], $r[5], $r[6], $r[7]) as $key) if (isset($existingKeys[$key]) || isset($seenImportKeys[$key])) { $isDuplicate = true; break; }
            if ($isDuplicate) { $duplicates++; continue; }
            foreach (duplicateKeys($r[0], $r[2], $r[4], $r[5], $r[6], $r[7]) as $key) $seenImportKeys[$key] = true;
            array_unshift($r, 'PAL-' . str_pad($nextNumber++, 5, '0', STR_PAD_LEFT));
            $batch[] = $r;
            if (count($batch) >= BATCH_SIZE) { insertBatch($conn, $batch, $inserted, $failed, $errors); $batch = []; }
        }
    }
    insertBatch($conn, $batch, $inserted, $failed, $errors);
    $conn->commit();
    respond(true, 'Import finished.', ['inserted'=>$inserted,'duplicates'=>$duplicates,'failed'=>$failed,'errors'=>array_slice($errors,0,10)]);
} catch (Exception $e) {
    $conn->rollback();
    respond(false, 'Import failed: ' . $e->getMessage());
}
?>
