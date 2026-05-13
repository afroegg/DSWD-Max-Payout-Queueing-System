<?php
include('../auth/check.php');
include('../config/db.php');

date_default_timezone_set('Asia/Manila');

$query = $conn->prepare("
    SELECT
        q.id,
        q.queue_number,
        q.queue_type,
        q.status,
        q.workflow_status,
        q.table_number,
        q.counter_number,
        q.called_at,
        q.assessed_at,
        q.paid_at,
        b.first_name,
        b.middle_name,
        b.last_name,
        b.ext_name,
        e.form_locked,
        e.eligibility_status,
        e.approved_cash_amount
    FROM queue_entries q
    INNER JOIN beneficiaries b ON b.id = q.beneficiary_id
    LEFT JOIN eligibility_forms e ON e.queue_entry_id = q.id
    WHERE DATE(q.transaction_date) = CURDATE()
      AND q.workflow_status IN (
            'WAITING_STEP_2',
            'CALLED_STEP_2',
            'WAITING_STEP_3',
            'CALLED_STEP_3'
          )
    ORDER BY
        CASE
            WHEN q.workflow_status = 'CALLED_STEP_2' THEN 0
            WHEN q.workflow_status = 'CALLED_STEP_3' THEN 1
            WHEN q.queue_type = 'priority' THEN 2
            ELSE 3
        END ASC,
        CASE
            WHEN q.queue_type = 'priority'
            THEN CAST(SUBSTRING(q.queue_number, 6) AS UNSIGNED)
            ELSE 999999
        END ASC,
        CASE
            WHEN q.queue_type = 'regular'
            THEN CAST(SUBSTRING(q.queue_number, 5) AS UNSIGNED)
            ELSE 999999
        END ASC,
        q.id ASC
");

$query->execute();
$result = $query->get_result();
$queue_entries = [];
while ($row = $result->fetch_assoc()) {
    $queue_entries[] = $row;
}

function displayStatus($status) {
    if ($status === 'WAITING_STEP_2') return 'Waiting Step 2';
    if ($status === 'CALLED_STEP_2') return 'Called Step 2';
    if ($status === 'WAITING_STEP_3') return 'Waiting Step 3';
    if ($status === 'CALLED_STEP_3') return 'Called Step 3';
    if ($status === 'PAID') return 'Paid';
    return 'No Status';
}

function statusClass($status) {
    if ($status === 'WAITING_STEP_2') return 'waiting-step-2';
    if ($status === 'CALLED_STEP_2') return 'called-step-2';
    if ($status === 'WAITING_STEP_3') return 'waiting-step-3';
    if ($status === 'CALLED_STEP_3') return 'called-step-3';
    if ($status === 'PAID') return 'paid';
    return 'none';
}

function clientName($entry) {
    $name = $entry['last_name'] . ', ' . $entry['first_name'];
    if (!empty($entry['middle_name'])) $name .= ' ' . $entry['middle_name'];
    if (!empty($entry['ext_name'])) $name .= ' ' . $entry['ext_name'];
    return $name;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Counter List</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .counter-header{background:linear-gradient(135deg,#168fcb 0%,#127caf 100%);color:white;padding:24px;margin:-22px -22px 22px -22px;border-radius:0}.counter-title{margin:0;font-size:32px;font-weight:700;letter-spacing:1px}.counter-subtitle{margin:6px 0 0;font-size:14px;opacity:.95}.header-controls{display:flex;justify-content:space-between;align-items:center;gap:16px}.back-link{display:inline-flex;align-items:center;gap:6px;color:white;text-decoration:none;font-weight:600}.back-link:hover{opacity:.9}.table-wrap{overflow:auto;max-height:70vh}.counter-table{width:100%;min-width:1180px;border-collapse:collapse;background:white;border:1px solid #cfd6de}.counter-table thead{background:#f5f7fa;position:sticky;top:0;z-index:10}.counter-table thead tr{border-bottom:2px solid #cfd6de}.counter-table th{padding:16px 12px;text-align:left;font-weight:700;font-size:12px;text-transform:uppercase;color:#334155;letter-spacing:.5px}.counter-table tbody tr{border-bottom:1px solid #e2e8f0;transition:background .2s}.counter-table tbody tr:hover{background:#f9fbfc}.counter-table tbody tr.row-calling{background:#fef3c7;border-left:4px solid #f59e0b}.counter-table tbody tr.row-priority{background:#fff7ed}.counter-table td{padding:16px 12px;vertical-align:middle}.counter-table td:nth-child(1){width:13%;font-weight:800;font-size:16px;color:#0f2f56;white-space:nowrap}.counter-table td:nth-child(2){width:24%;font-size:15px}.counter-table td:nth-child(3){width:23%}.counter-table td:nth-child(4){width:40%;text-align:left}.queue-priority{color:#c2410c!important}.priority-label{display:inline-block;margin-left:6px;background:#fed7aa;color:#9a3412;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:800}.assigned-counter{display:inline-flex;align-items:center;gap:5px;padding:5px 9px;border-radius:999px;background:#ecfeff;color:#155e75;font-size:12px;font-weight:800;margin-left:6px}.gis-badge{display:inline-flex;align-items:center;gap:5px;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:800;margin-top:6px}.gis-locked{background:#dcfce7;color:#166534}.gis-draft{background:#fee2e2;color:#991b1b}.status-badge{display:inline-block;padding:5px 9px;border-radius:999px;font-size:11px;font-weight:800;text-transform:uppercase;white-space:nowrap}.status-badge.waiting-step-2{background:#dbeafe;color:#1e40af}.status-badge.called-step-2{background:#fef3c7;color:#92400e}.status-badge.waiting-step-3{background:#e0e7ff;color:#3730a3}.status-badge.called-step-3{background:#fde68a;color:#78350f}.action-buttons{display:grid;grid-template-columns:126px 96px 128px 116px;gap:10px;align-items:center;justify-content:end}.action-form{display:contents;margin:0}.action-form.call-form{display:contents}.counter-select{grid-column:1;min-height:40px;width:126px;padding:7px 10px;border-radius:7px;border:1px solid #cbd5e1;font-size:14px;font-weight:800;outline:none;background:white}.action-btn{min-height:40px;display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:9px 12px;font-weight:800;font-size:13px;border:none;border-radius:7px;cursor:pointer;transition:all .2s;text-transform:uppercase;white-space:nowrap;text-decoration:none;color:white}.action-btn:hover:not(:disabled){transform:translateY(-2px);box-shadow:0 4px 8px rgba(0,0,0,.15)}.action-btn .material-icons{font-size:16px}.action-btn.call{grid-column:2;background:#10b981}.action-btn.gis{grid-column:3;background:#93aee8}.action-btn.revert{grid-column:4;background:#f4b183}.action-btn.assessed{grid-column:1;background:#9bbcf0}.action-btn.paid{grid-column:2;background:#d6a6ef}.action-btn.assessed-paid{grid-column:3 / span 2;background:#6fcbd4}.action-btn:disabled,.counter-select:disabled{opacity:.45;cursor:not-allowed;transform:none;box-shadow:none}.empty-state{padding:48px 24px;text-align:center;color:#94a3b8}.empty-state-icon{font-size:48px;margin-bottom:12px;opacity:.3}@media(max-width:1100px){.counter-table{min-width:1180px}.action-buttons{justify-content:start}}
    </style>
</head>
<body>
<div class="app">
    <main class="main">
        <section class="content">
            <div class="counter-header">
                <div class="header-controls">
                    <div>
                        <h1 class="counter-title">COUNTER LIST [Step 2 & 3]</h1>
                        <p class="counter-subtitle">Call beneficiary, encode GIS form, approve assistance, then mark assessed or paid.</p>
                    </div>
                    <a href="verifier.php" class="back-link"><span class="material-icons">arrow_back</span>Back to Verifier</a>
                </div>
            </div>
            <div class="table-wrap">
                <table class="counter-table">
                    <thead><tr><th>Queuing Number</th><th>Name of Client</th><th>Status / GIS</th><th>Action Buttons</th></tr></thead>
                    <tbody>
                    <?php if (count($queue_entries) === 0): ?>
                        <tr><td colspan="4"><div class="empty-state"><div class="empty-state-icon">📭</div><p>No active queues for Step 2 or Step 3.</p></div></td></tr>
                    <?php else: ?>
                        <?php foreach ($queue_entries as $entry): ?>
                            <?php
                                $workflowStatus = $entry['workflow_status'];
                                $isCalledStep2 = ($workflowStatus === 'CALLED_STEP_2');
                                $isCalledStep3 = ($workflowStatus === 'CALLED_STEP_3');
                                $isCalled = ($isCalledStep2 || $isCalledStep3);
                                $isPriority = ($entry['queue_type'] === 'priority');
                                $isGISLocked = intval($entry['form_locked'] ?? 0) === 1 && ($entry['eligibility_status'] ?? '') === 'Eligible' && floatval($entry['approved_cash_amount'] ?? 0) > 0;
                                $rowClass = $isCalled ? 'row-calling' : ($isPriority ? 'row-priority' : '');
                                $canCall = ($workflowStatus === 'WAITING_STEP_2' || $workflowStatus === 'WAITING_STEP_3');
                                $canOpenGIS = $isCalledStep2;
                                $canRevert = ($workflowStatus === 'CALLED_STEP_2' || $workflowStatus === 'WAITING_STEP_3' || $workflowStatus === 'CALLED_STEP_3');
                                $canAssess = ($workflowStatus === 'CALLED_STEP_2' && $isGISLocked);
                                $canPay = ($workflowStatus === 'CALLED_STEP_3' && $isGISLocked);
                                $canAssessPay = (($workflowStatus === 'CALLED_STEP_2' || $workflowStatus === 'CALLED_STEP_3') && $isGISLocked);
                                $selectedCounter = intval($entry['counter_number'] ?? 1);
                                if ($selectedCounter <= 0) $selectedCounter = 1;
                            ?>
                            <tr class="<?php echo $rowClass; ?>">
                                <td class="<?php echo $isPriority ? 'queue-priority' : ''; ?>"><?php echo htmlspecialchars($entry['queue_number']); ?><?php if ($isPriority): ?><span class="priority-label">PRIO</span><?php endif; ?></td>
                                <td><?php echo htmlspecialchars(clientName($entry)); ?></td>
                                <td>
                                    <span class="status-badge <?php echo statusClass($workflowStatus); ?>"><?php echo htmlspecialchars(displayStatus($workflowStatus)); ?></span>
                                    <?php if ($isCalled): ?><span class="assigned-counter">Counter <?php echo intval($entry['counter_number']); ?></span><?php endif; ?>
                                    <br>
                                    <?php if ($isGISLocked): ?><span class="gis-badge gis-locked">GIS Locked: ₱<?php echo number_format(floatval($entry['approved_cash_amount']), 2); ?></span><?php else: ?><span class="gis-badge gis-draft">GIS Not Locked</span><?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <form method="POST" action="../api/call_queue.php" class="action-form call-form">
                                            <input type="hidden" name="queue_id" value="<?php echo intval($entry['id']); ?>">
                                            <select name="counter_number" class="counter-select" data-queue-id="<?php echo intval($entry['id']); ?>" <?php echo !$canCall ? 'disabled' : ''; ?>>
                                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                                    <option value="<?php echo $i; ?>" <?php echo $selectedCounter === $i ? 'selected' : ''; ?>>Counter <?php echo $i; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                            <button type="submit" class="action-btn call" <?php echo !$canCall ? 'disabled' : ''; ?>><span class="material-icons">campaign</span>Call</button>
                                        </form>
                                        <a href="eligibility_form.php?queue_id=<?php echo intval($entry['id']); ?>" class="action-btn gis" <?php echo !$canOpenGIS ? 'style="pointer-events:none;opacity:0.45;"' : ''; ?>><span class="material-icons">description</span>GIS Form</a>
                                        <form method="POST" action="../api/revert_queue.php" class="action-form" onsubmit="return confirm('Revert this queue entry?');">
                                            <input type="hidden" name="queue_id" value="<?php echo intval($entry['id']); ?>"><input type="hidden" name="counter_number" value="<?php echo $selectedCounter; ?>">
                                            <button type="submit" class="action-btn revert" <?php echo !$canRevert ? 'disabled' : ''; ?>><span class="material-icons">undo</span>Revert</button>
                                        </form>
                                        <form method="POST" action="../api/mark_assessed.php" class="action-form">
                                            <input type="hidden" name="queue_id" value="<?php echo intval($entry['id']); ?>"><input type="hidden" name="counter_number" value="<?php echo $selectedCounter; ?>">
                                            <button type="submit" class="action-btn assessed" <?php echo !$canAssess ? 'disabled' : ''; ?>><span class="material-icons">check_circle</span>Assessed</button>
                                        </form>
                                        <form method="POST" action="../api/mark_paid.php" class="action-form" onsubmit="return confirm('Mark this queue as paid?');">
                                            <input type="hidden" name="queue_id" value="<?php echo intval($entry['id']); ?>"><input type="hidden" name="counter_number" value="<?php echo $selectedCounter; ?>">
                                            <button type="submit" class="action-btn paid" <?php echo !$canPay ? 'disabled' : ''; ?>><span class="material-icons">payments</span>Paid</button>
                                        </form>
                                        <form method="POST" action="../api/mark_assessed_paid.php" class="action-form" onsubmit="return confirm('Mark this queue as assessed and paid?');">
                                            <input type="hidden" name="queue_id" value="<?php echo intval($entry['id']); ?>"><input type="hidden" name="counter_number" value="<?php echo $selectedCounter; ?>">
                                            <button type="submit" class="action-btn assessed-paid" <?php echo !$canAssessPay ? 'disabled' : ''; ?>><span class="material-icons">done_all</span>Assessed & Paid</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <?php include('sidebar.php'); ?>
</div>
<script>
    let isSubmitting = false;
    let isInteractingWithCounter = false;
    let refreshTimer = null;

    function saveCounterSelections() {
        document.querySelectorAll('.counter-select').forEach(select => {
            if (!select.disabled) {
                localStorage.setItem('counter_select_' + select.dataset.queueId, select.value);
            }
        });
    }

    function restoreCounterSelections() {
        document.querySelectorAll('.counter-select').forEach(select => {
            const saved = localStorage.getItem('counter_select_' + select.dataset.queueId);
            if (saved && !select.disabled) {
                select.value = saved;
            }
        });
    }

    function scheduleRefresh() {
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(() => {
            if (!isSubmitting && !isInteractingWithCounter && !document.querySelector('.counter-select:focus')) {
                saveCounterSelections();
                location.reload();
            } else {
                scheduleRefresh();
            }
        }, 5000);
    }

    document.querySelectorAll('.counter-select').forEach(select => {
        select.addEventListener('focus', () => { isInteractingWithCounter = true; });
        select.addEventListener('mousedown', () => { isInteractingWithCounter = true; });
        select.addEventListener('touchstart', () => { isInteractingWithCounter = true; });
        select.addEventListener('change', () => { localStorage.setItem('counter_select_' + select.dataset.queueId, select.value); });
        select.addEventListener('blur', () => { setTimeout(() => { isInteractingWithCounter = false; }, 800); });
    });

    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            isSubmitting = true;
            saveCounterSelections();
            const buttons = this.querySelectorAll('button');
            buttons.forEach(btn => btn.disabled = true);
        });
    });

    restoreCounterSelections();
    scheduleRefresh();
</script>
</body>
</html>