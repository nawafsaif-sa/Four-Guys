<?php session_start(); $e = $_GET['e'] ?? ''; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body{background:url(newLogD.jpeg) center/cover no-repeat fixed;font-family:Arial;margin:0;padding:0}
    .box{width:43%;min-width:320px;max-width:520px;margin:40px 0 0 30px;background:rgba(0,0,0,.40);
      border:1px solid rgba(255,0,0,.25);border-radius:12px;box-shadow:0 0 10px rgba(255,0,0,.18);padding:22px;color:#fff}
    h1{text-align:center;margin:0 0 14px;font-size:42px}
    input,select{width:100%;padding:16px 12px;border-radius:10px;border:1px solid rgba(255,255,255,.15);
      background:#ffffff10;color:#fff;outline:none;margin-top:10px}
    input::placeholder{color:rgba(255,255,255,.55)}
    select option{color:#111} /* عشان خيارات السلكت تبان */
    button{width:100%;margin-top:12px;padding:14px 0;border:none;border-radius:18px;background:rgba(155,0,0,.75);
      color:#fff;font-weight:900;font-size:22px;cursor:pointer}
    .err{margin:0 0 10px;background:rgba(255,0,0,.14);border:1px solid rgba(255,0,0,.30);padding:10px;border-radius:10px;font-weight:900}
    a{display:inline-block;margin-top:10px;color:rgba(255,0,0,.85);text-decoration:none;font-weight:900}
    a:hover{text-decoration:underline}
    @media(max-width:900px){.box{width:92%;margin:16px auto;}}
  </style>
<link rel="stylesheet" href="assets/four-guys.css?v=1">
<script src="assets/four-guys.js?v=1" defer></script>
</head>
<body class="fg fg-auth fg-page-forgotpassword" data-page="forgotpassword">
<?php $fgBase = ""; include __DIR__ . "/Includes/header.php"; ?><main class="fg-auth-layout"><aside class="fg-auth-art"><span class="fg-eyebrow">A LITTLE FIRE. A LOT OF FLAVOR.</span><h2>Your next<br>favorite bite<br><em>starts here.</em></h2><img src="B2.png" alt="Four Guys double cheeseburger" width="700" height="700"><span class="fg-auth-art-note">FOUR GUYS · AL-AHSA</span></aside><section class="fg-auth-panel">
  <div class="box">
    <h1>Forgot Password</h1>

    <?php if($e==='empty'): ?><div class="err">❌ Fill all fields</div><?php endif; ?>
    <?php if($e==='notfound'): ?><div class="err">❌ Client not found</div><?php endif; ?>
    <?php if($e==='wrong'): ?><div class="err">❌ Wrong security answer</div><?php endif; ?>

    <form action="Backend/forgot_password.php" method="POST">
      <input type="tel" name="phone" placeholder="Phone Number" required>

      <select name="security_question" required>
        <option value="" selected disabled>Choose your security question</option>
        <option value="pet">What is your first pet name?</option>
        <option value="city">What city were you born in?</option>
        <option value="school">What is your first school name?</option>
      </select>

      <input type="text" name="security_answer" placeholder="Security Answer" required>

      <button type="submit">Next</button>
    </form>

    <a href="LoginD.php">← Back to Login</a>
  </div>
</section></main><?php $fgBase = ""; include __DIR__ . "/Includes/footer.php"; ?>
</body>
</html>