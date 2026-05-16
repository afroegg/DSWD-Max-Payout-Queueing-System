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

        <a href="register_walkin.php" class="<?php echo $current === 'register_walkin.php' ? 'active' : ''; ?>">
            <span class="material-icons">person_add</span>
            Register
        </a>

        <a href="assessment_screen.php" class="<?php echo ($current === 'assessment_screen.php' || $current === 'eligibility_form.php') ? 'active' : ''; ?>">
            <span class="material-icons">assignment</span>
            Assessment
        </a>

        <a href="confirmation_screen.php" class="<?php echo $current === 'confirmation_screen.php' ? 'active' : ''; ?>">
            <span class="material-icons">payments</span>
            Confirmation
        </a>

        <a href="guard_check_screen.php" class="<?php echo $current === 'guard_check_screen.php' ? 'active' : ''; ?>">
            <span class="material-icons">verified_user</span>
            Guard Check
        </a>

        <a href="analytics.php" class="<?php echo $current === 'analytics.php' ? 'active' : ''; ?>">
            <span class="material-icons">analytics</span>
            Analytics
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="../auth/logout.php">
            <span class="material-icons">logout</span>
            Logout
        </a>
    </div>
</aside>
