<?php
include('../auth/check.php');
include('../config/db.php');

date_default_timezone_set('Asia/Manila');

$counter_number = intval($_GET['counter'] ?? 1);

if ($counter_number <= 0) {
    $counter_number = 1;
}

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
        b.ext_name
    FROM queue_entries q
    INNER JOIN beneficiaries b ON b.id = q.beneficiary_id
    WHERE DATE(q.transaction_date) = CURDATE()
      AND q.workflow_status IN (
            'WAITING_STEP_2',
            'CALLED_STEP_2',
            'WAITING_STEP_3',
            'CALLED_STEP_3'
          )
    ORDER BY
        CASE
            WHEN q.workflow_status = 'CALLED_STEP_2' AND q.counter_number = ? THEN 0
            WHEN q.workflow_status = 'CALLED_STEP_3' AND q.counter_number = ? THEN 1
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

$query->bind_param("ii", $counter_number, $counter_number);
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

    if (!empty($entry['middle_name'])) {
        $name .= ' ' . $entry['middle_name'];
    }

    if (!empty($entry['ext_name'])) {
        $name .= ' ' . $entry['ext_name'];
    }

    return $name;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Counter <?php echo htmlspecialchars($counter_number); ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .counter-header {
            background: linear-gradient(135deg, #168fcb 0%, #127caf 100%);
            color: white;
            padding: 24px;
            margin: -22px -22px 22px -22px;
            border-radius: 0;
        }

        .counter-title {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .counter-subtitle {
            margin: 6px 0 0;
            font-size: 14px;
            opacity: 0.95;
        }

        .counter-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 1px solid #cfd6de;
        }

        .counter-table thead {
            background: #f5f7fa;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .counter-table thead tr {
            border-bottom: 2px solid #cfd6de;
        }

        .counter-table th {
            padding: 16px 12px;
            text-align: left;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            color: #334155;
            letter-spacing: 0.5px;
        }

        .counter-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: background 0.2s;
        }

        .counter-table tbody tr:hover {
            background: #f9fbfc;
        }

        .counter-table tbody tr.row-calling {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
        }

        .counter-table tbody tr.row-calling:hover {
            background: #fef08a;
        }

        .counter-table tbody tr.row-priority {
            background: #fff7ed;
        }

        .counter-table tbody tr.row-priority:hover {
            background: #ffedd5;
        }

        .counter-table td {
            padding: 16px 12px;
            vertical-align: middle;
        }

        .counter-table td:nth-child(1) {
            width: 17%;
            font-weight: 800;
            font-size: 16px;
            color: #0f2f56;
            white-space: nowrap;
        }

        .counter-table td:nth-child(2) {
            width: 38%;
            font-size: 15px;
        }

        .counter-table td:nth-child(3) {
            width: 45%;
            text-align: right;
        }

        .queue-priority {
            color: #c2410c !important;
        }

        .priority-label {
            display: inline-block;
            margin-left: 6px;
            background: #fed7aa;
            color: #9a3412;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 8px 14px;
            font-weight: 700;
            font-size: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            text-transform: uppercase;
            white-space: nowrap;
            text-decoration: none;
        }

        .action-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .action-btn.call {
            background: #10b981;
            color: white;
        }

        .action-btn.revert {
            background: #f97316;
            color: white;
        }

        .action-btn.assessed {
            background: #3b82f6;
            color: white;
        }

        .action-btn.paid {
            background: #a855f7;
            color: white;
        }

        .action-btn.assessed-paid {
            background: #06b6d4;
            color: white;
        }

        .action-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .empty-state {
            padding: 48px 24px;
            text-align: center;
            color: #94a3b8;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.3;
        }

        .table-wrap {
            overflow-y: auto;
            max-height: 70vh;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            margin-left: 8px;
            white-space: nowrap;
        }

        .status-badge.waiting-step-2 {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-badge.called-step-2 {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.waiting-step-3 {
            background: #e0e7ff;
            color: #3730a3;
        }

        .status-badge.called-step-3 {
            background: #fde68a;
            color: #78350f;
        }

        .status-badge.paid {
            background: #d1fae5;
            color: #065f46;
        }

        .header-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: white;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            opacity: 0.9;
        }

        @media (max-width: 1000px) {
            .counter-table {
                min-width: 950px;
            }

            .table-wrap {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>

<div class="app">
    <main class="main">
        <section class="content">
            <div class="counter-header">
                <div class="header-controls">
                    <div>
                        <h1 class="counter-title">COUNTER <?php echo htmlspecialchars($counter_number); ?></h1>
                        <p class="counter-subtitle">Step 2 Assessment and Step 3 Payout Queue</p>
                    </div>
                    <a href="verifier.php" class="back-link">
                        <span class="material-icons">arrow_back</span>
                        Back to Verifier
                    </a>
                </div>
            </div>

            <div class="table-wrap">
                <table class="counter-table">
                    <thead>
                        <tr>
                            <th>Queuing Number</th>
                            <th>Name of Client</th>
                            <th>Action Buttons</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($queue_entries) === 0): ?>
                            <tr>
                                <td colspan="3">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">📭</div>
                                        <p>No active queues for this counter.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($queue_entries as $entry): ?>
                                <?php
                                    $workflowStatus = $entry['workflow_status'];
                                    $isCalled = ($workflowStatus === 'CALLED_STEP_2' || $workflowStatus === 'CALLED_STEP_3');
                                    $isPriority = ($entry['queue_type'] === 'priority');

                                    $rowClass = '';
                                    if ($isCalled) {
                                        $rowClass = 'row-calling';
                                    } elseif ($isPriority) {
                                        $rowClass = 'row-priority';
                                    }

                                    $canCall = ($workflowStatus === 'WAITING_STEP_2' || $workflowStatus === 'WAITING_STEP_3');
                                    $canRevert = ($workflowStatus === 'CALLED_STEP_2' || $workflowStatus === 'WAITING_STEP_3' || $workflowStatus === 'CALLED_STEP_3');
                                    $canAssess = ($workflowStatus === 'CALLED_STEP_2');
                                    $canPay = ($workflowStatus === 'CALLED_STEP_3');
                                    $canAssessPay = ($workflowStatus === 'CALLED_STEP_2' || $workflowStatus === 'CALLED_STEP_3');
                                ?>
                                <tr class="<?php echo $rowClass; ?>">
                                    <td class="<?php echo $isPriority ? 'queue-priority' : ''; ?>">
                                        <?php echo htmlspecialchars($entry['queue_number']); ?>
                                        <?php if ($isPriority): ?>
                                            <span class="priority-label">PRIO</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars(clientName($entry)); ?>
                                        <span class="status-badge <?php echo statusClass($workflowStatus); ?>">
                                            <?php echo htmlspecialchars(displayStatus($workflowStatus)); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="action-buttons">
                                            <form method="POST" action="../api/call_queue.php" style="margin:0;display:inline;">
                                                <input type="hidden" name="queue_id" value="<?php echo intval($entry['id']); ?>">
                                                <input type="hidden" name="counter_number" value="<?php echo intval($counter_number); ?>">
                                                <button type="submit" class="action-btn call" <?php echo !$canCall ? 'disabled' : ''; ?>>
                                                    <span class="material-icons" style="font-size:16px;">campaign</span>
                                                    Call
                                                </button>
                                            </form>

                                            <form method="POST" action="../api/revert_queue.php" style="margin:0;display:inline;" onsubmit="return confirm('Revert this queue entry?');">
                                                <input type="hidden" name="queue_id" value="<?php echo intval($entry['id']); ?>">
                                                <input type="hidden" name="counter_number" value="<?php echo intval($counter_number); ?>">
                                                <button type="submit" class="action-btn revert" <?php echo !$canRevert ? 'disabled' : ''; ?>>
                                                    <span class="material-icons" style="font-size:16px;">undo</span>
                                                    Revert
                                                </button>
                                            </form>

                                            <form method="POST" action="../api/mark_assessed.php" style="margin:0;display:inline;">
                                                <input type="hidden" name="queue_id" value="<?php echo intval($entry['id']); ?>">
                                                <input type="hidden" name="counter_number" value="<?php echo intval($counter_number); ?>">
                                                <button type="submit" class="action-btn assessed" <?php echo !$canAssess ? 'disabled' : ''; ?>>
                                                    <span class="material-icons" style="font-size:16px;">check_circle</span>
                                                    Assessed
                                                </button>
                                            </form>

                                            <form method="POST" action="../api/mark_paid.php" style="margin:0;display:inline;" onsubmit="return confirm('Mark this queue as paid?');">
                                                <input type="hidden" name="queue_id" value="<?php echo intval($entry['id']); ?>">
                                                <input type="hidden" name="counter_number" value="<?php echo intval($counter_number); ?>">
                                                <button type="submit" class="action-btn paid" <?php echo !$canPay ? 'disabled' : ''; ?>>
                                                    <span class="material-icons" style="font-size:16px;">payments</span>
                                                    Paid
                                                </button>
                                            </form>

                                            <form method="POST" action="../api/mark_assessed_paid.php" style="margin:0;display:inline;" onsubmit="return confirm('Mark this queue as assessed and paid?');">
                                                <input type="hidden" name="queue_id" value="<?php echo intval($entry['id']); ?>">
                                                <input type="hidden" name="counter_number" value="<?php echo intval($counter_number); ?>">
                                                <button type="submit" class="action-btn assessed-paid" <?php echo !$canAssessPay ? 'disabled' : ''; ?>>
                                                    <span class="material-icons" style="font-size:16px;">done_all</span>
                                                    Assessed & Paid
                                                </button>
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

    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            isSubmitting = true;
            const buttons = this.querySelectorAll('button');
            buttons.forEach(btn => btn.disabled = true);
        });
    });

    setTimeout(() => {
        if (!isSubmitting) {
            location.reload();
        }
    }, 5000);
</script>

</body>
</html>