<?php
include('../auth/check.php');
include('../config/db.php');

date_default_timezone_set('Asia/Manila');

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function displayStatus($status) {
    if ($status === 'WAITING_STEP_2') return 'WAITING: STEP 2';
    if ($status === 'CALLED_STEP_2') return 'CALLED: STEP 2';
    if ($status === 'WAITING_STEP_3') return 'WAITING: STEP 3';
    if ($status === 'CALLED_STEP_3') return 'CALLED: STEP 3';
    if ($status === 'PAID') return 'PAID';
    if ($status === 'CANCELLED') return 'CANCELLED';

    return 'NO QUEUE NUMBER';
}

function statusClass($status) {
    if ($status === 'WAITING_STEP_2') return 'status-step-2';
    if ($status === 'CALLED_STEP_2') return 'status-called-2';
    if ($status === 'WAITING_STEP_3') return 'status-step-3';
    if ($status === 'CALLED_STEP_3') return 'status-called-3';
    if ($status === 'PAID') return 'status-paid';
    if ($status === 'CANCELLED') return 'status-cancelled';

    return 'status-none';
}

$query = "
    SELECT 
        b.id,
        b.last_name,
        b.first_name,
        b.middle_name,
        b.ext_name,
        b.region,
        b.province,
        b.city_municipality,
        b.barangay,
        b.contact_number,
        b.birthday_month,
        b.birthday_day,
        b.birthday_year,
        b.age,
        b.sex,
        b.lgu,
        q.queue_number,
        q.queue_type,
        q.workflow_status
    FROM beneficiaries b
    LEFT JOIN queue_entries q 
        ON q.beneficiary_id = b.id
        AND q.transaction_date = CURDATE()
        AND q.id = (
            SELECT MAX(q2.id)
            FROM queue_entries q2
            WHERE q2.beneficiary_id = b.id
            AND q2.transaction_date = CURDATE()
        )
    ORDER BY b.last_name ASC, b.first_name ASC
