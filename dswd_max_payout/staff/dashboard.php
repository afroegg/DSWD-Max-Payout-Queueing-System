<?php
include('../auth/check.php');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Dashboard</title>
<link rel="stylesheet" href="../assets/style.css">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>

<body>

<div class="app">

<!-- MAIN -->
<main class="main">

<header class="header">
    <h1>Dashboard</h1>
</header>

<section class="content">

<!-- INDICATORS -->
<div class="stats big">
    <div class="stat">
        <span>WAITING</span>
        <h2 id="waitingCount">0</h2>
    </div>

    <div class="stat">
        <span>SERVING</span>
        <h2 id="servingCount">0</h2>
    </div>

    <div class="stat">
        <span>RELEASED</span>
        <h2 id="releasedCount">0</h2>
    </div>
</div>

<!-- ACTION BUTTONS -->
<div class="actions big">
    <a href="serve_next.php" class="btn primary">
        <span class="material-icons">campaign</span>
        Serve Next
    </a>

    <a href="release_payout.php" class="btn success">
        <span class="material-icons">payments</span>
        Release Payout
    </a>
</div>

<!-- PANELS -->
<div class="dashboard-panels big">

<!-- QUEUE -->
<section class="panel">
    <div class="panel-header">
        <h3>Queue Monitoring</h3>
    </div>

    <div class="table-wrap big">
        <table class="dashboard-table big">
            <thead>
                <tr>
                    <th>Queue No.</th>
                    <th>Name</th>
                    <th>Program</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody id="queueTable"></tbody>
        </table>
    </div>
</section>

<!-- HISTORY -->
<section class="panel">
    <div class="panel-header">
        <h3>Payout Transactions</h3>
    </div>

    <div class="table-wrap big">
        <table class="dashboard-table big">
            <thead>
                <tr>
                    <th>Queue No.</th>
                    <th>Name</th>
                    <th>Amount</th>
                    <th>Batch</th>
                </tr>
            </thead>

            <tbody id="historyTable"></tbody>
        </table>
    </div>
</section>

</div>

</section>
</main>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="logo">ADMIN</div>

    <nav>
        <a href="dashboard.php" class="active">
            <span class="material-icons">dashboard</span> Dashboard
        </a>

        <a href="register_walkin.php">
            <span class="material-icons">person_add</span> Register
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="../auth/logout.php">
            <span class="material-icons">logout</span> Logout
        </a>
    </div>
</aside>

</div>

<!-- AJAX LIVE SCRIPT -->
<script>
async function loadLiveData() {
    try {
        const res = await fetch("../api/live_data.php");
        const data = await res.json();

        // COUNTS
        document.getElementById("waitingCount").innerText = data.waiting;
        document.getElementById("servingCount").innerText = data.serving;
        document.getElementById("releasedCount").innerText = data.released;

        // QUEUE
        let queueHTML = "";
        data.queue.forEach(row => {
            queueHTML += `
                <tr class="${row.status === 'serving' ? 'row-serving' : ''}">
                    <td>${row.queue_number}</td>
                    <td>${row.first_name} ${row.last_name}</td>
                    <td>${row.program_type}</td>
                    <td class="status ${row.status}">
                        ${row.status.toUpperCase()}
                    </td>
                </tr>
            `;
        });
        document.getElementById("queueTable").innerHTML = queueHTML;

        // HISTORY
        let historyHTML = "";
        data.history.forEach(row => {
            historyHTML += `
                <tr>
                    <td>${row.queue_number}</td>
                    <td>${row.first_name} ${row.last_name}</td>
                    <td>₱${parseFloat(row.amount).toFixed(2)}</td>
                    <td>${row.payout_batch}</td>
                </tr>
            `;
        });
        document.getElementById("historyTable").innerHTML = historyHTML;

    } catch (err) {
        console.error("Live update error:", err);
    }
}

// RUN EVERY 3 SECONDS
setInterval(loadLiveData, 3000);

// INITIAL LOAD
loadLiveData();


history.pushState(null, null, location.href);
window.onpopstate = function () {
    history.go(1);
};

</script>

</body>
</html>