<?php
include('../auth/check.php');
include('../config/db.php');

$beneficiary_id = intval($_GET['beneficiary_id'] ?? 0);
if ($beneficiary_id <= 0) {
    header('Location: verifier.php');
    exit;
}

$stmt = $conn->prepare('SELECT id, beneficiary_code, first_name, middle_name, last_name, ext_name FROM beneficiaries WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $beneficiary_id);
$stmt->execute();
$beneficiary = $stmt->get_result()->fetch_assoc();

if (!$beneficiary) {
    header('Location: verifier.php');
    exit;
}

$stmt = $conn->prepare('SELECT * FROM eligibility_forms WHERE beneficiary_id = ? LIMIT 1');
$stmt->bind_param('i', $beneficiary_id);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc() ?: [];

function h($value) { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function sel($form, $field, $value) { return (($form[$field] ?? '') === $value) ? 'selected' : ''; }
function chk($form, $field, $value) { return (($form[$field] ?? '') === $value) ? 'checked' : ''; }

$name = trim($beneficiary['last_name'] . ', ' . $beneficiary['first_name'] . ' ' . $beneficiary['middle_name'] . ' ' . $beneficiary['ext_name']);
$status = $form['eligibility_status'] ?? 'Pending Review';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Eligibility Form</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body { background:#f3f6fb; }
        .content-box { padding:20px; flex:1; overflow:auto; }
        .card { background:#fff; border:1px solid #d6dce8; border-radius:12px; padding:20px; margin-bottom:16px; }
        h1 { margin:0; color:#111827; }
        .muted { color:#6b7280; margin-top:6px; }
        .info { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-top:16px; }
        .info div { background:#f9fafb; border:1px solid #d1d5db; border-radius:10px; padding:12px; }
        .info span { display:block; color:#6b7280; font-size:12px; font-weight:700; margin-bottom:4px; }
        .pill { display:inline-block; margin-top:12px; padding:8px 12px; border-radius:999px; background:#fef3c7; color:#92400e; font-weight:800; }
        .grid { display:grid; grid-template-columns:repeat(2,1fr); gap:14px; }
        .field { display:flex; flex-direction:column; }
        .field label { font-size:13px; font-weight:800; color:#374151; margin-bottom:6px; }
        input, select, textarea { min-height:44px; padding:10px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:14px; }
        textarea { min-height:90px; resize:vertical; }
        .full { grid-column:1/-1; }
        .radio-row { display:flex; gap:14px; align-items:center; min-height:44px; }
        .actions { display:flex; gap:10px; margin-top:22px; }
        .save { background:#16a34a; color:white; border:0; padding:12px 18px; border-radius:8px; font-weight:800; cursor:pointer; }
        .back { background:#374151; color:white; text-decoration:none; padding:12px 18px; border-radius:8px; font-weight:800; }
    </style>
</head>
<body>
<div class="app">
<main class="main">
<section class="content-box">
    <div class="card">
        <h1>Eligibility / Client Intake Form</h1>
        <p class="muted">Complete this before generating a PAL or PRIO queue number.</p>
        <div class="pill">Current Status: <?php echo h($status); ?></div>
        <div class="info">
            <div><span>Beneficiary Code</span><strong><?php echo h($beneficiary['beneficiary_code']); ?></strong></div>
            <div><span>Name</span><strong><?php echo h($name); ?></strong></div>
            <div><span>Record ID</span><strong><?php echo h($beneficiary['id']); ?></strong></div>
        </div>
    </div>

    <form method="POST" action="../api/save_eligibility.php" class="card">
        <input type="hidden" name="beneficiary_id" value="<?php echo intval($beneficiary_id); ?>">
        <div class="grid">
            <div class="field">
                <label>ID Type Presented</label>
                <select name="id_type_presented" required>
                    <option value="">Select</option>
                    <option value="Government ID" <?php echo sel($form,'id_type_presented','Government ID'); ?>>Government ID</option>
                    <option value="Barangay Certificate" <?php echo sel($form,'id_type_presented','Barangay Certificate'); ?>>Barangay Certificate</option>
                    <option value="Senior Citizen ID" <?php echo sel($form,'id_type_presented','Senior Citizen ID'); ?>>Senior Citizen ID</option>
                    <option value="PWD ID" <?php echo sel($form,'id_type_presented','PWD ID'); ?>>PWD ID</option>
                    <option value="Other" <?php echo sel($form,'id_type_presented','Other'); ?>>Other</option>
                </select>
            </div>
            <div class="field">
                <label>Verification Note</label>
                <input type="text" name="id_reference_note" value="<?php echo h($form['id_reference_note'] ?? ''); ?>" placeholder="Example: ID checked">
            </div>
            <div class="field full">
                <label>Information matches masterlist?</label>
                <div class="radio-row">
                    <label><input type="radio" name="matches_masterlist" value="Yes" <?php echo chk($form,'matches_masterlist','Yes'); ?> required> Yes</label>
                    <label><input type="radio" name="matches_masterlist" value="No" <?php echo chk($form,'matches_masterlist','No'); ?>> No</label>
                </div>
            </div>
            <div class="field"><label>Household Members</label><input type="number" name="household_members" value="<?php echo h($form['household_members'] ?? ''); ?>"></div>
            <div class="field"><label>Dependents</label><input type="number" name="dependents" value="<?php echo h($form['dependents'] ?? ''); ?>"></div>
            <div class="field"><label>Monthly Income</label><input type="number" step="0.01" name="monthly_income" value="<?php echo h($form['monthly_income'] ?? ''); ?>"></div>
            <div class="field"><label>Source of Income</label><input type="text" name="income_source" value="<?php echo h($form['income_source'] ?? ''); ?>"></div>
            <div class="field">
                <label>Beneficiary Type</label>
                <select name="beneficiary_type" required>
                    <option value="">Select</option>
                    <option value="Indigent" <?php echo sel($form,'beneficiary_type','Indigent'); ?>>Indigent</option>
                    <option value="Senior Citizen" <?php echo sel($form,'beneficiary_type','Senior Citizen'); ?>>Senior Citizen</option>
                    <option value="PWD" <?php echo sel($form,'beneficiary_type','PWD'); ?>>PWD</option>
                    <option value="Solo Parent" <?php echo sel($form,'beneficiary_type','Solo Parent'); ?>>Solo Parent</option>
                    <option value="Medical Assistance" <?php echo sel($form,'beneficiary_type','Medical Assistance'); ?>>Medical Assistance</option>
                    <option value="Educational Assistance" <?php echo sel($form,'beneficiary_type','Educational Assistance'); ?>>Educational Assistance</option>
                    <option value="Other" <?php echo sel($form,'beneficiary_type','Other'); ?>>Other</option>
                </select>
            </div>
            <div class="field"><label>Already Received Payout?</label><select name="already_received_payout"><option value="No" <?php echo sel($form,'already_received_payout','No'); ?>>No</option><option value="Yes" <?php echo sel($form,'already_received_payout','Yes'); ?>>Yes</option></select></div>
            <div class="field"><label>Receiving Other Assistance?</label><select name="receiving_other_assistance"><option value="No" <?php echo sel($form,'receiving_other_assistance','No'); ?>>No</option><option value="Yes" <?php echo sel($form,'receiving_other_assistance','Yes'); ?>>Yes</option></select></div>
            <div class="field"><label>Eligibility Decision</label><select name="eligibility_status" required><option value="Pending Review" <?php echo sel($form,'eligibility_status','Pending Review'); ?>>Pending Review</option><option value="Eligible" <?php echo sel($form,'eligibility_status','Eligible'); ?>>Eligible</option><option value="Not Eligible" <?php echo sel($form,'eligibility_status','Not Eligible'); ?>>Not Eligible</option></select></div>
            <div class="field full"><label>Reason for Assistance</label><textarea name="assistance_reason" required><?php echo h($form['assistance_reason'] ?? ''); ?></textarea></div>
            <div class="field full"><label>Supporting Documents</label><textarea name="supporting_documents"><?php echo h($form['supporting_documents'] ?? ''); ?></textarea></div>
            <div class="field full"><label>Remarks</label><textarea name="remarks"><?php echo h($form['remarks'] ?? ''); ?></textarea></div>
        </div>
        <div class="actions">
            <button class="save" type="submit">Save Eligibility Form</button>
            <a class="back" href="verifier.php">Back to Verifier</a>
        </div>
    </form>
</section>
</main>
<?php include('sidebar.php'); ?>
</div>
</body>
</html>
