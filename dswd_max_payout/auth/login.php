<?php
session_start();
include('../config/db.php');

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, fullname, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // current setup uses plain-text password based on your earlier fix
        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
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
    <link rel="stylesheet" href="../assets/style.css">
</head>


<body class="login-body">

<div class="login-header"></div>

<div class="login-main">
    <div class="login-panel">

        <div class="login-brand">
            <img src="../assets/dswd_logo.png" class="login-logo">
            <h1>DSWD SYSTEM</h1>
            <p>Max Payout Queueing</p>
        </div>

        <form method="POST" class="login-form">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <button class="btn primary">Login</button>
        </form>

    </div>
</div>

</body>

</html>