<?php
include('../auth/check.php');
include('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../staff/assessment_screen.php');
    exit;
}

$queue_id = intval($_POST['queue_id'] ?? 0);
$beneficiary_id = intval($_POST['beneficiary_id'] ?? 0);

if ($queue_id <= 0 || $beneficiary_id <= 0) {
    header('Location: ../staff/assessment_screen.php');
    exit;
}

function goBack($queue_id) {
    header('Location: ../staff/eligibility_form.php?queue_id=' . intval($queue_id));
    exit;
}

$action = $_POST['action'] ?? 'save';
$form_locked = ($action === 'approve') ? 1 : 0;

$id_type_presented = trim($_POST['id_type_presented'] ?? '');
$id_reference_note = trim($_POST['id_reference_note'] ?? '');
$matches_masterlist = trim($_POST['matches_masterlist'] ?? 'No');
$household_members = ($_POST['household_members'] ?? '') !== '' ? intval($_POST['household_members']) : null;
$dependents = ($_POST['dependents'] ?? '') !== '' ? intval($_POST['dependents']) : null;
$monthly_income = ($_POST['monthly_income'] ?? '') !== '' ? floatval($_POST['monthly_income']) : 0;
$income_source = trim($_POST['income_source'] ?? '');
$beneficiary_type = trim($_POST['beneficiary_type'] ?? '');
$assistance_reason = trim($_POST['assistance_reason'] ?? '');
$supporting_documents = trim($_POST['supporting_documents'] ?? '');
$already_received_payout = trim($_POST['already_received_payout'] ?? 'No');
$receiving_other_assistance = trim($_POST['receiving_other_assistance'] ?? 'No');
$eligibility_status = trim($_POST['eligibility_status'] ?? 'Pending Review');
$approved_cash_amount = ($_POST['approved_cash_amount'] ?? '') !== '' ? floatval($_POST['approved_cash_amount']) : 0;
$verified_by = intval($_SESSION['user_id'] ?? 0);
$charter = $_POST['charter'] ?? [];
if (!is_array($charter)) $charter = [];
$remarks = json_encode($charter, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if (!in_array($matches_masterlist, ['Yes', 'No'])) $matches_masterlist = 'No';
if (!in_array($already_received_payout, ['Yes', 'No'])) $already_received_payout = 'No';
if (!in_array($receiving_other_assistance, ['Yes', 'No'])) $receiving_other_assistance = 'No';
if (!in_array($eligibility_status, ['Eligible', 'Not Eligible', 'Pending Review'])) $eligibility_status = 'Pending Review';

$assistance_mode = trim($charter['assistance_mode'] ?? '');
$canApproveWithoutCash = in_array($assistance_mode, ['Material Assistance','Guarantee Letter','Referral','Disapproval Letter'], true);
if ($form_locked === 1 && $eligibility_status === 'Eligible' && $approved_cash_amount <= 0 && !$canApproveWithoutCash) {
    goBack($queue_id);
}
if ($form_locked === 1 && $eligibility_status === 'Pending Review') {
    goBack($queue_id);
}

$check = $conn->prepare("SELECT id, form_locked FROM eligibility_forms WHERE queue_entry_id = ? OR beneficiary_id = ? ORDER BY id DESC LIMIT 1");
$check->bind_param('ii', $queue_id, $beneficiary_id);
$check->execute();
$result = $check->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();

    if (intval($row['form_locked']) === 1) {
        goBack($queue_id);
    }

    $form_id = intval($row['id']);

    $stmt = $conn->prepare("UPDATE eligibility_forms SET queue_entry_id=?, id_type_presented=?, id_reference_note=?, matches_masterlist=?, household_members=?, dependents=?, monthly_income=?, income_source=?, beneficiary_type=?, assistance_reason=?, supporting_documents=?, already_received_payout=?, receiving_other_assistance=?, eligibility_status=?, approved_cash_amount=?, form_locked=?, approved_at=IF(?=1,NOW(),approved_at), remarks=?, verified_by=?, verified_at=NOW() WHERE id=?");

    $stmt->bind_param(
        'isssiidsssssssdiisii',
        $queue_id,
        $id_type_presented,
        $id_reference_note,
        $matches_masterlist,
        $household_members,
        $dependents,
        $monthly_income,
        $income_source,
        $beneficiary_type,
        $assistance_reason,
        $supporting_documents,
        $already_received_payout,
        $receiving_other_assistance,
        $eligibility_status,
        $approved_cash_amount,
        $form_locked,
        $form_locked,
        $remarks,
        $verified_by,
        $form_id
    );
} else {
    $stmt = $conn->prepare("INSERT INTO eligibility_forms (beneficiary_id, queue_entry_id, id_type_presented, id_reference_note, matches_masterlist, household_members, dependents, monthly_income, income_source, beneficiary_type, assistance_reason, supporting_documents, already_received_payout, receiving_other_assistance, eligibility_status, approved_cash_amount, form_locked, approved_at, remarks, verified_by, verified_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, IF(?=1,NOW(),NULL), ?, ?, NOW())");

    $stmt->bind_param(
        'iisssiidsssssssdiisi',
        $beneficiary_id,
        $queue_id,
        $id_type_presented,
        $id_reference_note,
        $matches_masterlist,
        $household_members,
        $dependents,
        $monthly_income,
        $income_source,
        $beneficiary_type,
        $assistance_reason,
        $supporting_documents,
        $already_received_payout,
        $receiving_other_assistance,
        $eligibility_status,
        $approved_cash_amount,
        $form_locked,
        $form_locked,
        $remarks,
        $verified_by
    );
}

if ($stmt->execute()) {
    header('Location: ../staff/assessment_screen.php');
    exit;
}

goBack($queue_id);
?>
