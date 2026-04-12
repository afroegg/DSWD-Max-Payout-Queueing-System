<?php
include('../auth/check.php');
include('../config/db.php');

$today = date('Y-m-d');

$sql = "SELECT q.id, q.queue_number, q.status, q.counter_number,
               b.first_name, b.last_name, b.program_type
        FROM queue_entries q
        JOIN beneficiaries b ON q.beneficiary_id = b.id
        WHERE q.transaction_date = '$today'
        ORDER BY q.id ASC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Queue List</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
    <h2>Today's Queue List</h2>
    <table>
        <tr>
            <th>Queue No.</th>
            <th>Name</th>
            <th>Program</th>
            <th>Status</th>
            <th>Counter</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['queue_number']; ?></td>
            <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
            <td><?php echo $row['program_type']; ?></td>
            <td><?php echo $row['status']; ?></td>
            <td><?php echo $row['counter_number'] ?: '-'; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <a href="dashboard.php">Back</a>
</div>
</body>
</html>