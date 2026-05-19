<?php
include('../auth/check.php');
include('../config/db.php');

date_default_timezone_set('Asia/Manila');

function one($conn, $sql) {
    $result = $conn->query($sql);
    if (!$result) return 0;
    $row = $result->fetch_assoc();
    return intval($row['total'] ?? 0);
}
function money($v) { return '₱' . number_format(floatval($v), 2); }
function percent($part, $total) { return $total > 0 ? round(($part / $total) * 100, 2) : 0; }
function rows($conn, $sql) {
    $out = [];
    $res = $conn->query($sql);
    if ($res) while($r=$res->fetch_assoc()) $out[] = $r;
    return $out;
}

$today = date('M d, Y');
$totalQueues = one($conn, "SELECT COUNT(*) AS total FROM queue_entries WHERE DATE(transaction_date)=CURDATE()");
$waitingAssessment = one($conn, "SELECT COUNT(*) AS total FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND workflow_status='WAITING_STEP_2'");
$calledAssessment = one($conn, "SELECT COUNT(*) AS total FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND workflow_status='CALLED_STEP_2'");
$waitingRelease = one($conn, "SELECT COUNT(*) AS total FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND workflow_status='WAITING_STEP_3'");
$calledRelease = one($conn, "SELECT COUNT(*) AS total FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND workflow_status='CALLED_STEP_3'");
$heldQueues = one($conn, "SELECT COUNT(*) AS total FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND workflow_status='HELD'");
$paidQueues = one($conn, "SELECT COUNT(*) AS total FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND workflow_status='PAID'");
$cancelledQueues = one($conn, "SELECT COUNT(*) AS total FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND workflow_status='CANCELLED'");
$activeQueues = $waitingAssessment + $calledAssessment + $waitingRelease + $calledRelease + $heldQueues;
$priorityQueues = one($conn, "SELECT COUNT(*) AS total FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND queue_type='priority'");
$regularQueues = one($conn, "SELECT COUNT(*) AS total FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND queue_type='regular'");
$totalBeneficiaries = one($conn, "SELECT COUNT(*) AS total FROM beneficiaries");
$todayBeneficiaries = one($conn, "SELECT COUNT(DISTINCT beneficiary_id) AS total FROM queue_entries WHERE DATE(transaction_date)=CURDATE()");

$gisDrafts = one($conn, "SELECT COUNT(*) AS total FROM eligibility_forms e JOIN queue_entries q ON q.id=e.queue_entry_id WHERE DATE(q.transaction_date)=CURDATE() AND e.form_locked=0");
$gisLocked = one($conn, "SELECT COUNT(*) AS total FROM eligibility_forms e JOIN queue_entries q ON q.id=e.queue_entry_id WHERE DATE(q.transaction_date)=CURDATE() AND e.form_locked=1");
$gisEligible = one($conn, "SELECT COUNT(*) AS total FROM eligibility_forms e JOIN queue_entries q ON q.id=e.queue_entry_id WHERE DATE(q.transaction_date)=CURDATE() AND e.form_locked=1 AND e.eligibility_status='Eligible'");
$gisNotEligible = one($conn, "SELECT COUNT(*) AS total FROM eligibility_forms e JOIN queue_entries q ON q.id=e.queue_entry_id WHERE DATE(q.transaction_date)=CURDATE() AND e.form_locked=1 AND e.eligibility_status='Not Eligible'");
$approvedAmount = rows($conn, "SELECT IFNULL(SUM(e.approved_cash_amount),0) AS total_amount FROM eligibility_forms e JOIN queue_entries q ON q.id=e.queue_entry_id WHERE DATE(q.transaction_date)=CURDATE() AND e.form_locked=1 AND e.eligibility_status='Eligible'");
$totalApprovedAmount = floatval($approvedAmount[0]['total_amount'] ?? 0);

