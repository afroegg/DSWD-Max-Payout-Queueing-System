<?php
include('../auth/check.php');
include('../config/db.php');

header('Content-Type: application/json');
define('MAX_IMPORT_ROWS', 5000);

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
        $p1 = intval($parts[0]);
        $p2 = intval($parts[1]);
        $p3 = intval($parts[2]);
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
    if (in_array($sex, ['m', 'male', 'lalaki'])) return 'Male';
    if (in_array($sex, ['f', 'female', 'babae'])) return 'Female';
    return '';
}

function normalizePwd($value) {
    $value = strtolower(trim((string)$value));
    return in_array($value, ['1', 'yes', 'y', 'true', 'pwd', 'oo']) ? 1 : 0;
}

function readCsvRows($path) {
    $rows = [];
    $handle = fopen($path, 'r');
    if (!$handle) respond(false, 'Unable to read uploaded CSV file.');
    while (($data = fgetcsv($handle)) !== false) $rows[] = $data;
    fclose($handle);
    return $rows;
}

function colIndex($cellRef) {
    preg_match('/[A-Z]+/', strtoupper($cellRef), $m);
    $letters = $m[0] ?? 'A';
    $index = 0;
    for ($i = 0; $i < strlen($letters); $i++) $index = $index * 26 + (ord($letters[$i]) - 64);
    return $index - 1;
}

function xmlObject($xml, $errorMessage) {
    libxml_use_internal_errors(true);
    $obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_COMPACT | LIBXML_PARSEHUGE);
    if (!$obj) respond(false, $errorMessage);
    return $obj;
}

function readXlsxRows($path) {
    if (!class_exists('ZipArchive')) respond(false, 'Excel import requires PHP ZipArchive. Please redeploy with zip enabled or upload CSV.');

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) respond(false, 'Unable to open Excel file. Please upload a valid .xlsx file.');

    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $shared = xmlObject($sharedXml, 'Invalid shared strings XML. Try saving the Excel file again as .xlsx or CSV.');
        foreach ($shared->si as $si) {
            $text = '';
            if (isset($si->t)) $text = (string)$si->t;
            if (isset($si->r)) foreach ($si->r as $run) $text .= (string)$run->t;
            $sharedStrings[] = $text;
        }
    }

    $sheetPath = 'xl/worksheets/sheet1.xml';
    $workbookXml = $zip->getFromName('xl/workbook.xml');
    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

    if ($workbookXml !== false && $relsXml !== false) {
        $workbook = xmlObject($workbookXml, 'Invalid workbook XML. Try saving the Excel file again as .xlsx or CSV.');
        $rels = xmlObject($relsXml, 'Invalid workbook relationships XML. Try saving the Excel file again as .xlsx or CSV.');
        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $firstSheet = $workbook->sheets->sheet[0] ?? null;
        if ($firstSheet) {
            $attrs = $firstSheet->attributes('r', true);
            $rid = (string)$attrs['id'];
            foreach ($rels->Relationship as $rel) {
                $a = $rel->attributes();
                if ((string)$a['Id'] === $rid) {
                    $target = (string)$a['Target'];
                    if (strpos($target, '/xl/') === 0) $sheetPath = ltrim($target, '/');
                    elseif (strpos($target, 'worksheets/') === 0) $sheetPath = 'xl/' . $target;
                    else $sheetPath = 'xl/' . ltrim($target, '/');
                    break;
                }
            }
        }
    }

    $sheetXml = $zip->getFromName($sheetPath);
    if ($sheetXml === false) $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetXml === false) respond(false, 'Unable to read the first worksheet from the Excel file.');

    $sheet = xmlObject($sheetXml, 'Invalid worksheet XML. Try opening the file in Excel/Google Sheets, save as .xlsx again, or export as CSV.');

    $rows = [];
    foreach ($sheet->sheetData->row as $rowNode) {
        $row = [];
        foreach ($rowNode->c as $cell) {
            $attrs = $cell->attributes();
            $ref = (string)$attrs['r'];
            $type = (string)$attrs['t'];
            $style = (string)$attrs['s'];
            $index = colIndex($ref);
            $value = '';

            if ($type === 's') {
                $sharedIndex = intval((string)$cell->v);
                $value = $sharedStrings[$sharedIndex] ?? '';
            } elseif ($type === 'inlineStr') {
                $value = (string)$cell->is->t;
            } elseif ($type === 'b') {
                $value = ((string)$cell->v) === '1' ? 'Yes' : 'No';
            } else {
                $value = isset($cell->v) ? (string)$cell->v : '';
            }

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(false, 'Invalid request method.');
if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) respond(false, 'No file uploaded or upload failed.');

$file = $_FILES['import_file'];
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($extension, ['csv', 'xlsx'])) respond(false, 'Please upload CSV or Excel .xlsx file. Legacy .xls is not supported.');

