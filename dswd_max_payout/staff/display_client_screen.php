<?php
include('../auth/check.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Display Screen</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #f5f7fb;
            overflow: hidden;
            color: #111827;
        }

        .screen {
            display: grid;
            grid-template-columns: 1fr 1fr;
            width: 100vw;
            height: 100vh;
        }

        .panel {
            padding: 32px;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .panel.assessment {
            border-right: 8px solid #d1d5db;
        }

        .title {
            font-size: 52px;
            font-weight: 900;
            margin-bottom: 18px;
            letter-spacing: 1px;
        }

        .subtitle {
            font-size: 22px;
            color: #4b5563;
            margin-bottom: 28px;
            font-weight: 700;
        }

        .header {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
            font-size: 30px;
            font-weight: 900;
            border-bottom: 5px solid #111827;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }

        .list {
            overflow: hidden;
            flex: 1;
        }

        .row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
            align-items: center;
            padding: 18px 0;
            border-bottom: 2px solid #e5e7eb;
        }

        .queue-number {
            font-size: 58px;
            font-weight: 900;
            color: #0f2f56;
        }

        .counter-number {
            font-size: 58px;
            font-weight: 900;
            text-align: center;
            color: #b91c1c;
        }

        .empty {
            margin-top: 90px;
            text-align: center;
            font-size: 40px;
            font-weight: 800;
            color: #9ca3af;
        }

        .footer {
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
            background: #0f2f56;
            color: white;
            padding: 12px 26px;
            font-size: 20px;
            display: flex;
            justify-content: space-between;
            font-weight: 700;
        }

        @media screen and (max-width: 1200px) {
            .title { font-size: 38px; }
            .subtitle { font-size: 18px; }
            .header { font-size: 24px; }
            .queue-number,
            .counter-number { font-size: 42px; }
        }
    </style>
</head>
<body>

<div class="screen">
    <section class="panel assessment">
        <div class="title">STEP 2</div>
        <div class="subtitle">ASSESSMENT / GIS INTERVIEW</div>

        <div class="header">
            <div>Queueing Number</div>
            <div>Counter</div>
        </div>

        <div id="assessment-list" class="list"></div>
    </section>

    <section class="panel release">
        <div class="title">STEP 3</div>
        <div class="subtitle">PAYOUT / RELEASE</div>

        <div class="header">
            <div>Queueing Number</div>
            <div>Counter</div>
        </div>

        <div id="release-list" class="list"></div>
    </section>
</div>

<div class="footer">
    <span>DSWD Max Payout Queueing and Monitoring System</span>
    <span id="last-updated">Last updated: --</span>
</div>

<script>
    const assessmentList = document.getElementById('assessment-list');
    const releaseList = document.getElementById('release-list');
    const lastUpdated = document.getElementById('last-updated');

    function escapeHTML(value) {
        if (value === null || value === undefined) return '';

        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function renderList(container, items) {
        if (!items || items.length === 0) {
            container.innerHTML = '<div class="empty">No Active Queue</div>';
            return;
        }

        let html = '';

        items.forEach(item => {
            const counterNumber = item.counter_number && Number(item.counter_number) > 0
                ? item.counter_number
                : '-';

            html += `
                <div class="row">
                    <div class="queue-number">${escapeHTML(item.queue_number)}</div>
                    <div class="counter-number">${escapeHTML(counterNumber)}</div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    async function loadClientScreen() {
        try {
            const response = await fetch('../api/client_screen_data.php?ts=' + Date.now());
            const data = await response.json();

            if (!data.success) {
                assessmentList.innerHTML = '<div class="empty">Unable to Load</div>';
                releaseList.innerHTML = '<div class="empty">Unable to Load</div>';
                return;
            }

            renderList(assessmentList, data.assessment);
            renderList(releaseList, data.release);
            lastUpdated.textContent = 'Last updated: ' + data.updated_at;
        } catch (error) {
            console.error(error);
            assessmentList.innerHTML = '<div class="empty">Connection Error</div>';
            releaseList.innerHTML = '<div class="empty">Connection Error</div>';
        }
    }

    loadClientScreen();
    setInterval(loadClientScreen, 3000);
</script>

</body>
</html>
