<?php
include('../auth/check.php');
include('../config/db.php');

function one($conn, $sql) {
    $result = $conn->query($sql);
    if (!$result) return 0;
    $row = $result->fetch_assoc();
    return intval($row['total'] ?? 0);
}

$totalQueues = one($conn, "SELECT COUNT(*) AS total FROM queue_entries WHERE DATE(transaction_date)=CURDATE()");
$activeQueues = one($conn, "SELECT COUNT(*) AS total FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND workflow_status IN ('WAITING_STEP_2','CALLED_STEP_2','WAITING_STEP_3','CALLED_STEP_3')");
$paidQueues = one($conn, "SELECT COUNT(*) AS total FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND workflow_status='PAID'");
$priorityQueues = one($conn, "SELECT COUNT(*) AS total FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND queue_type='priority'");
$regularQueues = one($conn, "SELECT COUNT(*) AS total FROM queue_entries WHERE DATE(transaction_date)=CURDATE() AND queue_type='regular'");
$totalBeneficiaries = one($conn, "SELECT COUNT(*) AS total FROM beneficiaries");
$seniors = one($conn, "SELECT COUNT(*) AS total FROM beneficiaries WHERE age >= 60");
$pwd = one($conn, "SELECT COUNT(*) AS total FROM beneficiaries WHERE sms_opt_in = 1");

$paidPercent = $totalQueues > 0 ? round(($paidQueues / $totalQueues) * 100, 2) : 0;
$priorityPercent = $totalQueues > 0 ? round(($priorityQueues / $totalQueues) * 100, 2) : 0;
$seniorPercent = $totalBeneficiaries > 0 ? round(($seniors / $totalBeneficiaries) * 100, 2) : 0;
$pwdPercent = $totalBeneficiaries > 0 ? round(($pwd / $totalBeneficiaries) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Analytics</title>
<link rel="stylesheet" href="../assets/style.css">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<style>
.content{overflow:auto}.analytics-header{background:linear-gradient(135deg,#0b2e83,#168fcb);color:white;padding:24px;margin:-22px -22px 22px -22px}.analytics-header h1{margin:0;font-size:30px}.analytics-header p{margin:6px 0 0;opacity:.9}.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px}.card{background:white;border:1px solid #d6dce8;border-radius:12px;padding:18px}.card span{display:block;color:#64748b;font-size:12px;font-weight:900;text-transform:uppercase}.card strong{display:block;font-size:32px;margin-top:8px;color:#0f172a}.bar-card{background:white;border:1px solid #d6dce8;border-radius:12px;padding:20px;margin-bottom:14px}.bar-title{display:flex;justify-content:space-between;font-weight:900;margin-bottom:8px}.bar{height:18px;background:#e5e7eb;border-radius:999px;overflow:hidden}.fill{height:100%;background:#168fcb;border-radius:999px}.fill.orange{background:#f97316}.fill.green{background:#16a34a}.fill.purple{background:#7c3aed}@media(max-width:1000px){.cards{grid-template-columns:repeat(2,1fr)}}@media(max-width:650px){.cards{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="app"><main class="main"><section class="content">
<div class="analytics-header"><h1>Analytics</h1><p>Daily payout and beneficiary summary report.</p></div>
<div class="cards">
<div class="card"><span>Total Queues Today</span><strong><?php echo $totalQueues; ?></strong></div>
<div class="card"><span>Active Queues</span><strong><?php echo $activeQueues; ?></strong></div>
<div class="card"><span>Paid Today</span><strong><?php echo $paidQueues; ?></strong></div>
<div class="card"><span>Total Beneficiaries</span><strong><?php echo $totalBeneficiaries; ?></strong></div>
<div class="card"><span>PRIO Queues</span><strong><?php echo $priorityQueues; ?></strong></div>
<div class="card"><span>PAL Queues</span><strong><?php echo $regularQueues; ?></strong></div>
<div class="card"><span>Seniors</span><strong><?php echo $seniors; ?></strong></div>
<div class="card"><span>PWD</span><strong><?php echo $pwd; ?></strong></div>
</div>
<div class="bar-card"><div class="bar-title"><span>Paid Completion</span><span><?php echo $paidPercent; ?>%</span></div><div class="bar"><div class="fill green" style="width:<?php echo min(100,$paidPercent); ?>%"></div></div></div>
<div class="bar-card"><div class="bar-title"><span>Priority Queue Share</span><span><?php echo $priorityPercent; ?>%</span></div><div class="bar"><div class="fill orange" style="width:<?php echo min(100,$priorityPercent); ?>%"></div></div></div>
<div class="bar-card"><div class="bar-title"><span>Senior Beneficiaries</span><span><?php echo $seniorPercent; ?>%</span></div><div class="bar"><div class="fill" style="width:<?php echo min(100,$seniorPercent); ?>%"></div></div></div>
<div class="bar-card"><div class="bar-title"><span>PWD Beneficiaries</span><span><?php echo $pwdPercent; ?>%</span></div><div class="bar"><div class="fill purple" style="width:<?php echo min(100,$pwdPercent); ?>%"></div></div></div>
</section></main><?php include('sidebar.php'); ?></div>
</body>
</html>
