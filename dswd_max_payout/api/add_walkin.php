<?php
include('../auth/check.php');
include('../config/db.php');

function generateQueueNumber($conn) {
    $today = date('Y-m-d');

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM queue_entries
        WHERE transaction_date = ?
    ");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $next = str_pad($result['total'] + 1, 5, "0", STR_PAD_LEFT);
    return "MPO-" . $next;
}

$first_name = trim($_POST['first_name']);
$middle_name = trim($_POST['middle_name']);
$last_name = trim($_POST['last_name']);
$suffix = trim($_POST['suffix']);
$sex = trim($_POST['sex']);
$birthdate = !empty($_POST['birthdate']) ? $_POST['birthdate'] : null;
$address = trim($_POST['address']);
$contact_number = trim($_POST['contact_number']);
$national_id = trim($_POST['national_id']);
$household_id = trim($_POST['household_id']);
$program_type = trim($_POST['program_type']);

if (empty($first_name) || empty($last_name) || empty($program_type)) {
    die("Required fields are missing.");
}

$beneficiary_code = "BEN-" . time();
$today = date('Y-m-d');
$queue_number = generateQueueNumber($conn);

$stmt = $conn->prepare("INSERT INTO beneficiaries 
(beneficiary_code, first_name, middle_name, last_name, suffix, sex, birthdate, address, contact_number, national_id, household_id, program_type)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param(
    "ssssssssssss",
    $beneficiary_code,
    $first_name,
    $middle_name,
    $last_name,
    $suffix,
    $sex,
    $birthdate,
    $address,
    $contact_number,
    $national_id,
    $household_id,
    $program_type
);

if ($stmt->execute()) {
    $beneficiary_id = $stmt->insert_id;

    $stmt2 = $conn->prepare("INSERT INTO queue_entries (queue_number, beneficiary_id, transaction_date, status) VALUES (?, ?, ?, 'waiting')");
    $stmt2->bind_param("sis", $queue_number, $beneficiary_id, $today);

    if ($stmt2->execute()) {
        echo "
        <h2>Registration Successful</h2>
        <p><strong>Queue Number:</strong> $queue_number</p>
        <p><strong>Name:</strong> $first_name $last_name</p>
        <p><strong>Program:</strong> $program_type</p>
        <button onclick='window.print()'>Print Stub</button>
        <br><br>
        <a href='../staff/register_walkin.php'>Register Another</a>
        ";
    } else {
        echo "Error creating queue entry.";
    }
} else {
    echo "Error saving beneficiary.";
}
?>