";

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verifier Step 1</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f6fb;
            color: #1f2937;
        }

        .page-wrapper {
            padding: 20px;
        }

        .page-header {
            background: white;
            border: 1px solid #d6dce8;
            border-radius: 10px;
            padding: 18px 20px;
            margin-bottom: 16px;
        }

        .page-header h1 {
            margin: 0;
            font-size: 24px;
        }

        .page-header p {
            margin: 6px 0 0;
            font-size: 14px;
            color: #6b7280;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .search-box {
            width: 320px;
            max-width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
        }

        .back-link {
            text-decoration: none;
            background: #374151;
            color: white;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .sheet-card {
            background: white;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            overflow: hidden;
        }

        .table-scroll {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 2050px;
            border-collapse: collapse;
            font-size: 13px;
        }

        th {
            background: #dbeafe;
            color: #111827;
            font-weight: bold;
            text-align: center;
            border: 1px solid #b6c3d5;
            padding: 10px 8px;
            white-space: nowrap;
        }

        td {
            border: 1px solid #d1d5db;
            padding: 9px 8px;
            white-space: nowrap;
            vertical-align: middle;
            background: white;
        }

        tbody tr:nth-child(even) td {
            background: #f9fafb;
        }

        tbody tr:hover td {
            background: #eef6ff;
        }

        .row-number {
            text-align: center;
            font-weight: bold;
        }

        .queue-number {
            font-weight: bold;
            color: #1d4ed8;
            text-align: center;
        }

        .status-badge {
            display: inline-block;
            min-width: 130px;
            text-align: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-step-2 {
            background: #fef3c7;
            color: #92400e;
        }

        .status-called-2 {
            background: #fde68a;
            color: #78350f;
        }

        .status-step-3 {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-called-3 {
            background: #bfdbfe;
            color: #1e3a8a;
        }

        .status-paid {
            background: #dcfce7;
            color: #166534;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-none {
            background: #e5e7eb;
            color: #374151;
        }

        .action-group {
            display: flex;
            gap: 6px;
            justify-content: center;
        }

        .action-group form {
            margin: 0;
        }

        .btn {
            border: none;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 12px;
            color: white;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-regular {
            background: #16a34a;
        }

        .btn-priority {
            background: #dc2626;
        }

        .btn-regenerate {
            background: #f97316;
        }

        .btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .btn:hover:not(:disabled) {
            opacity: 0.9;
        }

        .footer-note {
            padding: 10px 12px;
            font-size: 12px;
            color: #6b7280;
            background: #f9fafb;
            border-top: 1px solid #d1d5db;
        }

        .empty-state {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <div class="page-header">
            <h1>Verifier Step 1</h1>
            <p>Beneficiary verification and queue number generation screen</p>
        </div>

        <div class="toolbar">
            <input
                type="text"
                id="searchInput"
                class="search-box"
                placeholder="Search beneficiary..."
            >

            <a href="dashboard.php" class="back-link">
                <span class="material-icons" style="font-size:18px;">arrow_back</span>
                Back to Dashboard
            </a>
        </div>

        <div class="sheet-card">
            <div class="table-scroll">
                <table id="verifierTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Last Name</th>
                            <th>First Name</th>
                            <th>Middle Name</th>
                            <th>Extn. Name</th>
                            <th>Region</th>
                            <th>Province</th>
                            <th>City/Municipality</th>
                            <th>Barangay</th>
                            <th>Contact No.</th>
                            <th>Birthday</th>
                            <th>Age</th>
                            <th>Sex</th>
                            <th>LGU</th>
                            <th>Generated Queue Number</th>
                            <th>Real-Time Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php $count = 1; ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php
                                    $birthday = '';

                                    if (!empty($row['birthday_month']) && !empty($row['birthday_day']) && !empty($row['birthday_year'])) {
                                        $birthday = $row['birthday_month'] . '/' . $row['birthday_day'] . '/' . $row['birthday_year'];
                                    }

                                    $queueNumber = $row['queue_number'] ?? '';
                                    $workflowStatus = $row['workflow_status'] ?? '';
                                    $displayStatus = displayStatus($workflowStatus);
                                    $statusClass = statusClass($workflowStatus);

                                    $hasQueue = !empty($queueNumber);
                                ?>

                                <tr>
                                    <td class="row-number"><?php echo $count++; ?></td>
                                    <td><?php echo e($row['last_name']); ?></td>
                                    <td><?php echo e($row['first_name']); ?></td>
                                    <td><?php echo e($row['middle_name']); ?></td>
                                    <td><?php echo e($row['ext_name']); ?></td>
                                    <td><?php echo e($row['region']); ?></td>
                                    <td><?php echo e($row['province']); ?></td>
                                    <td><?php echo e($row['city_municipality']); ?></td>
                                    <td><?php echo e($row['barangay']); ?></td>
                                    <td><?php echo e($row['contact_number']); ?></td>
                                    <td><?php echo e($birthday); ?></td>
                                    <td><?php echo e($row['age']); ?></td>
                                    <td><?php echo e($row['sex']); ?></td>
                                    <td><?php echo e($row['lgu']); ?></td>

                                    <td class="queue-number">
                                        <?php echo $hasQueue ? e($queueNumber) : 'Not generated'; ?>
                                    </td>

                                    <td>
                                        <span class="status-badge <?php echo e($statusClass); ?>">
                                            <?php echo e($displayStatus); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="action-group">
                                            <form method="POST" action="../api/generate_regular_qn.php" onsubmit="return confirm('Generate regular queue number for this beneficiary?');">
                                                <input type="hidden" name="beneficiary_id" value="<?php echo e($row['id']); ?>">
                                                <button type="submit" class="btn btn-regular" <?php echo $hasQueue ? 'disabled' : ''; ?>>
                                                    Generate Regular QN
                                                </button>
                                            </form>

                                            <form method="POST" action="../api/generate_priority_qn.php" onsubmit="return confirm('Generate priority queue number for this beneficiary?');">
                                                <input type="hidden" name="beneficiary_id" value="<?php echo e($row['id']); ?>">
                                                <button type="submit" class="btn btn-priority" <?php echo $hasQueue ? 'disabled' : ''; ?>>
                                                    Generate Priority QN
                                                </button>
                                            </form>

                                            <form method="POST" action="../api/regenerate_qn.php" onsubmit="return confirm('Regenerate queue number for this beneficiary?');">
                                                <input type="hidden" name="beneficiary_id" value="<?php echo e($row['id']); ?>">
                                                <button type="submit" class="btn btn-regenerate" <?php echo !$hasQueue ? 'disabled' : ''; ?>>
                                                    Regenerate QN
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="17" class="empty-state">
                                    No beneficiaries found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="footer-note">
                Queue buttons are connected to API endpoints for Member 3:
                generate_regular_qn.php, generate_priority_qn.php, and regenerate_qn.php.
            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById("searchInput");
        const table = document.getElementById("verifierTable");
        const rows = table.getElementsByTagName("tbody")[0].getElementsByTagName("tr");

        searchInput.addEventListener("input", function () {
            const searchValue = searchInput.value.toLowerCase();

            for (let i = 0; i < rows.length; i++) {
                const rowText = rows[i].innerText.toLowerCase();

                if (rowText.includes(searchValue)) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        });
    </script>
</body>
</html>
