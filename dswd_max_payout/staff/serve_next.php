<?php
include('../auth/check.php');
include('../config/db.php');
include('../api/queue_notifier.php');

$message = "";
$messageType = "";

$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $checkServing = $conn->prepare("
        SELECT id, queue_number
        FROM queue_entries
        WHERE transaction_date = ? AND status = 'serving'
        LIMIT 1
    ");
    $checkServing->bind_param("s", $today);
    $checkServing->execute();
    $servingResult = $checkServing->get_result();

    if ($servingResult->num_rows > 0) {
        $servingRow = $servingResult->fetch_assoc();
        $message = "Queue " . $servingRow['queue_number'] . " is still being served. Release the payout first before calling the next queue.";
        $messageType = "error-message";
    } else {
        $nextQueue = $conn->prepare("
            SELECT id, queue_number
            FROM queue_entries
            WHERE transaction_date = ? AND status = 'waiting'
            ORDER BY id ASC
            LIMIT 1
        ");
        $nextQueue->bind_param("s", $today);
        $nextQueue->execute();
        $nextResult = $nextQueue->get_result();

        if ($nextResult->num_rows > 0) {
            $nextRow = $nextResult->fetch_assoc();
            $queueId = $nextRow['id'];
            $queueNumber = $nextRow['queue_number'];

            $update = $conn->prepare("
                UPDATE queue_entries
                SET status = 'serving', called_at = NOW()
                WHERE id = ?
            ");
            $update->bind_param("i", $queueId);

            if ($update->execute()) {
                notifyCustomersTenAway($conn, $today);

                $message = "Queue " . $queueNumber . " is now being served.";
                $messageType = "success-message";
            } else {
                $message = "Failed to update the queue status.";
                $messageType = "error-message";
            }
        } else {
            $message = "No waiting queue available.";
            $messageType = "error-message";
        }
    }
}

$currentServing = $conn->prepare("
    SELECT q.queue_number, b.first_name, b.last_name
    FROM queue_entries q
    JOIN beneficiaries b ON q.beneficiary_id = b.id
    WHERE q.transaction_date = ? AND q.status = 'serving'
    LIMIT 1
");
$currentServing->bind_param("s", $today);
$currentServing->execute();
$currentServingResult = $currentServing->get_result();
$servingData = $currentServingResult->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Serve Next</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>

<div class="app">
    <main class="main">
        <header class="header">
            <div class="header-row">
                <h1>Serve Next</h1>
                <a href="dashboard.php" class="btn neutral">
                    <span class="material-icons">arrow_back</span>
                    Back to Dashboard
                </a>
            </div>
        </header>

        <section class="content page-content">
            <div class="form-page-card medium-form-card">
                <div class="section-title">
                    <h2>Single Counter Queue Control</h2>
                    <p>Call the next waiting beneficiary for the max payout counter.</p>
                </div>

                <?php if ($servingData): ?>
                    <div class="form-message success-message" style="margin-top:0;margin-bottom:20px;">
                        <strong>Currently Serving:</strong><br>
                        Queue No.: <?php echo htmlspecialchars($servingData['queue_number']); ?><br>
                        Name: <?php echo htmlspecialchars($servingData['first_name'] . ' ' . $servingData['last_name']); ?>
                    </div>
                <?php else: ?>
                    <div class="form-message" style="margin-top:0;margin-bottom:20px;background:#f8fafc;border-color:#cfd6de;color:#334155;">
                        No queue is currently being served.
                    </div>
                <?php endif; ?>

                <form method="POST" class="system-form">
                    <?php if (!empty($message)): ?>
                        <div class="form-message <?php echo $messageType; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <button type="submit" class="btn primary">
                            <span class="material-icons">campaign</span>
                            Serve Next
                        </button>

                        <a href="dashboard.php" class="btn neutral">
                            <span class="material-icons">close</span>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <?php include('sidebar.php'); ?>
</div>

</body>
</html>