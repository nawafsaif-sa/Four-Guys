<?php
session_start();

/* لو العميل مسجل دخول بالفعل (اختياري) */
// if (isset($_SESSION['client_id']) && ($_SESSION['role'] ?? '') === 'client') {
//   header("Location: home.php");
//   exit;
// }

$err = $_GET['err'] ?? '';
$ok  = $_GET['ok'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <style>
      * { box-sizing: border-box; margin: 0; padding: 0; }

      body {
        overflow-y: auto;
        background: url(newLogD.jpeg) center / cover no-repeat;
        background-attachment: fixed;
        margin: 0;
        padding: 0;
      }

      nav {
        display: flex;
        width: 100%;
        height: auto;
        max-height: 130px;
        background-color: rgba(0, 0, 0, 0);
        justify-items: center;
        align-items: center;
        gap: 99px;
        margin-bottom: 0px;
      }
      nav img { display: block; height: 100%; width: 17%; padding-left: 30px; }
      nav h6 { color: rgba(132, 255, 0, 0); font-size: clamp(26px, 1.2vw, 18px); }
      nav a {
        text-decoration: none;
        font-family: Cambria, Cochin, Georgia, Times, "Times New Roman", serif;
        color: rgb(255, 255, 255);
      }
      nav a:hover {
        color: rgba(139, 0, 0, 0.687);
        filter: drop-shadow(0 0px 40px rgb(255, 255, 255));
      }

      .field {
        width: 43%;
        height: 70%;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 0 10px rgba(255, 0, 0, 0.256);
        background-color: rgba(0, 0, 0, 0.354);
        align-content: center;
        justify-content: center;
        margin-left: 30px;
        margin-top: 20px;
        justify-items: center;
        padding-bottom: 20px;
      }
      .field h1 {
        color: rgb(255, 255, 255);
        font-family: Cambria, Cochin, Georgia, Times, "Times New Roman", serif;
        padding-top: 30px;
        font-size: 60px;
        text-align: center;
      }
      .field .here {
        padding-top: 30px;
        width: 87%;
        height: 80%;
        justify-self: center;
        margin: 0 auto;
      }

      ::placeholder {
        color: rgba(255, 255, 255, 0.5);
        font-size: 15px;
        letter-spacing: 1.2px;
        padding-left: 12px;
      }

      input[type="tel"], input[type="password"] {
        background-color: #ffffff0e;
        color: #fff;
        border: 1px solid #b3000000;
        border-radius: 8px;
        padding: 20px 12px;
        width: 100%;
        margin-bottom: 22px;
        outline: none;
      }

      .here button[type="submit"] {
        width: 100%;
        padding: 15px 0px;
        border-radius: 20px;
        border-style: none;
        background-color: rgba(155, 0, 0, 0.71);
        font-family: Arial, Helvetica, sans-serif;
        font-weight: bolder;
        font-size: 30px;
        color: white;
        transition: 0.3s ease;
        cursor: pointer;
      }
      .here button[type="submit"]:hover { transform: scale(1.05); }

      #sighn {
        width: 70%;
        padding: 10px 0px;
        border-radius: 32px;
        border-style: none;
        background-color: rgb(0, 0, 0);
        font-family: Arial, Helvetica, sans-serif;
        font-weight: bolder;
        font-size: 30px;
        color: white;
        border: 2px groove rgba(255, 0, 0, 0);
        margin: 16px auto 0;
        display: block;
        box-shadow: inset 0 0px 6px red;
        cursor: pointer;
      }

      .field input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: rgb(88, 0, 0);
        margin-top: 14px;
        margin-left: 6px;
      }

      h5 {
        color: rgba(255, 255, 255, 0.637);
        display: inline-block;
        margin-right: 10px;
        font-size: 20px;
      }

      .row-links{
        display:flex;
        align-items:center;
        justify-content:space-between;
        margin-top: 8px;
        gap: 10px;
        flex-wrap: wrap;
      }

      .forgot-link{
        text-decoration: none;
        color: rgba(255, 0, 0, 0.75);
        font-size: 20px;
      }
      .forgot-link:hover{ text-decoration: underline; }

      .alert{
        width: 87%;
        margin: 0 auto 14px;
        border-radius: 10px;
        padding: 10px 12px;
        font-family: Arial, Helvetica, sans-serif;
        font-weight: 700;
      }
      .alert.err{
        background: rgba(255, 0, 0, 0.12);
        border: 1px solid rgba(255, 0, 0, 0.35);
        color: #fff;
      }
      .alert.ok{
        background: rgba(0, 255, 128, 0.12);
        border: 1px solid rgba(0, 255, 128, 0.35);
        color: #fff;
      }

      .mode-toggle {
        position: fixed;
        bottom: 25px;
        right: 25px;
        background-color: rgba(0, 0, 0, 0.7);
        color: #fff;
        font-size: 28px;
        text-decoration: none;
        border-radius: 50%;
        width: 55px;
        height: 55px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.4);
        transition: 0.3s ease;
        z-index: 9999;
      }
      .mode-toggle:hover { background-color: rgba(255, 0, 0, 0.8); transform: scale(1.1); }

      @media (max-width: 900px){
        .field{ width: 92%; margin: 16px auto; }
        nav{ gap: 18px; justify-content: center; flex-wrap: wrap; padding: 10px 0; }
        nav img{ width: 140px; padding-left: 0; }
        .field h1{ font-size: 44px; }
      }
    </style>
  <link rel="stylesheet" href="assets/four-guys.css?v=1">
