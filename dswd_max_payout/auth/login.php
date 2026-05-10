<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

session_start();
include('../config/db.php');

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, username, password, fullname FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($password === $user['password']) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = !empty($user['fullname']) ? $user['fullname'] : $user['username'];

            header("Location: ../staff/dashboard.php");
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>DSWD System Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: #eef2f7;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-card {
            width: 900px;
            max-width: 92%;
            min-height: 520px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.18);
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
        }

        .login-left {
            padding: 60px 55px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
        }

        .login-title {
            margin-bottom: 26px;
        }

        .login-title h1 {
            margin: 0;
            font-size: 34px;
            letter-spacing: 2px;
            color: #0b2e83;
            font-weight: 800;
        }

        .login-title h1 span {
            color: #e11d2e;
        }

        .login-title p {
            margin: 8px 0 0;
            font-size: 13px;
            color: #4b5563;
        }

        .login-form {
            width: 100%;
        }

        .input-group {
            margin-bottom: 14px;
        }

        .input-group input {
            width: 100%;
            height: 44px;
            border: 1px solid #b7c0cf;
            border-radius: 5px;
            padding: 0 14px;
            font-size: 14px;
            outline: none;
            background: #ffffff;
        }

        .input-group input:focus {
            border-color: #0b2e83;
            box-shadow: 0 0 0 3px rgba(11, 46, 131, 0.12);
        }

        .login-btn {
            width: 100%;
            height: 46px;
            margin-top: 10px;
            border: none;
            border-radius: 999px;
            background: #0b2e83;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1px;
            cursor: pointer;
        }

        .login-btn:hover {
            background: #082466;
        }

        .error-message {
            margin-top: 12px;
            padding: 10px 12px;
            border-radius: 6px;
            background: #fee2e2;
            color: #991b1b;
            font-size: 13px;
            border: 1px solid #fecaca;
        }

        .login-note {
            margin-top: 20px;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
        }

        .login-right {
            position: relative;
            background: #172180;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 35px;
        }

        .login-right::before {
            content: "";
            position: absolute;
            width: 520px;
            height: 520px;
            background: #ed1c24;
            border-radius: 45%;
            left: -240px;
            top: -120px;
            transform: rotate(25deg);
        }

        .login-right::after {
            content: "";
            position: absolute;
            width: 620px;
            height: 420px;
            background: #ed1c24;
            border-radius: 50%;
            right: -260px;
            bottom: -140px;
            transform: rotate(-20deg);
        }

        .logo-panel {
            position: relative;
            z-index: 2;
            width: 260px;
            height: 260px;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 35px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
        }

        .logo-panel img {
            width: 100%;
            height: auto;
            object-fit: contain;
        }

        .system-label {
            position: absolute;
            z-index: 2;
            bottom: 38px;
            color: #ffffff;
            text-align: center;
        }

        .system-label h2 {
            margin: 0;
            font-size: 20px;
            letter-spacing: 1px;
        }

        .system-label p {
            margin: 5px 0 0;
            font-size: 13px;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            body {
                padding: 20px 0;
            }

            .login-card {
                grid-template-columns: 1fr;
            }

            .login-left {
                order: 2;
                padding: 40px 28px;
            }

            .login-right {
                order: 1;
                min-height: 260px;
            }

            .logo-panel {
                width: 170px;
                height: 170px;
                padding: 25px;
            }

            .system-label {
                display: none;
            }
        }
    </style>
</head>

<body>

<div class="login-card">

    <div class="login-left">
        <div class="login-title">
            <h1>LOG <span>IN</span></h1>
            <p>Hi! Log in to start managing the DSWD queueing system.</p>
        </div>

        <form method="POST" class="login-form">
            <div class="input-group">
                <input type="text" name="username" placeholder="Username" required>
            </div>

            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <button type="submit" class="login-btn">LOGIN</button>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
        </form>

        <div class="login-note">
            Authorized personnel only
        </div>
    </div>

    <div class="login-right">
        <div class="logo-panel">
            <img src="../assets/dswd_logo.png" alt="DSWD Logo">
        </div>

        <div class="system-label">
            <h2>DSWD MAX PAYOUT</h2>
            <p>Queueing and Monitoring System</p>
        </div>
    </div>

</div>

</body>
</html>