$pwd = one($conn, "SELECT COUNT(*) AS total FROM beneficiaries WHERE sms_opt_in=1");
$pregnant = one($conn, "SELECT COUNT(*) AS total FROM beneficiaries WHERE is_pregnant=1");
$seniorsOnly = one($conn, "SELECT COUNT(*) AS total FROM beneficiaries WHERE age>=60 AND sms_opt_in=0 AND is_pregnant=0");
$regularCategory = one($conn, "SELECT COUNT(*) AS total FROM beneficiaries WHERE age<60 AND sms_opt_in=0 AND is_pregnant=0");
$pwdQueues = one($conn, "SELECT COUNT(*) AS total FROM queue_entries q JOIN beneficiaries b ON b.id=q.beneficiary_id WHERE DATE(q.transaction_date)=CURDATE() AND b.sms_opt_in=1");
$pregnantQueues = one($conn, "SELECT COUNT(*) AS total FROM queue_entries q JOIN beneficiaries b ON b.id=q.beneficiary_id WHERE DATE(q.transaction_date)=CURDATE() AND b.is_pregnant=1");
$seniorQueues = one($conn, "SELECT COUNT(*) AS total FROM queue_entries q JOIN beneficiaries b ON b.id=q.beneficiary_id WHERE DATE(q.transaction_date)=CURDATE() AND b.age>=60 AND b.sms_opt_in=0 AND b.is_pregnant=0");
$regularOnlyQueues = one($conn, "SELECT COUNT(*) AS total FROM queue_entries q JOIN beneficiaries b ON b.id=q.beneficiary_id WHERE DATE(q.transaction_date)=CURDATE() AND b.age<60 AND b.sms_opt_in=0 AND b.is_pregnant=0");

$serviceRows = rows($conn, "SELECT CASE WHEN queue_number LIKE 'MEDI-%' THEN 'MEDI - Medical' WHEN queue_number LIKE 'FNRL-%' THEN 'FNRL - Funeral' WHEN queue_number LIKE 'EDUC-%' THEN 'EDUC - Educational' WHEN queue_number LIKE 'TRAN-%' THEN 'TRAN - Transportation' WHEN queue_number LIKE 'MTRL-%' THEN 'MTRL - Material' WHEN queue_number LIKE 'FDAS-%' THEN 'FDAS - Food' WHEN queue_number LIKE 'CRAS-%' THEN 'CRAS - Cash Relief' WHEN queue_number LIKE 'PRIO-%' THEN 'PRIO - Priority' ELSE 'Legacy / Other' END AS service, COUNT(*) AS total FROM queue_entries WHERE DATE(transaction_date)=CURDATE() GROUP BY service ORDER BY FIELD(service,'MEDI - Medical','FNRL - Funeral','EDUC - Educational','TRAN - Transportation','MTRL - Material','FDAS - Food','CRAS - Cash Relief','PRIO - Priority','Legacy / Other')");
$statusRows = [
    ['label'=>'Waiting Assessment', 'value'=>$waitingAssessment, 'class'=>'blue'],
    ['label'=>'Called Assessment', 'value'=>$calledAssessment, 'class'=>'orange'],
    ['label'=>'Waiting Release', 'value'=>$waitingRelease, 'class'=>'purple'],
    ['label'=>'Called Release', 'value'=>$calledRelease, 'class'=>'pink'],
    ['label'=>'Held', 'value'=>$heldQueues, 'class'=>'slate'],
    ['label'=>'Paid', 'value'=>$paidQueues, 'class'=>'green'],
    ['label'=>'Cancelled', 'value'=>$cancelledQueues, 'class'=>'red']
];

