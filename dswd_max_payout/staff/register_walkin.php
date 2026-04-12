<?php include('../auth/check.php'); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Register Walk-in</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>

<div class="app">

    <main class="main">
        <header class="header">
            <div class="header-row">
                <h1>Register Walk-in</h1>
                <a href="dashboard.php" class="btn neutral">
                    <span class="material-icons">arrow_back</span>
                    Back to Dashboard
                </a>
            </div>
        </header>

        <section class="content page-content">
            <div class="form-page-card large-form-card">
                <div class="section-title">
                    <h2>Beneficiary Information</h2>
                    <p>Enter the beneficiary details to generate a queue number.</p>
                </div>

                <form action="../api/add_walkin.php" method="POST" class="system-form">
                    <div class="form-grid two-col">
                        <div class="field-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" placeholder="Enter first name" required>
                        </div>

                        <div class="field-group">
                            <label>Middle Name</label>
                            <input type="text" name="middle_name" placeholder="Enter middle name">
                        </div>

                        <div class="field-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" placeholder="Enter last name" required>
                        </div>

                        <div class="field-group">
                            <label>National ID</label>
                            <input type="text" name="national_id" placeholder="Enter national ID">
                        </div>

                        <div class="field-group">
                            <label>Household ID</label>
                            <input type="text" name="household_id" placeholder="Enter household ID">
                        </div>

                        <div class="field-group">
                            <label>Program Type</label>
                            <input type="text" name="program_type" placeholder="e.g. AICS, 4Ps, SLP" required>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn primary">
                            <span class="material-icons">confirmation_number</span>
                            Register & Generate Queue
                        </button>

                        <a href="dashboard.php" class="btn neutral">
                            <span class="material-icons">close</span>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <?php include('sidebar.php'); ?>

</div>

</body>
</html>