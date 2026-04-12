<?php
include('../auth/check.php');
include('../config/db.php');

$msg = "";
$msgType = "";
$today = date('Y-m-d');

// Get the one currently serving queue
$servingQuery = $conn->prepare("
    SELECT q.id, q.queue_number, q.beneficiary_id,
           b.first_name, b.last_name, b.program_type
    FROM queue_entries q
    JOIN beneficiaries b ON q.beneficiary_id = b.id
    WHERE q.transaction_date = ? AND q.status = 'serving'
    LIMIT 1
");
$servingQuery->bind_param("s", $today);
$servingQuery->execute();
$servingResult = $servingQuery->get_result();
$servingData = $servingResult->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $queue_id = intval($_POST['queue_id']);
    $batch = trim($_POST['batch']);
    $amount = floatval($_POST['amount']);

    // Check if this queue already has released payout
    $check = $conn->prepare("
        SELECT id
        FROM payouts
        WHERE queue_entry_id = ? AND status = 'released'
        LIMIT 1
    ");
    $check->bind_param("i", $queue_id);
    $check->execute();
    $checkRes = $check->get_result();

    if ($checkRes->num_rows > 0) {
        $msg = "Payout has already been released for this queue.";
        $msgType = "error-message";
    } else {
        // Re-read queue entry
        $queueCheck = $conn->prepare("
            SELECT beneficiary_id, queue_number
            FROM queue_entries
            WHERE id = ? AND transaction_date = ? AND status = 'serving'
            LIMIT 1
        ");
        $queueCheck->bind_param("is", $queue_id, $today);
        $queueCheck->execute();
        $queueRes = $queueCheck->get_result();

        if ($queueRes->num_rows > 0) {
            $queueRow = $queueRes->fetch_assoc();
            $beneficiary_id = $queueRow['beneficiary_id'];
            $released_by = $_SESSION['user_id'];

            $insert = $conn->prepare("
                INSERT INTO payouts (
                    beneficiary_id,
                    queue_entry_id,
                    payout_batch,
                    payout_date,
                    amount,
                    released_by,
                    released_at,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, NOW(), 'released')
            ");
            $insert->bind_param("iissdi", $beneficiary_id, $queue_id, $batch, $today, $amount, $released_by);

            if ($insert->execute()) {
                $update = $conn->prepare("
                    UPDATE queue_entries
                    SET status = 'released', released_at = NOW()
                    WHERE id = ?
                ");
                $update->bind_param("i", $queue_id);
                $update->execute();

                $msg = "Payout released successfully.";
                $msgType = "success-message";

                // Refresh serving data after release
                $servingData = null;
            } else {
                $msg = "Failed to save the payout release.";
                $msgType = "error-message";
            }
        } else {
            $msg = "No active serving queue found.";
            $msgType = "error-message";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Release Payout</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>

<div class="app">

    <main class="main">
        <header class="header">
            <div class="header-row">
                <h1>Release Payout</h1>
                <a href="dashboard.php" class="btn neutral">
                    <span class="material-icons">arrow_back</span>
                    Back to Dashboard
                </a>
            </div>
        </header>

        <section class="content page-content">
            <div class="form-page-card medium-form-card">
                <div class="section-title">
                    <h2>Payout Processing</h2>
                    <p>Release the payout for the beneficiary currently being served at the single max payout counter.</p>
                </div>

                <?php if ($servingData): ?>
                    <div class="form-message" style="margin-top: 0; margin-bottom: 20px; background:#f8fafc; border-color:#cfd6de; color:#334155;">
                        <strong>Now Serving</strong><br>
                        Queue No.: <?php echo htmlspecialchars($servingData['queue_number']); ?><br>
                        Name: <?php echo htmlspecialchars($servingData['first_name'] . ' ' . $servingData['last_name']); ?><br>
                        Program: <?php echo htmlspecialchars($servingData['program_type']); ?>
                    </div>

                    <form method="POST" class="system-form">
                        <input type="hidden" name="queue_id" value="<?php echo $servingData['id']; ?>">

                        <div class="form-grid single-col">
                            <div class="field-group">
                                <label>Batch</label>
                                <input type="text" name="batch" placeholder="Enter payout batch" required>
                            </div>

                            <div class="field-group">
                                <label>Amount</label>
                                <input type="number" step="0.01" name="amount" placeholder="Enter amount" required>
                            </div>
                        </div>

                        <?php if (!empty($msg)): ?>
                            <div class="form-message <?php echo $msgType; ?>">
                                <?php echo htmlspecialchars($msg); ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-actions">
                            <button type="submit" class="btn success">
                                <span class="material-icons">payments</span>
                                Release Payout
                            </button>

                            <a href="dashboard.php" class="btn neutral">
                                <span class="material-icons">close</span>
                                Cancel
                            </a>
                        </div>
                    </form>
                <?php else: ?>
                    <?php if (!empty($msg)): ?>
                        <div class="form-message <?php echo $msgType; ?>">
                            <?php echo htmlspecialchars($msg); ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-message error-message" style="margin-top: 0;">
                        No beneficiary is currently being served.
                    </div>

                    <div class="form-actions">
                        <a href="serve_next.php" class="btn primary">
                            <span class="material-icons">campaign</span>
                            Go to Serve Next
                        </a>

                        <a href="dashboard.php" class="btn neutral">
                            <span class="material-icons">arrow_back</span>
                            Back to Dashboard
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include('sidebar.php'); ?>

</div>

</body>
</html>