$paidPercent = percent($paidQueues, $totalQueues);
$activePercent = percent($activeQueues, $totalQueues);
$priorityPercent = percent($priorityQueues, $totalQueues);
$regularPercent = percent($regularQueues, $totalQueues);
$gisLockPercent = percent($gisLocked, $totalQueues);
$pwdPercent = percent($pwd, $totalBeneficiaries);
$pregnantPercent = percent($pregnant, $totalBeneficiaries);
$seniorPercent = percent($seniorsOnly, $totalBeneficiaries);
$regularCategoryPercent = percent($regularCategory, $totalBeneficiaries);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Analytics</title>
<link rel="stylesheet" href="../assets/style.css">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<style>
.content{overflow:auto}.analytics-header{background:linear-gradient(135deg,#0b2e83,#168fcb);color:white;padding:24px;margin:-22px -22px 22px -22px;display:flex;justify-content:space-between;align-items:center;gap:16px}.analytics-header h1{margin:0;font-size:30px}.analytics-header p{margin:6px 0 0;opacity:.9}.refresh-btn{height:44px;border:0;border-radius:10px;background:rgba(255,255,255,.18);color:white;font-weight:900;padding:0 14px;display:inline-flex;align-items:center;gap:7px;cursor:pointer}.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px}.card{background:white;border:1px solid #d6dce8;border-radius:12px;padding:18px}.card span{display:block;color:#64748b;font-size:12px;font-weight:900;text-transform:uppercase}.card strong{display:block;font-size:32px;margin-top:8px;color:#0f172a}.card small{display:block;margin-top:6px;color:#64748b;font-weight:800}.section{background:white;border:1px solid #d6dce8;border-radius:12px;padding:20px;margin-bottom:14px}.section h2{margin:0 0 14px;color:#0f172a}.bar-card{margin-bottom:13px}.bar-title{display:flex;justify-content:space-between;font-weight:900;margin-bottom:8px}.bar{height:18px;background:#e5e7eb;border-radius:999px;overflow:hidden}.fill{height:100%;background:#168fcb;border-radius:999px}.orange{background:#f97316}.green{background:#16a34a}.purple{background:#7c3aed}.blue{background:#2563eb}.pink{background:#db2777}.slate{background:#475569}.red{background:#dc2626}.mini-table{width:100%;border-collapse:collapse}.mini-table th,.mini-table td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left}.mini-table th{background:#f8fafc;text-transform:uppercase;font-size:12px}.two-col{display:grid;grid-template-columns:1fr 1fr;gap:14px}.auto-note{font-size:12px;color:#64748b;margin-top:12px;text-align:right}@media(max-width:1000px){.cards{grid-template-columns:repeat(2,1fr)}.two-col{grid-template-columns:1fr}}@media(max-width:650px){.cards{grid-template-columns:1fr}.analytics-header{flex-direction:column;align-items:flex-start}}
</style>
</head>
<body>
<div class="app"><main class="main"><section class="content">
<div class="analytics-header"><div><h1>Analytics</h1><p>Current system state for <?php echo htmlspecialchars($today); ?>: queues, payouts, GIS, assistance types, and priority categories.</p></div><button class="refresh-btn" onclick="location.reload()"><span class="material-icons">refresh</span>Refresh</button></div>
<div class="cards">
<div class="card"><span>Total Queues Today</span><strong><?php echo $totalQueues; ?></strong><small><?php echo $todayBeneficiaries; ?> unique clients today</small></div>
<div class="card"><span>Active Queues</span><strong><?php echo $activeQueues; ?></strong><small><?php echo $activePercent; ?>% still active</small></div>
<div class="card"><span>Paid / Released Today</span><strong><?php echo $paidQueues; ?></strong><small><?php echo $paidPercent; ?>% completion</small></div>
<div class="card"><span>Approved Amount</span><strong style="font-size:26px"><?php echo money($totalApprovedAmount); ?></strong><small>locked eligible GIS/CE</small></div>
<div class="card"><span>Priority Queues</span><strong><?php echo $priorityQueues; ?></strong><small><?php echo $priorityPercent; ?>% today</small></div>
<div class="card"><span>Regular Queues</span><strong><?php echo $regularQueues; ?></strong><small><?php echo $regularPercent; ?>% today</small></div>
<div class="card"><span>GIS Locked</span><strong><?php echo $gisLocked; ?></strong><small><?php echo $gisLockPercent; ?>% of queues</small></div>
<div class="card"><span>GIS Drafts</span><strong><?php echo $gisDrafts; ?></strong><small><?php echo $gisEligible; ?> eligible / <?php echo $gisNotEligible; ?> not eligible</small></div>
</div>
<div class="two-col">
<div class="section"><h2>Workflow State</h2><?php foreach($statusRows as $row): $pct=percent($row['value'],$totalQueues); ?><div class="bar-card"><div class="bar-title"><span><?php echo htmlspecialchars($row['label']); ?></span><span><?php echo intval($row['value']); ?> · <?php echo $pct; ?>%</span></div><div class="bar"><div class="fill <?php echo $row['class']; ?>" style="width:<?php echo min(100,$pct); ?>%"></div></div></div><?php endforeach; ?></div>
<div class="section"><h2>Queue Performance</h2>
<div class="bar-card"><div class="bar-title"><span>Paid Completion</span><span><?php echo $paidPercent; ?>%</span></div><div class="bar"><div class="fill green" style="width:<?php echo min(100,$paidPercent); ?>%"></div></div></div>
<div class="bar-card"><div class="bar-title"><span>Active Queue Share</span><span><?php echo $activePercent; ?>%</span></div><div class="bar"><div class="fill blue" style="width:<?php echo min(100,$activePercent); ?>%"></div></div></div>
<div class="bar-card"><div class="bar-title"><span>Priority Queue Share</span><span><?php echo $priorityPercent; ?>%</span></div><div class="bar"><div class="fill orange" style="width:<?php echo min(100,$priorityPercent); ?>%"></div></div></div>
<div class="bar-card"><div class="bar-title"><span>Regular Queue Share</span><span><?php echo $regularPercent; ?>%</span></div><div class="bar"><div class="fill purple" style="width:<?php echo min(100,$regularPercent); ?>%"></div></div></div>
</div></div>
<div class="section"><h2>Assistance Type Queue Counts</h2><table class="mini-table"><thead><tr><th>Assistance Type</th><th>Queues Today</th><th>Share</th></tr></thead><tbody><?php if(count($serviceRows)>0): foreach($serviceRows as $r): $pct=percent($r['total'],$totalQueues); ?><tr><td><?php echo htmlspecialchars($r['service']); ?></td><td><?php echo intval($r['total']); ?></td><td><?php echo $pct; ?>%</td></tr><?php endforeach; else: ?><tr><td colspan="3">No queues yet today.</td></tr><?php endif; ?></tbody></table></div>
<div class="section"><h2>Beneficiary Category Segregation</h2><table class="mini-table"><thead><tr><th>Category</th><th>Total Beneficiaries</th><th>Share</th><th>Queues Today</th></tr></thead><tbody><tr><td>PWD</td><td><?php echo $pwd; ?></td><td><?php echo $pwdPercent; ?>%</td><td><?php echo $pwdQueues; ?></td></tr><tr><td>Pregnant</td><td><?php echo $pregnant; ?></td><td><?php echo $pregnantPercent; ?>%</td><td><?php echo $pregnantQueues; ?></td></tr><tr><td>Senior only</td><td><?php echo $seniorsOnly; ?></td><td><?php echo $seniorPercent; ?>%</td><td><?php echo $seniorQueues; ?></td></tr><tr><td>Other / Regular</td><td><?php echo $regularCategory; ?></td><td><?php echo $regularCategoryPercent; ?>%</td><td><?php echo $regularOnlyQueues; ?></td></tr></tbody></table></div>
<div class="section"><h2>Category Percentage Bars</h2><div class="bar-card"><div class="bar-title"><span>PWD</span><span><?php echo $pwdPercent; ?>%</span></div><div class="bar"><div class="fill purple" style="width:<?php echo min(100,$pwdPercent); ?>%"></div></div></div><div class="bar-card"><div class="bar-title"><span>Pregnant</span><span><?php echo $pregnantPercent; ?>%</span></div><div class="bar"><div class="fill pink" style="width:<?php echo min(100,$pregnantPercent); ?>%"></div></div></div><div class="bar-card"><div class="bar-title"><span>Senior only</span><span><?php echo $seniorPercent; ?>%</span></div><div class="bar"><div class="fill" style="width:<?php echo min(100,$seniorPercent); ?>%"></div></div></div><div class="bar-card"><div class="bar-title"><span>Other / Regular</span><span><?php echo $regularCategoryPercent; ?>%</span></div><div class="bar"><div class="fill slate" style="width:<?php echo min(100,$regularCategoryPercent); ?>%"></div></div></div></div>
<div class="auto-note">Auto-refreshes every 15 seconds. Last loaded: <?php echo date('h:i:s A'); ?></div>
</section></main><?php include('sidebar.php'); ?></div>
<script>setTimeout(function(){ location.reload(); }, 15000);</script>
</body>
</html>