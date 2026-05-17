<?php
include('../auth/check.php');
include('../config/db.php');

$sql = "
    SELECT q.queue_number, q.counter_number, q.table_number, q.workflow_status, q.called_at,
           b.first_name, b.middle_name, b.last_name, b.ext_name
    FROM queue_entries q
    JOIN beneficiaries b ON b.id = q.beneficiary_id
    WHERE DATE(q.transaction_date) = CURDATE()
      AND q.workflow_status IN ('CALLED_STEP_2','CALLED_STEP_3')
    ORDER BY q.called_at DESC, q.id DESC
    LIMIT 8
";
$result = $conn->query($sql);

function h($v){return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');}
function fullName($r){return trim(($r['last_name'] ?? '').', '.($r['first_name'] ?? '').' '.($r['middle_name'] ?? '').' '.($r['ext_name'] ?? ''));}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Counter Display</title>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<style>
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#081f4d;color:#fff;min-height:100vh;overflow:hidden}.screen{min-height:100vh;padding:28px;background:linear-gradient(135deg,#061a42,#0b2e83 55%,#168fcb)}.header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px}.title h1{margin:0;font-size:44px;letter-spacing:.5px}.title p{margin:6px 0 0;font-size:18px;opacity:.88}.clock{text-align:right;font-weight:900}.clock .time{font-size:42px}.clock .date{font-size:16px;opacity:.85}.now-card{background:rgba(255,255,255,.96);color:#0f172a;border-radius:28px;padding:34px;box-shadow:0 20px 60px rgba(0,0,0,.28);margin-bottom:22px}.now-label{font-size:22px;font-weight:900;color:#0b2e83;text-transform:uppercase;letter-spacing:1px}.now-row{display:grid;grid-template-columns:1.4fr 1fr;gap:22px;align-items:center;margin-top:12px}.queue-big{font-size:88px;font-weight:1000;line-height:.95;color:#c2410c}.counter-big{font-size:62px;font-weight:1000;color:#0b2e83;text-align:right}.name{font-size:22px;color:#475569;font-weight:800;margin-top:12px}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}.mini{background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.26);border-radius:18px;padding:18px;min-height:148px;backdrop-filter:blur(6px)}.mini span{display:block;opacity:.8;font-size:12px;font-weight:900;text-transform:uppercase}.mini strong{display:block;font-size:34px;margin-top:8px}.mini b{display:block;font-size:18px;margin-top:8px;color:#dbeafe}.empty{height:55vh;display:flex;align-items:center;justify-content:center;text-align:center;font-size:34px;font-weight:900;opacity:.85}.footer{position:fixed;left:28px;right:28px;bottom:18px;font-size:16px;opacity:.9;display:flex;justify-content:space-between}@media(max-width:1000px){.grid{grid-template-columns:repeat(2,1fr)}.queue-big{font-size:62px}.counter-big{font-size:44px}.title h1{font-size:34px}}
</style>
</head>
<body>
<div class="screen">
    <div class="header">
        <div class="title"><h1>DSWD Queue Display</h1><p>Now Serving / Counter Monitor</p></div>
        <div class="clock"><div class="time" id="time">--:--</div><div class="date" id="date">Loading...</div></div>
    </div>

    <?php
    $rows = [];
    if ($result) while($r = $result->fetch_assoc()) $rows[] = $r;
    $now = $rows[0] ?? null;
    ?>

    <?php if($now): ?>
    <div class="now-card">
        <div class="now-label">Now Serving</div>
        <div class="now-row">
            <div>
                <div class="queue-big"><?php echo h($now['queue_number']); ?></div>
                <div class="name"><?php echo h(fullName($now)); ?></div>
            </div>
            <div class="counter-big">COUNTER <?php echo intval($now['counter_number'] ?: $now['table_number'] ?: 1); ?></div>
        </div>
    </div>
    <div class="grid">
        <?php foreach(array_slice($rows,1,8) as $r): ?>
        <div class="mini"><span>Recently Called</span><strong><?php echo h($r['queue_number']); ?></strong><b>Counter <?php echo intval($r['counter_number'] ?: $r['table_number'] ?: 1); ?></b></div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty">No queue currently called.</div>
    <?php endif; ?>
</div>
<div class="footer"><span>Please wait for your queue number to be called.</span><span>Auto-refresh every 5 seconds</span></div>
<script>
function tick(){const d=new Date();document.getElementById('time').textContent=d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});document.getElementById('date').textContent=d.toLocaleDateString([], {weekday:'long',year:'numeric',month:'long',day:'numeric'});}tick();setInterval(tick,1000);setTimeout(()=>location.reload(),5000);
</script>
</body>
</html>
