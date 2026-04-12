<?php
include('../config/db.php');
include('../config/app.php');

function normalizePHNumberLocal($number) {
    $number = preg_replace('/\D+/', '', $number);
    if (strpos($number, '63') === 0) {
        return '0' . substr($number, 2);
    }
    if (strpos($number, '9') === 0 && strlen($number) === 10) {
        return '0' . $number;
    }
    return $number;
}

function generateQueueNumber($conn) {
    $today = date('Y-m-d');
    $prefix = "MP-" . date('Ymd') . "-";

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM queue_entries WHERE transaction_date = ?");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $next = str_pad($result['total'] + 1, 3, "0", STR_PAD_LEFT);
    return $prefix . $next;
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$contact_number = normalizePHNumberLocal(trim($_POST['contact_number'] ?? ''));
$national_id = trim($_POST['national_id'] ?? '');
$household_id = trim($_POST['household_id'] ?? '');
$program_type = trim($_POST['program_type'] ?? 'MAX PAYOUT');
$sms_opt_in = isset($_POST['sms_opt_in']) ? 1 : 0;

$today = date('Y-m-d');

if ($first_name === '' || $last_name === '' || $contact_number === '') {
    die("Required fields are missing.");
}

/*
    Duplicate active queue protection:
    If same mobile OR same national ID OR same household ID already has waiting/serving today,
    reuse the existing queue instead of creating another one.
*/
$dupSql = "
    SELECT q.queue_number, q.id
    FROM queue_entries q
    JOIN beneficiaries b ON q.beneficiary_id = b.id
    WHERE q.transaction_date = ?
      AND q.status IN ('waiting', 'serving')
      AND (
            (b.contact_number = ? AND ? <> '')
         OR (b.national_id = ? AND ? <> '')
         OR (b.household_id = ? AND ? <> '')
      )
    LIMIT 1
";
$dup = $conn->prepare($dupSql);
$dup->bind_param(
    "sssssss",
    $today,
    $contact_number, $contact_number,
    $national_id, $national_id,
    $household_id, $household_id
);
$dup->execute();
$dupRes = $dup->get_result();

if ($dupRes->num_rows > 0) {
    $existing = $dupRes->fetch_assoc();
    $queue_number = $existing['queue_number'];
} else {
    $beneficiary_code = "BEN-" . time() . rand(100,999);
    $queue_number = generateQueueNumber($conn);

    $insertBeneficiary = $conn->prepare("
        INSERT INTO beneficiaries
        (beneficiary_code, first_name, last_name, contact_number, national_id, household_id, program_type, sms_opt_in)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insertBeneficiary->bind_param(
        "sssssssi",
        $beneficiary_code,
        $first_name,
        $last_name,
        $contact_number,
        $national_id,
        $household_id,
        $program_type,
        $sms_opt_in
    );
    $insertBeneficiary->execute();

    $beneficiary_id = $insertBeneficiary->insert_id;

    $insertQueue = $conn->prepare("
        INSERT INTO queue_entries (queue_number, beneficiary_id, transaction_date, status, sms_notified_10)
        VALUES (?, ?, ?, 'waiting', 0)
    ");
    $insertQueue->bind_param("sis", $queue_number, $beneficiary_id, $today);
    $insertQueue->execute();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your Queue Stub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{
            margin:0;
            font-family:Arial,sans-serif;
            background:#eef1f4;
            color:#1f2937;
        }
        .wrap{
            max-width:700px;
            margin:0 auto;
            padding:24px 16px 40px;
        }
        .card{
            background:#fff;
            border:1px solid #cfd6de;
            padding:30px 24px;
            text-align:center;
        }
        h1{
            margin:0 0 12px;
            color:#0f2f56;
            font-size:28px;
        }
        .queue{
            font-size:44px;
            font-weight:800;
            color:#168fcb;
            margin:16px 0 10px;
            letter-spacing:1px;
        }
        .sub{
            color:#475569;
            margin-bottom:20px;
        }
        .btn{
            display:inline-block;
            margin-top:12px;
            padding:12px 18px;
            background:#168fcb;
            color:#fff;
            text-decoration:none;
            font-weight:700;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Registration Complete</h1>
        <div class="sub">Please save your queue number.</div>
        <div class="queue"><?php echo htmlspecialchars($queue_number); ?></div>
        <div class="sub">You will receive an SMS when you are 10 queues away, if you opted in.</div>
        <a href="register.php" class="btn">Register Another</a>
    </div>
</div>
</body>
</html>