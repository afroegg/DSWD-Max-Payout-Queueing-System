<?php
$current = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <div class="logo">ADMIN</div>

    <nav>
        <a href="verifier.php" class="<?php echo $current === 'verifier.php' ? 'active' : ''; ?>">
            <span class="material-icons">fact_check</span>
            Verify
        </a>

        <a href="assessment_screen.php" class="<?php echo ($current === 'assessment_screen.php' || $current === 'eligibility_form.php') ? 'active' : ''; ?>">
            <span class="material-icons">assignment</span>
            Assessment
        </a>

        <a href="confirmation_screen.php" class="<?php echo $current === 'confirmation_screen.php' ? 'active' : ''; ?>">
            <span class="material-icons">payments</span>
            Confirmation
        </a>

        <a href="analytics.php" class="<?php echo $current === 'analytics.php' ? 'active' : ''; ?>">
            <span class="material-icons">analytics</span>
            Analytics
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="../kiosk/index.php" target="_blank">
            <span class="material-icons">touch_app</span>
            Kiosk
        </a>

        <a href="../auth/logout.php">
            <span class="material-icons">logout</span>
            Logout
        </a>
    </div>
</aside>

<style>
/* Counter page icon button override */
.counter-table td:nth-child(4) {
    width: 34% !important;
}

.counter-table .action-buttons {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 8px !important;
    align-items: center !important;
    justify-content: flex-start !important;
}

.counter-table .action-form {
    display: inline-flex !important;
    margin: 0 !important;
}

.counter-table .counter-select {
    width: 110px !important;
    min-height: 38px !important;
    height: 38px !important;
    padding: 0 8px !important;
    border-radius: 8px !important;
    font-size: 12px !important;
    font-weight: 800 !important;
    background: #ffffff !important;
    color: #111827 !important;
}

.counter-table .action-btn {
    width: 38px !important;
    min-width: 38px !important;
    height: 38px !important;
    min-height: 38px !important;
    padding: 0 !important;
    border-radius: 8px !important;
    font-size: 0 !important;
    gap: 0 !important;
    color: #ffffff !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    border: none !important;
    box-shadow: none !important;
    opacity: 1 !important;
}

.counter-table .action-btn .material-icons {
    font-size: 20px !important;
    margin: 0 !important;
}

.counter-table .action-btn.call { background: #16a34a !important; }
.counter-table .action-btn.gis { background: #2563eb !important; }
.counter-table .action-btn.revert { background: #f97316 !important; }
.counter-table .action-btn.assessed { background: #0ea5e9 !important; }
.counter-table .action-btn.paid { background: #9333ea !important; }
.counter-table .action-btn.assessed-paid { background: #14b8a6 !important; }
.counter-table .action-btn.cancel { background: #dc2626 !important; }

.counter-table .action-btn:disabled,
.counter-table .counter-select:disabled {
    opacity: 0.35 !important;
    cursor: not-allowed !important;
    filter: grayscale(0.15) !important;
}

.counter-table .action-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    opacity: 0.9 !important;
}
</style>
