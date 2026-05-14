<?php
$current = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <div class="logo">ADMIN</div>

    <nav>
        <a href="verifier.php" class="<?php echo $current === 'verifier.php' ? 'active' : ''; ?>">
            <span class="material-icons">fact_check</span>
            Verify [Step 1]
        </a>

        <a href="register_walkin.php" class="<?php echo $current === 'register_walkin.php' ? 'active' : ''; ?>">
            <span class="material-icons">person_add</span>
            Register Walk-in
        </a>

        <a href="counter.php" class="<?php echo ($current === 'counter.php' || $current === 'eligibility_form.php') ? 'active' : ''; ?>">
            <span class="material-icons">assignment</span>
            Assessment / Confirmation
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="../auth/logout.php">
            <span class="material-icons">logout</span>
            Logout
        </a>
    </div>
</aside>
