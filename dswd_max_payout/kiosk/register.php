<?php
include('../config/app.php');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>DSWD QR Self-Registration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{
            margin:0;
            font-family:Arial,sans-serif;
            background:#eef1f4;
            color:#1f2937;
        }
        .wrap{
            max-width:700px;
            margin:0 auto;
            padding:24px 16px 40px;
        }
        .card{
            background:#fff;
            border:1px solid #cfd6de;
            padding:24px;
        }
        h1{
            margin:0 0 8px;
            color:#0f2f56;
            font-size:28px;
        }
        p{
            margin:0 0 18px;
            color:#475569;
        }
        .field{
            margin-bottom:16px;
        }
        label{
            display:block;
            margin-bottom:8px;
            font-weight:600;
            font-size:14px;
        }
        input{
            width:100%;
            box-sizing:border-box;
            height:50px;
            padding:0 14px;
            border:1px solid #94a3b8;
            font-size:15px;
        }
        input:focus{
            outline:none;
            border-color:#168fcb;
        }
        .check{
            display:flex;
            gap:10px;
            align-items:flex-start;
            margin-top:14px;
            margin-bottom:20px;
        }
        .check input{
            width:auto;
            height:auto;
            margin-top:3px;
        }
        button{
            width:100%;
            height:50px;
            border:none;
            background:#168fcb;
            color:#fff;
            font-size:15px;
            font-weight:700;
            cursor:pointer;
        }
        .note{
            margin-top:18px;
            font-size:13px;
            color:#64748b;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>DSWD Max Payout</h1>
        <p>QR Self-Registration</p>

        <form action="submit.php" method="POST">
            <div class="field">
                <label>First Name</label>
                <input type="text" name="first_name" required>
            </div>

            <div class="field">
                <label>Last Name</label>
                <input type="text" name="last_name" required>
            </div>

            <div class="field">
                <label>Mobile Number</label>
                <input type="text" name="contact_number" placeholder="09XXXXXXXXX" required>
            </div>

            <div class="field">
                <label>National ID (optional)</label>
                <input type="text" name="national_id">
            </div>

            <div class="field">
                <label>Household ID (optional)</label>
                <input type="text" name="household_id">
            </div>

            <input type="hidden" name="program_type" value="MAX PAYOUT">

            <div class="check">
                <input type="checkbox" name="sms_opt_in" value="1" id="sms_opt_in">
                <label for="sms_opt_in" style="margin:0;font-weight:normal;">
                    I agree to receive an SMS alert from the system when my queue is almost up.
                </label>
            </div>

            <button type="submit">Get Queue Number</button>
        </form>

        <div class="note">
            After registration, your queue stub will appear on-screen immediately.
        </div>
    </div>
</div>
</body>
</html>