<?php
session_start();
$isClient = (isset($_SESSION['client_id']) && (($_SESSION['role'] ?? '') === 'client'));

$err = $_GET['err'] ?? '';
$ok  = $_GET['ok'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>contact</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    *{box-sizing:border-box;margin:0;padding:0}
    
    body{
        overflow-y: auto;
        background:linear-gradient(0deg,rgb(0, 0, 0)) ,url(Theme.png) center / cover;
    }
    nav{
        display: flex;
        width: 100%; 
        height: auto;
        max-height: 130px;
        background-color: rgba(0, 0, 0, 0);
        justify-content:space-between;
        align-items: center;
        gap: 24px;
    }
    nav img{
        display:block;
        height: 100%;
        width: 17%;
        padding-left: 30px;
    }
    nav h6{
        color: rgba(132, 255, 0, 0);
        font-size:clamp(26px, 1.2vw, 18px);
    }
    nav a{
        text-decoration: none;
        font-family:Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        color: rgb(255, 255, 255); 
    }
     nav a:hover{
        color:rgba(139, 0, 0, 0.687);
        filter: drop-shadow(0 0px 40px rgb(255, 255, 255));
    }
    nav form{width:auto;height:auto;}
    form button{
        background-color: rgba(219, 0, 0, 0.687);
        padding: 20px 35px;
        margin-right: 20px;
        border-style:groove;
        border-radius: 30px;
        border-width: 1px;
        color: rgb(255, 250, 243);
        font-size: 17px;
        box-shadow: inset 0 0px 40px rgba(255, 0, 0, 0);
        margin-top: 10px;
        cursor:pointer;
    }
    form button:hover{ filter: drop-shadow(0 0px 2px rgba(255, 255, 255, 0.089)); }

    .our{ margin-top:20px; box-shadow: 0 0px 30px rgba(255, 0, 0, 0.194); }
    .our img{ width: 100%; }

    .comcard{
        align-items: center;
        display: flex;
        width: 100%;
        height: 210px;
        justify-content: center;
        gap: 90px;
    }
    .comcard .card{
        display: flex;
        width: auto;
        height: 80%;
        background-color: rgba(255, 255, 255, 0);
        color: white;
    }
    .card img{ width:auto; height:80%; }
    .comcard p{
        padding-top: 20px;
        font-size: 35px;
        font-family:Arial, Helvetica, sans-serif;
    }

    .all{ display:flex; width:100%; height:auto; justify-content:center; gap:100px; }

    .map-container{
        width: 40%;
        height: 600px;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 0 20px rgba(255, 0, 0, 0.096);
    }

    .field{
        width: 40%;
        height: 600px;
        border-radius: 10px;
        overflow: hidden;
        background-color: rgba(255, 255, 255, 0.059);
        align-content: center;
    }
    .field h1{
        color: rgb(255, 255, 255);
        padding-left: 50px;
        font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
    }
    .field .here{ padding-top: 30px; width: 87%; height: 80%; justify-self: center; }

    ::placeholder {
       color: rgba(255, 255, 255, 0.5); 
       font-size: 17px;
       font-weight: 400;
       letter-spacing: 1.5px;
       padding-left: 12px;
    }
    input{
       background-color: #ffffff0e; 
       color: #fff;              
       border: 1px solid #b3000000; 
       border-radius: 8px;
       padding: 12px 12px;
       width: 100%;
       margin-bottom: 20px;
       outline: none;
    }
    textarea{
       background-color: #ffffff0e; 
       color: #fff;              
       border: 1px solid #b3000000; 
       border-radius: 8px;
       padding: 18px 12px;
       width: 100%;
       margin-bottom: 20px;
       outline: none;
       min-height: 160px;
       resize: vertical;
    }
    textarea::placeholder{ padding-left:0px; letter-spacing:1px; }

    .here button[type="submit"]{
        width: 100%;
        padding: 15px 0px;
        border-radius: 8px;
        background-color: red;
        border: none;
        color: white;
        font-weight: 900;
        cursor: pointer;
    }
    .here button[disabled]{ opacity:.5; cursor:not-allowed; }

    .alert{
      width:87%;
      margin: 10px auto 0;
      padding: 10px 12px;
      border-radius: 10px;
      font-weight: 900;
      font-family: Arial, Helvetica, sans-serif;
      color:#fff;
    }
    .alert.ok{background: rgba(0,255,128,.12); border:1px solid rgba(0,255,128,.35);}
    .alert.err{background: rgba(255,0,0,.12); border:1px solid rgba(255,0,0,.35);}

    .fot{ width:100%; max-height:330px; background-color: rgba(138, 138, 138, 0.128); }
    .fot .im{ width:25%; height:auto; }
    .fot .icon{ float:right; width:3.5%; height:auto; margin-top:5px; margin-right:8px; transition:.3s ease; }
    .fot .icon:hover{ transform: scale(1.1); }

    @media (max-width: 1000px){
      .all{flex-direction:column; gap:20px;}
      .map-container,.field{width:92%; margin:0 auto;}
      nav{flex-wrap:wrap; justify-content:center;}
      nav img{width:160px; padding-left:0;}
    }
</style>
<link rel="stylesheet" href="assets/four-guys.css?v=1">
<script src="assets/four-guys.js?v=1" defer></script>
</head>
<body class="fg fg-public fg-page-contact" data-page="contact">
    <?php $fgBase = ""; include __DIR__ . "/Includes/header.php"; ?>

    <section class="fg-page-heading"><span class="fg-eyebrow">PULL UP A CHAIR</span><h1>Let’s talk <em>good food.</em></h1><p>A question, a craving, or just a hello. We’d love to hear from you.</p></section>

    <div class="comcard">
     <div class="card"><img src="call.png" alt=""><p>+966 53 252 6851<br><span style="font-size: 20px;">Available Daily 10AM - 12AM</span></p></div>
     <div class="card"><img src="mail.png" alt=""><p>Email<br><span style="font-size: 20px;">vhgg9183@gmail.com</span></p></div>
     <div class="card"><img src="Loca.png" alt=""><p>Al-hsa,<br><span style="font-size: 20px;">Saudi Arabia-<br>Aljorn</span></p></div>
    </div>

    <div class="all">
        <div class="map-container">
          <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d57609.365798697814!2d49.66035009410068!3d25.518871491044088!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3e37bbff894fffbd%3A0x3b268b11cbe32c8a!2z2KfZhNis2LHZhg!5e0!3m2!1sar!2ssa!4v1760133659649!5m2!1sar!2ssa"
            width="100%" height="800px"
            style="border:0; filter: brightness(0.6) contrast(1.2) grayscale(0.2);"
            allowfullscreen="" loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>

        <div class="field">
            <span class="fg-eyebrow">DROP US A LINE</span><h1>Say hello.</h1>

            <?php if ($err): ?>
              <div class="alert err">
                <?php
                  if ($err === 'login') echo "❌ Please login first to send a message";
                  elseif ($err === 'empty') echo "❌ Please fill all fields";
                  else echo "❌ Failed to send message";
                ?>
              </div>
            <?php endif; ?>

            <?php if ($ok): ?>
              <div class="alert ok">✅ Message sent successfully</div>
            <?php endif; ?>

            <form class="here" action="Backend/contact_send.php" method="POST">
                <input type="text" name="name" placeholder="Name" required>

                <!-- ✅ بدل الإيميل: جوال -->
                <input type="tel" name="phone" placeholder="Phone Number" required>

                <input type="text" name="subject" placeholder="Subject" required>

                <textarea name="message" placeholder="Message" required></textarea>

                <button type="submit" <?php if (!$isClient) echo 'disabled'; ?>>
                  Send The Message
                </button>

                <?php if (!$isClient): ?>
                  <div class="alert err" style="margin-top:12px;">
                    ❌ You must login before sending.
                  </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    
<?php $fgBase = ""; include __DIR__ . "/Includes/footer.php"; ?>
</body>
</html>