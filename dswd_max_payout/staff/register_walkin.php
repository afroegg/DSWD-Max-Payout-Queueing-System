<?php include('../auth/check.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Walk-in</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body { background:#f3f6fb; }
        .walkin-wrap { padding:20px; flex:1; overflow:auto; }
        .page-card { background:white; border:1px solid #d6dce8; border-radius:12px; padding:20px; margin-bottom:16px; }
        .page-card h1 { margin:0; color:#111827; font-size:26px; }
        .page-card p { margin:8px 0 0; color:#6b7280; font-size:14px; }
        .form-card { background:white; border:1px solid #d6dce8; border-radius:12px; padding:22px; }
        .section-title { margin:0 0 18px; display:flex; align-items:center; gap:10px; color:#0f2f56; }
        .section-title .material-icons { color:#168fcb; }
        .form-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }
        .field-group { display:flex; flex-direction:column; }
        .field-group label { font-size:13px; font-weight:800; color:#374151; margin-bottom:6px; }
        .field-group input,
        .field-group select { height:44px; padding:0 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:14px; background:white; outline:none; }
        .field-group input:focus,
        .field-group select:focus { border-color:#168fcb; box-shadow:0 0 0 3px rgba(22,143,203,.12); }
        .full { grid-column:1/-1; }
        .two { grid-column:span 2; }
        .form-actions { display:flex; gap:10px; margin-top:22px; flex-wrap:wrap; }
        .save-btn,
        .cancel-btn { min-height:46px; border:none; border-radius:9px; padding:0 18px; font-weight:900; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:7px; }
        .save-btn { background:#168fcb; color:white; }
        .cancel-btn { background:#e5e7eb; color:#111827; }
        .helper-note { background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; padding:12px 14px; border-radius:10px; font-size:13px; font-weight:700; margin-bottom:16px; }
        @media(max-width:1100px){ .form-grid { grid-template-columns:repeat(2,1fr); } .two { grid-column:span 1; } }
        @media(max-width:700px){ .form-grid { grid-template-columns:1fr; } .two { grid-column:span 1; } }
    </style>
</head>
<body>
<div class="app">
    <main class="main">
        <section class="walkin-wrap">
            <div class="page-card">
                <h1>Register Walk-in</h1>
                <p>Create a beneficiary personal record only. Queue numbers are generated after verification in Step 1.</p>
            </div>

            <form action="../api/add_walkin.php" method="POST" class="form-card">
                <div class="helper-note">
                    This registration prevents repeated manual encoding. After saving, search the beneficiary in Verify [Step 1], then generate PAL or PRIO queue number.
                </div>

                <h3 class="section-title"><span class="material-icons">person</span>Personal Information</h3>
                <div class="form-grid">
                    <div class="field-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required>
                    </div>

                    <div class="field-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required>
                    </div>

                    <div class="field-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name">
                    </div>

                    <div class="field-group">
                        <label>Ext. Name</label>
                        <input type="text" name="ext_name" placeholder="Jr., Sr., III">
                    </div>

                    <div class="field-group">
                        <label>Birthday Month *</label>
                        <select name="birthday_month" required>
                            <option value="">Select</option>
                            <option value="1">January</option><option value="2">February</option><option value="3">March</option><option value="4">April</option>
                            <option value="5">May</option><option value="6">June</option><option value="7">July</option><option value="8">August</option>
                            <option value="9">September</option><option value="10">October</option><option value="11">November</option><option value="12">December</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label>Birthday Day *</label>
                        <input type="number" name="birthday_day" min="1" max="31" required>
                    </div>

                    <div class="field-group">
                        <label>Birthday Year *</label>
                        <input type="number" name="birthday_year" min="1900" max="2100" required>
                    </div>

                    <div class="field-group">
                        <label>Age *</label>
                        <input type="number" name="age" min="0" max="130" required>
                    </div>

                    <div class="field-group">
                        <label>Sex *</label>
                        <select name="sex" required>
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact_number" placeholder="09XXXXXXXXX">
                    </div>

                    <div class="field-group">
                        <label>National ID</label>
                        <input type="text" name="national_id">
                    </div>

                    <div class="field-group">
                        <label>Household ID</label>
                        <input type="text" name="household_id">
                    </div>
                </div>

                <h3 class="section-title" style="margin-top:24px;"><span class="material-icons">location_on</span>Address / Program Details</h3>
                <div class="form-grid">
                    <div class="field-group">
                        <label>Region *</label>
                        <input type="text" name="region" value="Region IV-A" required>
                    </div>

                    <div class="field-group">
                        <label>Province *</label>
                        <input type="text" name="province" value="Cavite" required>
                    </div>

                    <div class="field-group">
                        <label>City / Municipality *</label>
                        <input type="text" name="city_municipality" required>
                    </div>

                    <div class="field-group">
                        <label>Barangay *</label>
                        <input type="text" name="barangay" required>
                    </div>

                    <div class="field-group two">
                        <label>LGU *</label>
                        <input type="text" name="lgu" required>
                    </div>

                    <div class="field-group two">
                        <label>Program Type *</label>
                        <input type="text" name="program_type" placeholder="AICS / AKAP / 4Ps / SLP" required>
                    </div>

                    <div class="field-group">
                        <label>SMS Opt-in</label>
                        <select name="sms_opt_in">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="save-btn">
                        <span class="material-icons">save</span>
                        Save Beneficiary Record
                    </button>

                    <a href="verifier.php" class="cancel-btn">
                        <span class="material-icons">arrow_back</span>
                        Back to Verify
                    </a>
                </div>
            </form>
        </section>
    </main>

    <?php include('sidebar.php'); ?>
</div>
</body>
</html>
