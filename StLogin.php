<?php
session_start();

// إذا الموظف مسجل دخول من قبل، ودّيه لصفحته مباشرة
if (isset($_SESSION['role'])) {
  $r = $_SESSION['role'];
  if ($r == 'chef')      { header("Location: chef.php"); exit; }
  if ($r == 'cashier')   { header("Location: cashier.php"); exit; }
  if ($r == 'waiter')    { header("Location: Waiter.php"); exit; }
  if ($r == 'manager')   { header("Location: manager.php"); exit; }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <title>Staff Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <style>
      * { box-sizing: border-box; margin: 0; padding: 0; }

      body {
        overflow-y: auto;
        background: linear-gradient(0deg, rgb(255, 255, 255)), url(LightBack.png);
        background-attachment: fixed;
        margin: 0;
        padding: 0;
        align-items: center;
        justify-items: center;
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
      nav img {
        display: block;
        height: 100%;
        width: 17%;
        padding-left: 30px;
        filter: drop-shadow(0 3px 3px rgba(0, 0, 0, 0.594));
      }
      nav h6 {
        color: rgba(132, 255, 0, 0);
        font-size: clamp(26px, 1.2vw, 18px);
      }
      nav a {
        text-decoration: none;
        font-family: Cambria, Cochin, Georgia, Times, "Times New Roman", serif;
        color: rgb(0, 0, 0);
      }
      nav a:hover {
        color: rgba(139, 0, 0, 0.687);
        filter: drop-shadow(0 0px 40px rgb(255, 255, 255));
      }
      nav form { width: auto; height: auto; }

      .field {
        width: 43%;
        height: 70%;
        max-height: 1200px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.491);
        background-color: rgb(255, 255, 255);
        align-content: center;
        justify-content: center;
        justify-items: center;
        margin-top: 25px;
      }
      .field h1 {
        color: rgb(0, 0, 0);
        font-family: Cambria, Cochin, Georgia, Times, "Times New Roman", serif;
        padding-top: 30px;
        font-size: 60px;
        max-width: 80%;
      }
      .field .here {
        padding-top: 30px;
        width: 87%;
        height: 80%;
        box-shadow: 0 0 8px inset rgba(229, 229, 229, 0);
        justify-self: center;
      }
      ::placeholder {
        color: rgba(0, 0, 0, 0.5);
        font-size: 17px;
        font-weight: 400;
        letter-spacing: 1.5px;
        padding-left: 12px;
      }
      input {
        background-color: rgba(31, 29, 29, 0.041);
        color: #000000;
        border: 1px solid #b3000000;
        border-radius: 8px;
        padding: 20px 0px;
        width: 100%;
        margin-bottom: 40px;
      }
      input:focus { box-shadow: 0 0px 7px red; }

      .here button[type="submit"] {
        width: 100%;
        padding: 15px 0px;
        margin-bottom: 30px;
        border-radius: 20px;
        border-style: none;
        background-color: rgb(214, 0, 0);
        font-family: Arial, Helvetica, sans-serif;
        font-weight: bolder;
        font-size: 30px;
        color: white;
        transition: 0.3s ease;
      }
      .here button[type="submit"]:hover { transform: scale(1.05); }

      .field input[type="checkbox"] {
        width: 5%;
        height: 13px;
        accent-color: rgb(88, 0, 0);
        margin-top: 20px;
        margin-left: 20px;
      }

      h5 {
        color: rgba(0, 0, 0, 0.637);
        display: inline-block;
        margin-right: 40px;
        font-size: 20px;
      }
    </style>
  <link rel="stylesheet" href="assets/four-guys.css?v=1">
<script src="assets/four-guys.js?v=1" defer></script>
</head>

  <body class="fg fg-auth fg-page-stlogin" data-page="stlogin">
    <?php $fgBase = ""; include __DIR__ . "/Includes/header.php"; ?><main class="fg-auth-layout"><aside class="fg-auth-art"><span class="fg-eyebrow">A LITTLE FIRE. A LOT OF FLAVOR.</span><h2>Your next<br>favorite bite<br><em>starts here.</em></h2><img src="B2.png" alt="Four Guys double cheeseburger" width="700" height="700"><span class="fg-auth-art-note">FOUR GUYS · AL-AHSA</span></aside><section class="fg-auth-panel">

    <div class="field">
      <span class="fg-eyebrow">THE FOUR GUYS TEAM</span><h1>Ready for your shift?</h1><p class="fg-form-intro">Sign in to your staff workspace.</p>

      <?php
      if (isset($_GET['error'])) {
        echo "<p style='color:red;font-size:18px;'>Invalid ID or Password ❌</p>";
      }
      ?>

      <form class="here" action="Backend/login_staff.php" method="POST">
        <input type="number" name="ID" placeholder="ID" required />
        <input type="password" name="Password" placeholder="Password" required />
        <button type="submit">LOGIN</button>
      </form>
    </div>

    <!-- أزلنا روابط الدخول المباشر للصفحات لأنها تكسر الصلاحيات -->
  </section></main><?php $fgBase = ""; include __DIR__ . "/Includes/footer.php"; ?>
</body>
</html>
