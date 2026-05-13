<?php
$current = basename($_SERVER['PHP_SELF']);
$counter = intval($_GET['counter'] ?? 0);
?>

<aside class="sidebar">
    <div class="logo">ADMIN</div>

    <nav>
        <a href="verifier.php" class="<?php echo $current === 'verifier.php' ? 'active' : ''; ?>">
            <span class="material-icons">fact_check</span>
            Verifier [Step 1]
        </a>

        <div class="sidebar-section-title">Counters [Step 2 & 3]</div>

        <?php for ($i = 1; $i <= 10; $i++): ?>
            <a href="counter.php?counter=<?php echo $i; ?>" class="<?php echo ($current === 'counter.php' && $counter === $i) ? 'active' : ''; ?>">
                <span class="material-icons">table_restaurant</span>
                Counter <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <!-- Temporarily hidden until old pages are needed again -->
        <!--
        <a href="dashboard.php" class="<?php echo $current === 'dashboard.php' ? 'active' : ''; ?>">
            <span class="material-icons">dashboard</span>
            Dashboard
        </a>

        <a href="register_walkin.php" class="<?php echo $current === 'register_walkin.php' ? 'active' : ''; ?>">
            <span class="material-icons">person_add</span>
            Register
        </a>
        -->
    </nav>

    <div class="sidebar-footer">
        <a href="../auth/logout.php">
            <span class="material-icons">logout</span>
            Logout
        </a>
    </div>
</aside>

<style>
    .sidebar nav {
        overflow-y: auto;
        padding-right: 4px;
    }

    .sidebar-section-title {
        margin: 18px 0 8px;
        padding: 0 12px;
        font-size: 11px;
        font-weight: 800;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }

    .sidebar nav::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar nav::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar nav::-webkit-scrollbar-thumb {
        background: #475569;
        border-radius: 999px;
    }
</style>
