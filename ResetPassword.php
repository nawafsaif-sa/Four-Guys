<?php
session_start();
require_once __DIR__ . "/Backend/db.php";

if (!isset($_SESSION['reset_user_id'])) {
  header("Location: ForgotPassword.php?e=invalid");
  exit;
}

$e = $_GET['e'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body{background:url(newLogD.jpeg) center/cover no-repeat fixed;font-family:Arial;margin:0;padding:0}
    .box{width:43%;min-width:320px;max-width:520px;margin:40px 0 0 30px;background:rgba(0,0,0,.40);
      border:1px solid rgba(255,0,0,.25);border-radius:12px;box-shadow:0 0 10px rgba(255,0,0,.18);padding:22px;color:#fff}
    h1{text-align:center;margin:0 0 14px;font-size:42px}
    input{width:100%;padding:16px 12px;border-radius:10px;border:1px solid rgba(255,255,255,.15);
      background:#ffffff10;color:#fff;outline:none;margin-top:10px}
    button{width:100%;margin-top:12px;padding:14px 0;border:none;border-radius:18px;background:rgba(155,0,0,.75);
      color:#fff;font-weight:900;font-size:22px;cursor:pointer}
    .err{margin:0 0 10px;background:rgba(255,0,0,.14);border:1px solid rgba(255,0,0,.30);padding:10px;border-radius:10px;font-weight:900}
    a{display:inline-block;margin-top:10px;color:rgba(255,0,0,.85);text-decoration:none;font-weight:900}
  </style>
<link rel="stylesheet" href="assets/four-guys.css?v=1">
<script src="assets/four-guys.js?v=1" defer></script>
</head>
<body class="fg fg-auth fg-page-resetpassword" data-page="resetpassword">
<?php $fgBase = ""; include __DIR__ . "/Includes/header.php"; ?><main class="fg-auth-layout"><aside class="fg-auth-art"><span class="fg-eyebrow">A LITTLE FIRE. A LOT OF FLAVOR.</span><h2>Your next<br>favorite bite<br><em>starts here.</em></h2><img src="B2.png" alt="Four Guys double cheeseburger" width="700" height="700"><span class="fg-auth-art-note">FOUR GUYS · AL-AHSA</span></aside><section class="fg-auth-panel">
  <div class="box">
    <h1>Reset Password</h1>

    <?php if($e==='empty'): ?><div class="err">❌ Enter new password</div><?php endif; ?>

    <form action="Backend/reset_password.php" method="POST">
      <input type="password" name="new_password" placeholder="New Password" required>
      <button type="submit">Change Password</button>
    </form>

    <a href="LoginD.php">← Back to Login</a>
  </div>
</section></main><?php $fgBase = ""; include __DIR__ . "/Includes/footer.php"; ?>
</body>
</html>