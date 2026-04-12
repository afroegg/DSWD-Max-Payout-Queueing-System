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

<main class="main">

<header class="header">
    <h1>Dashboard</h1>
</header>

<section class="content">

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

<div class="dashboard-panels big">

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
                    <th>Action</th>
                </tr>
            </thead>

            <tbody id="queueTable"></tbody>
        </table>
    </div>
</section>

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

<?php include('sidebar.php'); ?>

</div>

<script>
async function loadLiveData() {
    try {
        const res = await fetch("../api/live_data.php");
        const data = await res.json();

        document.getElementById("waitingCount").innerText = data.waiting;
        document.getElementById("servingCount").innerText = data.serving;
        document.getElementById("releasedCount").innerText = data.released;

        let queueHTML = "";
        data.queue.forEach(row => {
            let actionHtml = "-";

            if (row.status === "waiting") {
                actionHtml = `
                    <form method="POST" action="../api/remove_queue.php" onsubmit="return confirm('Remove this queue entry?');" style="margin:0;">
                        <input type="hidden" name="queue_id" value="${row.id}">
                        <button type="submit" class="table-remove-btn">Remove</button>
                    </form>
                `;
            }

            queueHTML += `
                <tr class="${row.status === 'serving' ? 'row-serving' : ''}">
                    <td>${row.queue_number}</td>
                    <td>${row.first_name} ${row.last_name}</td>
                    <td>${row.program_type}</td>
                    <td class="status ${row.status}">${row.status.toUpperCase()}</td>
                    <td>${actionHtml}</td>
                </tr>
            `;
        });

        if (!data.queue.length) {
            queueHTML = `
                <tr>
                    <td colspan="5" class="empty-state">No waiting or serving queues.</td>
                </tr>
            `;
        }

        document.getElementById("queueTable").innerHTML = queueHTML;

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

        if (!data.history.length) {
            historyHTML = `
                <tr>
                    <td colspan="4" class="empty-state">No payout transactions yet.</td>
                </tr>
            `;
        }

        document.getElementById("historyTable").innerHTML = historyHTML;

    } catch (err) {
        console.error("Live update error:", err);
    }
}

setInterval(loadLiveData, 3000);
loadLiveData();

window.addEventListener("pageshow", function (event) {
    const nav = performance.getEntriesByType("navigation");
    if (event.persisted || (nav.length && nav[0].type === "back_forward")) {
        window.location.reload();
    }
});
</script>

</body>
</html>