$allRows = $extension === 'xlsx' ? readXlsxRows($file['tmp_name']) : readCsvRows($file['tmp_name']);
if (count($allRows) < 1) respond(false, 'Uploaded file is empty.');

$headers = array_shift($allRows);
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
    foreach ($allRows as $data) {
        $rowNumber++;
        if ($rowNumber > MAX_IMPORT_ROWS + 1) { $errors[] = 'Import limit reached. Maximum rows: ' . MAX_IMPORT_ROWS; break; }
        if (count(array_filter($data, fn($v) => trim((string)$v) !== '')) === 0) continue;

        $row = [];
        foreach ($cleanHeaders as $i => $header) $row[$header] = $data[$i] ?? '';

        $first_name = valueFrom($row, ['first_name', 'firstname', 'given_name']);
        $middle_name = valueFrom($row, ['middle_name', 'middlename', 'middle_initial', 'mi']);
        $last_name = valueFrom($row, ['last_name', 'lastname', 'surname', 'family_name']);
        $ext_name = valueFrom($row, ['ext_name', 'suffix', 'extension']);
        $contact_number = valueFrom($row, ['contact_number', 'contact_no', 'mobile_number', 'phone']);
        $birthday = valueFrom($row, ['birthday', 'birthdate', 'date_of_birth', 'dob']);
        [$birthday_month, $birthday_day, $birthday_year] = parseBirthday($birthday, valueFrom($row, ['birthday_month', 'birth_month', 'month']), valueFrom($row, ['birthday_day', 'birth_day', 'day']), valueFrom($row, ['birthday_year', 'birth_year', 'year']));
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

        if ($first_name === '' || $last_name === '' || $birthday_month <= 0 || $birthday_day <= 0 || $birthday_year <= 0 || $sex === '' || $city_municipality === '' || $barangay === '' || $lgu === '') {
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
        if ($dup->get_result()->num_rows > 0) { $duplicates++; continue; }

        $beneficiary_code = 'PAL-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        $nextNumber++;

        $insert = $conn->prepare("INSERT INTO beneficiaries (beneficiary_code, first_name, middle_name, last_name, ext_name, contact_number, birthday_month, birthday_day, birthday_year, age, sex, lgu, national_id, household_id, program_type, region, province, city_municipality, barangay, sms_opt_in) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insert->bind_param('ssssssiiiisssssssssi', $beneficiary_code, $first_name, $middle_name, $last_name, $ext_name, $contact_number, $birthday_month, $birthday_day, $birthday_year, $age, $sex, $lgu, $national_id, $household_id, $program_type, $region, $province, $city_municipality, $barangay, $sms_opt_in);

        if ($insert->execute()) $inserted++;
        else { $failed++; $errors[] = "Row {$rowNumber}: failed to insert."; }
    }

    $conn->commit();
    respond(true, 'Import finished.', ['inserted' => $inserted, 'duplicates' => $duplicates, 'failed' => $failed, 'errors' => array_slice($errors, 0, 10)]);
} catch (Exception $e) {
    $conn->rollback();
    respond(false, 'Import failed: ' . $e->getMessage());
}
?>
