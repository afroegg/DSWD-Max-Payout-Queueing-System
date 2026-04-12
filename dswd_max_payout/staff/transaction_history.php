<?php
include('../auth/check.php');
include('../config/db.php');

$sql = "SELECT p.id, q.queue_number, b.first_name, b.last_name, b.program_type,
               p.payout_batch, p.payout_date, p.amount, p.released_at
        FROM payouts p
        JOIN beneficiaries b ON p.beneficiary_id = b.id
        JOIN queue_entries q ON p.queue_entry_id = q.id
        ORDER BY p.released_at DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Transaction History</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
    <h2>Payout Transaction History</h2>
    <table>
        <tr>
            <th>Queue No.</th>
            <th>Name</th>
            <th>Program</th>
            <th>Batch</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Released At</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['queue_number']; ?></td>
            <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
            <td><?php echo $row['program_type']; ?></td>
            <td><?php echo $row['payout_batch']; ?></td>
            <td><?php echo number_format($row['amount'], 2); ?></td>
            <td><?php echo $row['payout_date']; ?></td>
            <td><?php echo $row['released_at']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <a href="dashboard.php">Back</a>
</div>
</body>
</html>