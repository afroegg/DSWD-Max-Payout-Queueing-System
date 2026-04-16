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
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="login-body">

<div class="login-page-shell">

    <div class="login-top-banner">
        <img src="../assets/header.png" alt="DSWD Header" class="login-top-banner-img">
    </div>


    <div class="login-main-area">
        <div class="login-panel">

            <div class="login-brand">
                <img src="../assets/dswd_logo.png" class="login-logo" alt="DSWD Logo">
                <h1>DSWD SYSTEM</h1>
                <p>Max Payout Queueing</p>
            </div>

            <form method="POST" class="login-form">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>

                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <button type="submit" class="btn primary wide-btn">Login</button>
            </form>

        </div>
    </div>

</div>

</body>
</html>
