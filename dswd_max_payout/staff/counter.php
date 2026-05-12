<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Counter <?php echo $counter_number; ?></title>
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
            font-weight: 600;
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

        /* HIGHLIGHT CURRENTLY CALLED/SERVING ROW */
        .counter-table tbody tr.row-calling {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
        }

        .counter-table tbody tr.row-calling:hover {
            background: #fef08a;
        }

        .counter-table td {
            padding: 16px 12px;
            vertical-align: middle;
        }

        /* Column widths */
        .counter-table td:nth-child(1) {
            width: 15%;
            font-weight: 700;
            font-size: 16px;
            color: #0f2f56;
        }

        .counter-table td:nth-child(2) {
            width: 40%;
            font-size: 15px;
        }

        .counter-table td:nth-child(3) {
            width: 45%;
            text-align: right;
        }

        /* ACTION BUTTONS */
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
            font-weight: 600;
            font-size: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            text-transform: uppercase;
            white-space: nowrap;
            text-decoration: none;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .action-btn.call {
            background: #10b981;
            color: white;
        }

        .action-btn.call:hover {
            background: #059669;
        }

        .action-btn.revert {
            background: #f97316;
            color: white;
        }

        .action-btn.revert:hover {
            background: #ea580c;
        }

        .action-btn.assessed {
            background: #3b82f6;
            color: white;
        }

        .action-btn.assessed:hover {
            background: #2563eb;
        }

        .action-btn.paid {
            background: #a855f7;
            color: white;
        }

        .action-btn.paid:hover {
            background: #9333ea;
        }

        .action-btn.assessed-paid {
            background: #06b6d4;
            color: white;
        }

        .action-btn.assessed-paid:hover {
            background: #0891b2;
        }

        .action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.waiting {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-badge.serving {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.assessed {
            background: #dbeafe;
            color: #1e40af;
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
            font-weight: 500;
        }

        .back-link:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>

<div class="app">
    <main class="main">
        <div class="counter-header">
            <div class="header-controls">
                <div>
                    <h1 class="counter-title">COUNTER <?php echo $counter_number; ?></h1>
                </div>
                <a href="dashboard.php" class="back-link">
                    <span class="material-icons">arrow_back</span>
                    Back to Dashboard
                </a>
            </div>
        </div>

        <section class="content">
            <div class="table-wrap">
                <table class="counter-table">
                    <thead>
                        <tr>
                            <th>QUEUING NUMBER</th>
                            <th>NAME OF CLIENT</th>
                            <th>ACTION BUTTONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($queue_entries) === 0): ?>
                            <tr>
                                <td colspan="3">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">📭</div>
                                        <p>No active queues for this counter</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($queue_entries as $entry): ?>
                                <?php 
                                    // Determine if this row is currently being called
                                    $isServing = ($entry['status'] === 'serving');
                                    $rowClass = $isServing ? 'row-calling' : '';
                                ?>
                                <tr class="<?php echo $rowClass; ?>">
                                    <td>
                                        <?php echo htmlspecialchars($entry['queue_number']); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($entry['first_name'] . ', ' . $entry['last_name']); ?>
                                        <span class="status-badge <?php echo strtolower($entry['status']); ?>">
                                            <?php echo htmlspecialchars($entry['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <!-- CALL BUTTON -->
                                            <form method="POST" action="../api/call_queue.php" style="margin:0;display:inline;">
                                                <input type="hidden" name="queue_id" value="<?php echo $entry['id']; ?>">
                                                <button type="submit" class="action-btn call" <?php echo ($isServing) ? 'disabled' : ''; ?>>
                                                    <span class="material-icons" style="font-size:16px;">campaign</span>
                                                    CALL
                                                </button>
                                            </form>

                                            <!-- REVERT BUTTON -->
                                            <form method="POST" action="../api/revert_queue.php" style="margin:0;display:inline;" onsubmit="return confirm('Revert this queue entry?');">
                                                <input type="hidden" name="queue_id" value="<?php echo $entry['id']; ?>">
                                                <button type="submit" class="action-btn revert">
                                                    <span class="material-icons" style="font-size:16px;">undo</span>
                                                    REVERT
                                                </button>
                                            </form>

                                            <!-- ASSESSED BUTTON -->
                                            <form method="POST" action="../api/mark_assessed.php" style="margin:0;display:inline;">
                                                <input type="hidden" name="queue_id" value="<?php echo $entry['id']; ?>">
                                                <button type="submit" class="action-btn assessed">
                                                    <span class="material-icons" style="font-size:16px;">check_circle</span>
                                                    ASSESSED
                                                </button>
                                            </form>

                                            <!-- PAID BUTTON -->
                                            <form method="POST" action="../api/mark_paid.php" style="margin:0;display:inline;">
                                                <input type="hidden" name="queue_id" value="<?php echo $entry['id']; ?>">
                                                <button type="submit" class="action-btn paid">
                                                    <span class="material-icons" style="font-size:16px;">payments</span>
                                                    PAID
                                                </button>
                                            </form>

                                            <!-- ASSESSED AND PAID BUTTON -->
                                            <form method="POST" action="../api/mark_assessed_paid.php" style="margin:0;display:inline;">
                                                <input type="hidden" name="queue_id" value="<?php echo $entry['id']; ?>">
                                                <button type="submit" class="action-btn assessed-paid">
                                                    <span class="material-icons" style="font-size:16px;">done_all</span>
                                                    ASSESSED & PAID
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
    // Auto-refresh counter display every 2 seconds
    setTimeout(() => {
        location.reload();
    }, 2000);

    // Prevent duplicate form submissions
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const buttons = this.querySelectorAll('button');
            buttons.forEach(btn => btn.disabled = true);
        });
    });
</script>

</body>
</html>