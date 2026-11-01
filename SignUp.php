<?php
$err = $_GET['err'] ?? '';
$msg = '';

if ($err === 'empty')      $msg = '❌ Please fill all fields';
elseif ($err === 'phone')  $msg = '❌ Phone number already exists';
elseif ($err === 'db')     $msg = '❌ Database error';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <style>
    *{box-sizing:border-box;margin:0;padding:0}

    body{
      background:url(newLogD.jpeg) center/cover no-repeat fixed;
      font-family:Arial;
    }

    nav{
      display:flex;gap:80px;align-items:center;
      padding:20px 30px;
    }
    nav img{width:17%}
    nav a{color:#fff;text-decoration:none;font-size:22px}
    nav a:hover{color:brown}

    .field{
      width:45%;
      margin:30px;
      background:rgba(0,0,0,.35);
      border-radius:12px;
      padding:30px;
      box-shadow:0 0 15px rgba(255,0,0,.3);
    }

    h1{color:#fff;font-size:55px;margin-bottom:20px}

    input{
      width:100%;
      padding:18px;
      margin-bottom:25px;
      border-radius:8px;
      border:none;
      background:#ffffff10;
      color:#fff;
      font-size:18px;
    }

    ::placeholder{color:#ffffff99}

    button{
      width:100%;
      padding:16px;
      border:none;
      border-radius:20px;
      font-size:26px;
      font-weight:bold;
      background:rgba(155,0,0,.8);
      color:#fff;
      cursor:pointer;
      transition:.3s;
    }
    button:hover{transform:scale(1.05)}

    .errbox{
      margin-bottom:15px;
      padding:12px;
      border-radius:10px;
      background:rgba(255,0,0,.15);
      border:1px solid rgba(255,0,0,.4);
      color:#fff;
      font-weight:bold;
    }

    .link{
      display:block;
      margin-top:18px;
      color:#ff8080;
      font-size:20px;
      text-decoration:none;
    }
  </style>
<link rel="stylesheet" href="assets/four-guys.css?v=1">
<script src="assets/four-guys.js?v=1" defer></script>
</head>

<body class="fg fg-auth fg-page-signup" data-page="signup">

<?php $fgBase = ""; include __DIR__ . "/Includes/header.php"; ?><main class="fg-auth-layout"><aside class="fg-auth-art"><span class="fg-eyebrow">A LITTLE FIRE. A LOT OF FLAVOR.</span><h2>Your next<br>favorite bite<br><em>starts here.</em></h2><img src="B2.png" alt="Four Guys double cheeseburger" width="700" height="700"><span class="fg-auth-art-note">FOUR GUYS · AL-AHSA</span></aside><section class="fg-auth-panel">

<div class="field">
  <h1>Create Account</h1>

  <?php if($msg): ?>
    <div class="errbox"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

 <form action="Backend/SignUpClient.php" method="POST">
  <input type="text" name="full_name" placeholder="Full Name" required>
  <input type="tel" name="phone" placeholder="Phone Number" required>
  <input type="password" name="password" placeholder="Password" required>

  <select name="security_question" required style="width:100%;padding:18px;margin-bottom:25px;border-radius:8px;border:none;background:#ffffff10;color:#fff;font-size:18px;">
    <option value="" selected disabled>Choose a security question</option>
    <option value="pet">What is your first pet name?</option>
    <option value="city">What city were you born in?</option>
    <option value="school">What is your first school name?</option>
  </select>

  <input type="text" name="security_answer" placeholder="Security Answer" required>

  <button type="submit">SIGN UP</button>
</form>

  <a class="link" href="LoginD.php">Already have an account? Login</a>
</div>

</section></main><?php $fgBase = ""; include __DIR__ . "/Includes/footer.php"; ?>
</body>
</html>
