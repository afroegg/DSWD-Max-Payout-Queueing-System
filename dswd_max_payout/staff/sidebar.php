<?php
$current = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <div class="logo">ADMIN</div>

    <nav>
        <a href="verifier.php" class="<?php echo $current === 'verifier.php' ? 'active' : ''; ?>">
            <span class="material-icons">fact_check</span>
            Verifier [Step 1]
        </a>

        <a href="counter.php" class="<?php echo $current === 'counter.php' ? 'active' : ''; ?>">
            <span class="material-icons">table_restaurant</span>
            Counter List [Step 2 & 3]
        </a>

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
