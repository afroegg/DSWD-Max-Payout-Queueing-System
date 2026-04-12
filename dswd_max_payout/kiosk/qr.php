<?php
include('../config/app.php');
$targetUrl = BASE_URL . '/kiosk/register.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>DSWD Kiosk QR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body{
            margin:0;
            font-family:Arial,sans-serif;
            background:#eef1f4;
            color:#1f2937;
        }
        .wrap{
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:24px;
            box-sizing:border-box;
        }
        .card{
            background:#fff;
            border:1px solid #cfd6de;
            padding:28px;
            text-align:center;
            width:100%;
            max-width:520px;
        }
        h1{
            margin:0 0 8px;
            color:#0f2f56;
        }
        p{
            color:#475569;
        }
        #qrcode{
            display:flex;
            justify-content:center;
            margin:22px 0;
        }
        .url{
            font-size:13px;
            word-break:break-all;
            color:#64748b;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>DSWD Max Payout QR Registration</h1>
        <p>Scan this code to get a queue number on your phone.</p>
        <div id="qrcode"></div>
        <div class="url"><?php echo htmlspecialchars($targetUrl); ?></div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById("qrcode"), {
    text: <?php echo json_encode($targetUrl); ?>,
    width: 260,
    height: 260
});
</script>
</body>
</html>