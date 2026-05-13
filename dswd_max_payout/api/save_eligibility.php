<?php
include('../auth/check.php');
include('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../staff/verifier.php');
    exit;
}

$beneficiary_id = intval($_POST['beneficiary_id'] ?? 0);
if ($beneficiary_id <= 0) {
    header('Location: ../staff/verifier.php');
    exit;
}

$id_type_presented = trim($_POST['id_type_presented'] ?? '');
$id_reference_note = trim($_POST['id_reference_note'] ?? '');
$matches_masterlist = trim($_POST['matches_masterlist'] ?? 'No');
$household_members = ($_POST['household_members'] ?? '') !== '' ? intval($_POST['household_members']) : null;
$dependents = ($_POST['dependents'] ?? '') !== '' ? intval($_POST['dependents']) : null;
$monthly_income = ($_POST['monthly_income'] ?? '') !== '' ? floatval($_POST['monthly_income']) : null;
$income_source = trim($_POST['income_source'] ?? '');
$beneficiary_type = trim($_POST['beneficiary_type'] ?? '');
$assistance_reason = trim($_POST['assistance_reason'] ?? '');
$supporting_documents = trim($_POST['supporting_documents'] ?? '');
$already_received_payout = trim($_POST['already_received_payout'] ?? 'No');
$receiving_other_assistance = trim($_POST['receiving_other_assistance'] ?? 'No');
$eligibility_status = trim($_POST['eligibility_status'] ?? 'Pending Review');
$remarks = trim($_POST['remarks'] ?? '');
$verified_by = intval($_SESSION['user_id'] ?? 0);

if (!in_array($matches_masterlist, ['Yes', 'No'])) $matches_masterlist = 'No';
if (!in_array($already_received_payout, ['Yes', 'No'])) $already_received_payout = 'No';
if (!in_array($receiving_other_assistance, ['Yes', 'No'])) $receiving_other_assistance = 'No';
if (!in_array($eligibility_status, ['Eligible', 'Not Eligible', 'Pending Review'])) $eligibility_status = 'Pending Review';

$check = $conn->prepare('SELECT id FROM eligibility_forms WHERE beneficiary_id = ? LIMIT 1');
$check->bind_param('i', $beneficiary_id);
$check->execute();
$result = $check->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $form_id = intval($row['id']);

    $stmt = $conn->prepare("UPDATE eligibility_forms SET id_type_presented=?, id_reference_note=?, matches_masterlist=?, household_members=?, dependents=?, monthly_income=?, income_source=?, beneficiary_type=?, assistance_reason=?, supporting_documents=?, already_received_payout=?, receiving_other_assistance=?, eligibility_status=?, remarks=?, verified_by=?, verified_at=NOW() WHERE id=?");
    $stmt->bind_param('sssiidssssssssii', $id_type_presented, $id_reference_note, $matches_masterlist, $household_members, $dependents, $monthly_income, $income_source, $beneficiary_type, $assistance_reason, $supporting_documents, $already_received_payout, $receiving_other_assistance, $eligibility_status, $remarks, $verified_by, $form_id);
} else {
    $stmt = $conn->prepare("INSERT INTO eligibility_forms (beneficiary_id, id_type_presented, id_reference_note, matches_masterlist, household_members, dependents, monthly_income, income_source, beneficiary_type, assistance_reason, supporting_documents, already_received_payout, receiving_other_assistance, eligibility_status, remarks, verified_by, verified_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('isssiidssssssssi', $beneficiary_id, $id_type_presented, $id_reference_note, $matches_masterlist, $household_members, $dependents, $monthly_income, $income_source, $beneficiary_type, $assistance_reason, $supporting_documents, $already_received_payout, $receiving_other_assistance, $eligibility_status, $remarks, $verified_by);
}

if ($stmt->execute()) {
    echo "<script>alert('Eligibility form saved successfully.'); window.location.href='../staff/verifier.php';</script>";
    exit;
}

$error = addslashes($conn->error);
echo "<script>alert('Failed to save eligibility form. Error: {$error}'); window.location.href='../staff/eligibility_form.php?beneficiary_id={$beneficiary_id}';</script>";
exit;
?>
