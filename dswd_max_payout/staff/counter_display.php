<?php
include('../auth/check.php');
include('../config/db.php');

function getRows($conn, $status) {
    $stmt = $conn->prepare("SELECT q.queue_number, q.counter_number, q.table_number, q.called_at FROM queue_entries q WHERE DATE(q.transaction_date)=CURDATE() AND q.workflow_status=? ORDER BY q.called_at DESC, q.id DESC LIMIT 8");
    $stmt->bind_param('s', $status);
    $stmt->execute();
    return $stmt->get_result();
}

$assessment = getRows($conn, 'CALLED_STEP_2');
$payout = getRows($conn, 'CALLED_STEP_3');

function h($v){return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');}
function counterNo($r){return intval($r['counter_number'] ?: $r['table_number'] ?: 1);}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Counter Display</title>
<style>
*{box-sizing:border-box}html,body{margin:0;width:100%;height:100%;font-family:Arial,sans-serif;background:#f2f5f9;color:#111827;overflow:hidden}.display{height:100vh;display:grid;grid-template-rows:1fr 46px}.boards{display:grid;grid-template-columns:1fr 1fr;height:100%;min-height:0}.panel{padding:34px 32px 20px;min-width:0}.panel:first-child{border-right:3px solid #111827}.title{font-size:42px;letter-spacing:12px;font-weight:1000;margin:6px 0 54px;text-transform:uppercase}.title.payout{letter-spacing:8px}.heads{display:grid;grid-template-columns:1fr 190px;gap:18px;font-size:28px;font-weight:1000;letter-spacing:3px;margin-bottom:14px}.rule{height:5px;background:#111827;margin-bottom:36px}.row{display:grid;grid-template-columns:1fr 190px;gap:18px;align-items:center;padding:10px 0 28px;margin-bottom:20px;border-bottom:1px solid #d9dee8}.qnum{font-size:56px;line-height:1;font-weight:1000;color:#0b2e83;letter-spacing:2px}.cnum{font-size:44px;line-height:1;font-weight:1000;color:#b91c1c;text-align:center}.empty{height:330px;display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:1000;color:#9ca3af;letter-spacing:3px}.footer{background:#0b2e83;color:white;display:flex;align-items:center;justify-content:space-between;padding:0 26px;font-size:18px;font-weight:1000;letter-spacing:1px}.footer span:last-child{font-size:17px}@media(max-width:1000px){.title{font-size:30px;letter-spacing:6px}.heads{font-size:20px;grid-template-columns:1fr 120px}.row{grid-template-columns:1fr 120px}.qnum{font-size:38px}.cnum{font-size:32px}.panel{padding:24px 18px}.footer{font-size:14px}}
</style>
</head>
<body>
<div class="display">
    <div class="boards">
        <section class="panel">
            <h1 class="title">Assessment</h1>
            <div class="heads"><div>Queueing Number</div><div>Counter</div></div>
            <div class="rule"></div>
            <?php if($assessment && $assessment->num_rows > 0): while($r=$assessment->fetch_assoc()): ?>
                <div class="row"><div class="qnum"><?php echo h($r['queue_number']); ?></div><div class="cnum"><?php echo counterNo($r); ?></div></div>
            <?php endwhile; else: ?>
                <div class="empty">No Active Queue</div>
            <?php endif; ?>
        </section>
        <section class="panel">
            <h1 class="title payout">Payout / Release</h1>
            <div class="heads"><div>Queueing Number</div><div>Counter</div></div>
            <div class="rule"></div>
            <?php if($payout && $payout->num_rows > 0): while($r=$payout->fetch_assoc()): ?>
                <div class="row"><div class="qnum"><?php echo h($r['queue_number']); ?></div><div class="cnum"><?php echo counterNo($r); ?></div></div>
            <?php endwhile; else: ?>
                <div class="empty">No Active Queue</div>
            <?php endif; ?>
        </section>
    </div>
    <footer class="footer"><span>DSWD Max Payout Queueing and Monitoring System</span><span>Last updated: <b id="clock">--:--:--</b></span></footer>
</div>
<script>
function tick(){document.getElementById('clock').textContent=new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit',second:'2-digit'});}tick();setInterval(tick,1000);setTimeout(()=>location.reload(),5000);
</script>
</body>
</html>
