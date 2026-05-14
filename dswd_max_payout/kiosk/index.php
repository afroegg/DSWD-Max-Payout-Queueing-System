<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DSWD Walk-in Kiosk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #eef2f7; color: #111827; min-height: 100vh; }
        .screen { min-height: 100vh; display: none; }
        .screen.active { display: flex; }
        .start-screen { align-items: center; justify-content: center; padding: 30px; background: linear-gradient(135deg, #0b2e83, #168fcb); }
        .start-card { width: 760px; max-width: 96%; background: #fff; border-radius: 28px; padding: 54px 40px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,.28); }
        .logo-circle { width: 120px; height: 120px; border-radius: 50%; margin: 0 auto 22px; display: flex; align-items: center; justify-content: center; background: #eff6ff; color: #0b2e83; }
        .logo-circle .material-icons { font-size: 68px; }
        .start-card h1 { margin: 0; font-size: 42px; color: #00008b; }
        .start-card p { margin: 14px auto 28px; font-size: 18px; color: #374151; line-height: 1.5; max-width: 560px; }
        .tap-btn { border: none; border-radius: 999px; background: #0b2e83; color: #fff; font-size: 24px; font-weight: 900; padding: 20px 48px; cursor: pointer; box-shadow: 0 12px 26px rgba(11,46,131,.28); }
        .tap-btn:active { transform: scale(.98); }
        .form-screen { align-items: stretch; justify-content: center; padding: 24px; }
        .kiosk-shell { width: 1180px; max-width: 100%; display: flex; flex-direction: column; min-height: calc(100vh - 48px); }
        .topbar { background: #0b2e83; color: white; padding: 18px 22px; border-radius: 18px 18px 0 0; display: flex; align-items: center; justify-content: space-between; gap: 14px; }
        .topbar h1 { margin: 0; font-size: 26px; }
        .topbar p { margin: 4px 0 0; opacity: .9; font-size: 14px; }
        .home-btn { border: none; border-radius: 10px; background: rgba(255,255,255,.16); color: white; padding: 12px 16px; font-weight: 900; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .form-card { background: #fff; border: 1px solid #d6dce8; border-top: 0; border-radius: 0 0 18px 18px; padding: 22px; flex: 1; overflow: auto; }
        .helper-note { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 14px 16px; border-radius: 12px; font-size: 15px; font-weight: 800; margin-bottom: 18px; }
        .section-title { margin: 20px 0 14px; color: #0f2f56; display: flex; align-items: center; gap: 8px; font-size: 19px; }
        .section-title:first-of-type { margin-top: 0; }
        .form-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
        .field-group { display: flex; flex-direction: column; }
        .field-group label { font-size: 14px; font-weight: 900; color: #374151; margin-bottom: 7px; }
        .field-group input, .field-group select { height: 50px; padding: 0 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 16px; background: white; outline: none; }
        .field-group input:focus, .field-group select:focus { border-color: #168fcb; box-shadow: 0 0 0 3px rgba(22,143,203,.12); }
        .two { grid-column: span 2; }
        .actions { display: flex; gap: 12px; margin-top: 24px; flex-wrap: wrap; }
        .submit-btn, .clear-btn { min-height: 54px; border: none; border-radius: 12px; padding: 0 24px; font-size: 16px; font-weight: 900; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .submit-btn { background: #168fcb; color: white; }
        .clear-btn { background: #e5e7eb; color: #111827; }
        .success-screen { align-items: center; justify-content: center; padding: 30px; background: #f3f6fb; }
        .success-card { width: 680px; max-width: 96%; background: white; border-radius: 24px; padding: 46px 34px; text-align: center; border: 1px solid #d6dce8; box-shadow: 0 14px 35px rgba(15,23,42,.14); }
        .success-icon { width: 105px; height: 105px; border-radius: 50%; background: #dcfce7; color: #166534; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
        .success-icon .material-icons { font-size: 60px; }
        .success-card h1 { margin: 0; color: #166534; font-size: 34px; }
        .success-card p { color: #374151; font-size: 17px; line-height: 1.5; }
        .code-box { background: #eff6ff; color: #00008b; border: 1px solid #bfdbfe; padding: 14px 18px; border-radius: 12px; font-size: 24px; font-weight: 900; margin: 20px auto; display: inline-block; }
        .done-btn { border: none; border-radius: 999px; background: #0b2e83; color: white; font-size: 18px; font-weight: 900; padding: 16px 34px; cursor: pointer; }
        @media(max-width: 1000px) { .form-grid { grid-template-columns: repeat(2,1fr); } .two { grid-column: span 1; } }
        @media(max-width: 650px) { .start-card h1 { font-size: 32px; } .form-screen { padding: 12px; } .form-grid { grid-template-columns: 1fr; } .topbar { flex-direction: column; align-items: flex-start; } }
    </style>
</head>
<body>

<section id="startScreen" class="screen start-screen active">
    <div class="start-card">
        <div class="logo-circle"><span class="material-icons">touch_app</span></div>
        <h1>DSWD Walk-in Kiosk</h1>
        <p>Register your information here. After submitting, please wait for staff verification and queue number generation.</p>
        <button class="tap-btn" onclick="showForm()">Tap to Start</button>
    </div>
</section>

<section id="formScreen" class="screen form-screen">
    <div class="kiosk-shell">
        <div class="topbar">
            <div>
                <h1>Walk-in Registration</h1>
                <p>Please fill out your personal information carefully.</p>
            </div>
            <button class="home-btn" type="button" onclick="goHome()"><span class="material-icons">home</span> Home</button>
        </div>

        <form action="../api/add_walkin.php" method="POST" class="form-card">
            <input type="hidden" name="source" value="kiosk">

            <div class="helper-note">
                This form only creates your beneficiary record. Staff will verify your name and generate your PAL or PRIO queue number.
            </div>

            <h3 class="section-title"><span class="material-icons">person</span>Personal Information</h3>
            <div class="form-grid">
                <div class="field-group"><label>Last Name *</label><input type="text" name="last_name" required autocomplete="off"></div>
                <div class="field-group"><label>First Name *</label><input type="text" name="first_name" required autocomplete="off"></div>
                <div class="field-group"><label>Middle Name</label><input type="text" name="middle_name" autocomplete="off"></div>
                <div class="field-group"><label>Ext. Name</label><input type="text" name="ext_name" placeholder="Jr., Sr., III" autocomplete="off"></div>

                <div class="field-group"><label>Birthday Month *</label><select name="birthday_month" required><option value="">Select</option><option value="1">January</option><option value="2">February</option><option value="3">March</option><option value="4">April</option><option value="5">May</option><option value="6">June</option><option value="7">July</option><option value="8">August</option><option value="9">September</option><option value="10">October</option><option value="11">November</option><option value="12">December</option></select></div>
                <div class="field-group"><label>Birthday Day *</label><input type="number" name="birthday_day" min="1" max="31" required></div>
                <div class="field-group"><label>Birthday Year *</label><input type="number" name="birthday_year" min="1900" max="2100" required></div>
                <div class="field-group"><label>Age *</label><input type="number" name="age" min="0" max="130" required></div>

                <div class="field-group"><label>Sex *</label><select name="sex" required><option value="">Select</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
                <div class="field-group"><label>Contact Number</label><input type="text" name="contact_number" placeholder="09XXXXXXXXX" autocomplete="off"></div>
                <div class="field-group"><label>National ID</label><input type="text" name="national_id" autocomplete="off"></div>
                <div class="field-group"><label>Household ID</label><input type="text" name="household_id" autocomplete="off"></div>
            </div>

            <h3 class="section-title"><span class="material-icons">location_on</span>Address / Program Details</h3>
            <div class="form-grid">
                <div class="field-group"><label>Region *</label><input type="text" name="region" value="Region IV-A" required></div>
                <div class="field-group"><label>Province *</label><input type="text" name="province" value="Cavite" required></div>
                <div class="field-group"><label>City / Municipality *</label><input type="text" name="city_municipality" required autocomplete="off"></div>
                <div class="field-group"><label>Barangay *</label><input type="text" name="barangay" required autocomplete="off"></div>
                <div class="field-group two"><label>LGU *</label><input type="text" name="lgu" required autocomplete="off"></div>
                <div class="field-group two"><label>Program Type *</label><input type="text" name="program_type" placeholder="AICS / AKAP / 4Ps / SLP" required autocomplete="off"></div>
                <div class="field-group"><label>SMS Opt-in</label><select name="sms_opt_in"><option value="0">No</option><option value="1">Yes</option></select></div>
            </div>

            <div class="actions">
                <button type="submit" class="submit-btn"><span class="material-icons">send</span> Submit Registration</button>
                <button type="reset" class="clear-btn"><span class="material-icons">backspace</span> Clear Form</button>
            </div>
        </form>
    </div>
</section>

<section id="successScreen" class="screen success-screen">
    <div class="success-card">
        <div class="success-icon"><span class="material-icons">check_circle</span></div>
        <h1>Registration Submitted</h1>
        <p>Your information has been saved. Please wait for staff to verify your name and generate your queue number.</p>
        <div class="code-box" id="codeBox">Saved</div>
        <br>
        <button class="done-btn" onclick="goHome()">Done</button>
    </div>
</section>

<script>
    function showOnly(id) {
        document.querySelectorAll('.screen').forEach(screen => screen.classList.remove('active'));
        document.getElementById(id).classList.add('active');
    }

    function showForm() {
        showOnly('formScreen');
    }

    function goHome() {
        document.querySelector('form').reset();
        showOnly('startScreen');
    }

    const params = new URLSearchParams(window.location.search);
    if (params.get('success') === '1') {
        const code = params.get('code') || 'Saved';
        document.getElementById('codeBox').innerText = code;
        showOnly('successScreen');
        window.history.replaceState({}, document.title, 'index.php');
    }
</script>
</body>
</html>