<script src="assets/four-guys.js?v=1" defer></script>
</head>

  <body class="fg fg-auth fg-page-logind" data-page="logind">
    <?php $fgBase = ""; include __DIR__ . "/Includes/header.php"; ?><main class="fg-auth-layout"><aside class="fg-auth-art"><span class="fg-eyebrow">A LITTLE FIRE. A LOT OF FLAVOR.</span><h2>Your next<br>favorite bite<br><em>starts here.</em></h2><img src="B2.png" alt="Four Guys double cheeseburger" width="700" height="700"><span class="fg-auth-art-note">FOUR GUYS · AL-AHSA</span></aside><section class="fg-auth-panel">

    <div class="field">
      <span class="fg-eyebrow">GOOD TO SEE YOU AGAIN</span><h1>Welcome back.</h1><p class="fg-form-intro">Sign in to order your favorites and track your meals.</p>

      <?php if ($err): ?>
        <div class="alert err">
          <?php
            if ($err === 'invalid') echo "❌ Wrong phone or password";
            elseif ($err === 'not_client') echo "❌ This account is not a client account";
            else echo "❌ Login failed";
          ?>
        </div>
      <?php endif; ?>

      <?php if ($ok): ?>
        <div class="alert ok">
          <?php
            if ($ok === 'reset_sent') echo "✅ Reset code sent. Check your email/phone.";
            else echo "✅ Done";
          ?>
        </div>
      <?php endif; ?>

      <form class="here" action="Backend/DLogin.php" method="POST">
        <input type="tel" name="phone" placeholder="Phone Number" required />
        <input type="password" name="password" placeholder="Password" required />
        <input type="hidden" name="next" value="<?= htmlspecialchars($_GET['next'] ?? '') ?>">
        <button type="submit">LOGIN</button>

        <div class="row-links">
          <label>
            <input type="checkbox" name="remember" value="1" />
            <h5>Remember Me</h5>
          </label>

          <a class="forgot-link" href="ForgotPassword.php">Forgot Password?</a>
        </div>

        <!-- ✅ Sign Up لازم ما يكون submit -->
        <button id="sighn" type="button" onclick="window.location.href='SignUp.php'">
          Sign Up
        </button>
      </form>
    </div>

 
  </section></main><?php $fgBase = ""; include __DIR__ . "/Includes/footer.php"; ?>
</body>
</html>
