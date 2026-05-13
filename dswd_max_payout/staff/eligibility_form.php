<?php
include('../auth/check.php');
include('../config/db.php');

$queue_id = intval($_GET['queue_id'] ?? 0);
if ($queue_id <= 0) { header('Location: counter.php'); exit; }

$stmt = $conn->prepare("SELECT q.id AS queue_id,q.queue_number,q.workflow_status,q.beneficiary_id,b.beneficiary_code,b.first_name,b.middle_name,b.last_name,b.ext_name FROM queue_entries q INNER JOIN beneficiaries b ON b.id=q.beneficiary_id WHERE q.id=? LIMIT 1");
$stmt->bind_param('i',$queue_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
if (!$data) { header('Location: counter.php'); exit; }

$stmt = $conn->prepare("SELECT * FROM eligibility_forms WHERE queue_entry_id=? OR beneficiary_id=? ORDER BY id DESC LIMIT 1");
$stmt->bind_param('ii',$queue_id,$data['beneficiary_id']);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc() ?: [];

function h($v){return htmlspecialchars($v ?? '',ENT_QUOTES,'UTF-8');}
function s($f,$k,$v){return (($f[$k] ?? '')===$v)?'selected':'';}
function c($f,$k,$v){return (($f[$k] ?? '')===$v)?'checked':'';}

$is_locked = intval($form['form_locked'] ?? 0) === 1;
$name = trim($data['last_name'].', '.$data['first_name'].' '.$data['middle_name'].' '.$data['ext_name']);
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>GIS Assessment Form</title><link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"><link rel="stylesheet" href="../assets/style.css"><style>
body{background:#f3f6fb}.wrap{padding:20px;flex:1;overflow:auto}.card{background:white;border:1px solid #d6dce8;border-radius:12px;padding:20px;margin-bottom:16px}h1{margin:0;color:#111827}.muted{color:#6b7280}.grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px}.field{display:flex;flex-direction:column}.field label{font-size:13px;font-weight:800;color:#374151;margin-bottom:6px}input,select,textarea{min-height:44px;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px}textarea{min-height:90px;resize:vertical}.full{grid-column:1/-1}.info{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:16px}.info div{background:#f9fafb;border:1px solid #d1d5db;border-radius:10px;padding:12px}.info span{display:block;color:#6b7280;font-size:12px;font-weight:700}.actions{display:flex;gap:10px;margin-top:20px;flex-wrap:wrap}.btn{border:0;text-decoration:none;padding:12px 18px;border-radius:8px;font-weight:800;cursor:pointer}.save{background:#2563eb;color:white}.approve{background:#16a34a;color:white}.back{background:#374151;color:white}.locked{display:inline-block;background:#dcfce7;color:#166534;padding:8px 12px;border-radius:999px;font-weight:800}.unlocked{display:inline-block;background:#fef3c7;color:#92400e;padding:8px 12px;border-radius:999px;font-weight:800}.radio{display:flex;gap:14px;align-items:center;min-height:44px}@media(max-width:800px){.grid,.info{grid-template-columns:1fr}}
</style></head><body><div class="app"><main class="main"><section class="wrap">
<div class="card"><h1>GIS / Assessment Interview Form</h1><p class="muted">Encode interview notes only after the beneficiary is called at Step 2 Assessment.</p><div class="<?php echo $is_locked?'locked':'unlocked'; ?>"><?php echo $is_locked?'Approved and Locked':'Draft / Not yet approved'; ?></div><div class="info"><div><span>Queue Number</span><strong><?php echo h($data['queue_number']); ?></strong></div><div><span>Beneficiary</span><strong><?php echo h($name); ?></strong></div><div><span>Beneficiary Code</span><strong><?php echo h($data['beneficiary_code']); ?></strong></div></div></div>
<form class="card" method="POST" action="../api/save_eligibility.php">
<input type="hidden" name="queue_id" value="<?php echo intval($queue_id); ?>"><input type="hidden" name="beneficiary_id" value="<?php echo intval($data['beneficiary_id']); ?>">
<div class="grid">
<div class="field"><label>ID Type Presented</label><select name="id_type_presented" <?php echo $is_locked?'disabled':''; ?> required><option value="">Select</option><option value="Government ID" <?php echo s($form,'id_type_presented','Government ID'); ?>>Government ID</option><option value="Barangay Certificate" <?php echo s($form,'id_type_presented','Barangay Certificate'); ?>>Barangay Certificate</option><option value="Senior Citizen ID" <?php echo s($form,'id_type_presented','Senior Citizen ID'); ?>>Senior Citizen ID</option><option value="PWD ID" <?php echo s($form,'id_type_presented','PWD ID'); ?>>PWD ID</option><option value="Other" <?php echo s($form,'id_type_presented','Other'); ?>>Other</option></select></div>
<div class="field"><label>Verification Note</label><input type="text" name="id_reference_note" value="<?php echo h($form['id_reference_note'] ?? ''); ?>" <?php echo $is_locked?'disabled':''; ?>></div>
<div class="field full"><label>Information matches masterlist?</label><div class="radio"><label><input type="radio" name="matches_masterlist" value="Yes" <?php echo c($form,'matches_masterlist','Yes'); ?> <?php echo $is_locked?'disabled':''; ?> required> Yes</label><label><input type="radio" name="matches_masterlist" value="No" <?php echo c($form,'matches_masterlist','No'); ?> <?php echo $is_locked?'disabled':''; ?>> No</label></div></div>
<div class="field"><label>Household Members</label><input type="number" name="household_members" value="<?php echo h($form['household_members'] ?? ''); ?>" <?php echo $is_locked?'disabled':''; ?>></div>
<div class="field"><label>Dependents</label><input type="number" name="dependents" value="<?php echo h($form['dependents'] ?? ''); ?>" <?php echo $is_locked?'disabled':''; ?>></div>
<div class="field"><label>Monthly Income</label><input type="number" step="0.01" name="monthly_income" value="<?php echo h($form['monthly_income'] ?? ''); ?>" <?php echo $is_locked?'disabled':''; ?>></div>
<div class="field"><label>Source of Income</label><input type="text" name="income_source" value="<?php echo h($form['income_source'] ?? ''); ?>" <?php echo $is_locked?'disabled':''; ?>></div>
<div class="field"><label>Beneficiary Type</label><select name="beneficiary_type" <?php echo $is_locked?'disabled':''; ?> required><option value="">Select</option><option value="Indigent" <?php echo s($form,'beneficiary_type','Indigent'); ?>>Indigent</option><option value="Senior Citizen" <?php echo s($form,'beneficiary_type','Senior Citizen'); ?>>Senior Citizen</option><option value="PWD" <?php echo s($form,'beneficiary_type','PWD'); ?>>PWD</option><option value="Solo Parent" <?php echo s($form,'beneficiary_type','Solo Parent'); ?>>Solo Parent</option><option value="Medical Assistance" <?php echo s($form,'beneficiary_type','Medical Assistance'); ?>>Medical Assistance</option><option value="Educational Assistance" <?php echo s($form,'beneficiary_type','Educational Assistance'); ?>>Educational Assistance</option><option value="Other" <?php echo s($form,'beneficiary_type','Other'); ?>>Other</option></select></div>
<div class="field"><label>Approved Assistance Cash Amount</label><input type="number" step="0.01" min="0" name="approved_cash_amount" value="<?php echo h($form['approved_cash_amount'] ?? ''); ?>" <?php echo $is_locked?'disabled':''; ?> required></div>
<div class="field"><label>Already Received Payout?</label><select name="already_received_payout" <?php echo $is_locked?'disabled':''; ?>><option value="No" <?php echo s($form,'already_received_payout','No'); ?>>No</option><option value="Yes" <?php echo s($form,'already_received_payout','Yes'); ?>>Yes</option></select></div>
<div class="field"><label>Final Decision</label><select name="eligibility_status" <?php echo $is_locked?'disabled':''; ?> required><option value="Pending Review" <?php echo s($form,'eligibility_status','Pending Review'); ?>>Pending Review</option><option value="Eligible" <?php echo s($form,'eligibility_status','Eligible'); ?>>Eligible</option><option value="Not Eligible" <?php echo s($form,'eligibility_status','Not Eligible'); ?>>Not Eligible</option></select></div>
<div class="field full"><label>Interview Notes / Reason for Assistance</label><textarea name="assistance_reason" <?php echo $is_locked?'disabled':''; ?> required><?php echo h($form['assistance_reason'] ?? ''); ?></textarea></div>
<div class="field full"><label>Supporting Documents Presented</label><textarea name="supporting_documents" <?php echo $is_locked?'disabled':''; ?>><?php echo h($form['supporting_documents'] ?? ''); ?></textarea></div>
<div class="field full"><label>Remarks</label><textarea name="remarks" <?php echo $is_locked?'disabled':''; ?>><?php echo h($form['remarks'] ?? ''); ?></textarea></div>
</div><div class="actions"><?php if(!$is_locked): ?><button class="btn save" type="submit" name="action" value="save">Save Draft</button><button class="btn approve" type="submit" name="action" value="approve" onclick="return confirm('Approve assistance and lock this form?');">Approve Assistance / Lock Form</button><?php endif; ?><a class="btn back" href="counter.php">Back to Counter List</a></div>
</form></section></main><?php include('sidebar.php'); ?></div></body></html>
