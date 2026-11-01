<?php
session_start();
$isClient = (isset($_SESSION['client_id']) && (($_SESSION['role'] ?? '') === 'client'));

require_once __DIR__ . "/Backend/db.php"; // ✅ عدل المسار إذا db.php بمكان ثاني

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// ✅ صور TOP PICKS الموجودة في الهوم عندك
$topImgs = ['B2.png','chese2.png','fries.png','R3.png'];

// ✅ نجيب المنتجات اللي صورها من ضمن هالقائمة
$escaped = array_map(function($x) use ($conn){
  return "'" . mysqli_real_escape_string($conn, $x) . "'";
}, $topImgs);

$sql = "SELECT id, name, description, price, image_path, is_active
        FROM products
        WHERE image_path IN (" . implode(",", $escaped) . ")";
$res = mysqli_query($conn, $sql);

$P = [];
if ($res) {
  while ($row = mysqli_fetch_assoc($res)) {
    $P[$row['image_path']] = $row;
  }
}

// ✅ يرسم الكرت فقط إذا موجود + is_active=1
function renderTopCard($img, $fallbackName, $fallbackDesc, $P) {
  if (!isset($P[$img])) return;
  if ((int)$P[$img]['is_active'] !== 1) return;

  $id   = (int)$P[$img]['id'];
  $name = htmlspecialchars($P[$img]['name'] ?? $fallbackName);
  $desc = htmlspecialchars($P[$img]['description'] ?? $fallbackDesc);
  $imgEsc = htmlspecialchars($img);

  $priceVal = (float)($P[$img]['price'] ?? 0);
  $priceTxt = number_format($priceVal, 2);

  echo '
    <article class="fg-food-card">
      <a class="fg-food-photo" href="product.php?id='.$id.'"><img src="'.$imgEsc.'" alt="'.$name.'" loading="lazy" width="480" height="400"></a>
      <div class="fg-food-content"><div class="fg-food-meta"><span>FRESH FROM OUR KITCHEN</span></div>
      <h2><a href="product.php?id='.$id.'">'.$name.'</a></h2><p>'.$desc.'</p>
      <div class="fg-food-bottom"><strong>'.$priceTxt.' <small>SAR</small></strong>
      <form action="Backend/cart_add.php" method="POST"><input type="hidden" name="product_id" value="'.$id.'"><input type="hidden" name="qty" value="1"><button type="submit" aria-label="Add '.$name.' to cart">Add <span aria-hidden="true">+</span></button></form></div></div>
    </article>
  ';
}

/* =========================
   ✅ Latest Reviews for Home
   ========================= */
function starsTxt($val){
  $v = (int)$val;
  if ($v < 0) $v = 0;
  if ($v > 5) $v = 5;
  return str_repeat("★", $v) . str_repeat("☆", 5-$v);
}

$homeReviews = [];
$q = "
  SELECT
    pr.id AS review_id,
    pr.rating,
    pr.comment,
    pr.created_at,
    p.id AS product_id,
    p.name AS product_name,
    p.image_path,
    (SELECT COUNT(*) FROM review_votes rv WHERE rv.review_id = pr.id AND rv.vote='like') AS likes,
    (SELECT COUNT(*) FROM review_votes rv WHERE rv.review_id = pr.id AND rv.vote='dislike') AS dislikes
  FROM product_reviews pr
  JOIN products p ON p.id = pr.product_id
  ORDER BY pr.id DESC
  LIMIT 50
";
$r = mysqli_query($conn, $q);
if ($r) {
  while ($row = mysqli_fetch_assoc($r)) {
    $homeReviews[] = $row;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <title>Home</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <style>
      * { box-sizing: border-box; margin: 0; padding: 0; }

      body {
        overflow-y: auto;
        background: linear-gradient(0deg, rgba(0, 0, 0, 0.4)),
          url(theme.png) center / cover;
      }
      nav {
        display: flex;
        width: 100%;
        height: auto;
        max-height: 130px;
        background-color: rgb(0, 0, 0);
        justify-content: space-between;
        align-items: center;
        gap: 24px;
      }
      nav img { display: block; height: 100%; width: 17%; padding-left: 30px; }
      nav h6 { color: rgba(132, 255, 0, 0); font-size: clamp(26px, 1.2vw, 18px); }
      nav a {
        text-decoration: none;
        font-family: Cambria, Cochin, Georgia, Times, "Times New Roman", serif;
        color: rgb(255, 255, 255);
        transition: 0.3s ease;
      }
      nav a:hover {
        color: rgba(139, 0, 0, 0.687);
        filter: drop-shadow(0 0px 40px rgb(255, 255, 255));
        transform: scale(1.5);
      }
      nav form { width: auto; height: auto; }
      form button {
        background-color: rgba(219, 0, 0, 0.687);
        padding-top: 20px;
        padding-bottom: 20px;
        padding-right: 35px;
        padding-left: 35px;
        margin-right: 20px;
        border-style: groove;
        border-radius: 30px;
        border-width: 1px;
        color: rgb(255, 250, 243);
        font-size: 17px;
        box-shadow: inset 0 0px 40px rgba(255, 0, 0, 0);
        margin-top: 10px;
        transition: 0.3s ease;
      }
      form button:hover { filter: drop-shadow(0 0px 2px rgba(255, 255, 255, 0.089)); transform: scale(1.1); }

      .head {
        justify-content: center;
        border-top: rgba(255, 255, 255, 0.205);
        border-bottom: 0.5px groove rgba(128, 128, 128, 0.532);
        height: auto;
        box-shadow: 0 0px 20px rgba(255, 255, 255, 0);
        z-index: -1;
        margin-top: 20px;
      }
      .head img {
        display: flex;
        width: 100%;
        height: auto;
        justify-self: center;
        max-width: 1300px;
        filter: drop-shadow(0 10px 6px rgba(255, 255, 255, 0.39));
      }
      .speach {
        justify-items: center;
        align-items: center;
        font-family: Cambria, Cochin, Georgia, Times, "Times New Roman", serif;
        color: rgba(255, 255, 255, 0.918);
        padding-top: 90px;
        padding-bottom: 90px;
      }
      .speach h1 { font-size: clamp(28px, 4vw, 60px); filter: drop-shadow(0 0px 10px rgba(255, 255, 255, 0.192)); }
      .speach h2 { font-size: clamp(18px, 2.6vw, 40px); filter: drop-shadow(0 0px 10px rgba(189, 85, 0, 0.397)); }

      .special {
        display: flex;
        flex-wrap: wrap;
        width: 100%;
        height: 452px;
        align-items: center;
        justify-content: center;
        gap: clamp(50px, 2vw, 70px);
        box-shadow: 0 0px 60px inset rgba(161, 0, 0, 0);
      }
      .special .picc {
        width: 20%;
        height: 90%;
        background-color: rgba(147, 0, 0, 0);
        border-radius: 10px;
        border: 3px groove rgba(255, 0, 0, 0);
        justify-items: center;
        transition: 0.3s ease;
      }
      .picc:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 35px rgba(255, 0, 0, 0.25),
          0 4px 10px rgba(255, 255, 255, 0.08) inset;
        transform: scale(1.1);
      }
      .picc img { width: 100%; height: auto; aspect-ratio: 3/3; display: block; filter: drop-shadow(0 0px 7px rgba(255, 255, 255, 0)); }
      .picc h2 {
        color: rgba(255, 248, 220, 0.918);
        font-size: clamp(30px, 2.2vw, 24px);
        font-family: Cambria, Cochin, Georgia, Times, "Times New Roman", serif;
        filter: drop-shadow(0 0px 5px rgba(255, 227, 194, 0.274));
      }
      .picc h3 { color: rgba(255, 255, 253, 0.918); padding-top: 10px; margin-bottom: 10px; font-size: clamp(20px, 1.6vw, 19px); }
      .picc .butto {
        display: block;
        background: rgba(211, 0, 0, 0.687);
        width: 83%;
        border-style: groove;
        border-radius: 29px;
        border-width: 1px;
        border-color: rgba(255, 0, 0, 0);
        color: rgb(255, 250, 243);
        font-size: clamp(14px, 1.8vw, 18px);
        font-family: Cambria, Cochin, Georgia, Times, "Times New Roman", serif;
        padding: 15px 12px;
        margin-top: 20px;
        box-shadow: 0 0px 30px rgba(168, 0, 0, 0.788);
        transition: 0.3s ease;
        cursor: pointer;
      }

      .footer { width: 100%; justify-content: center; border-bottom-style: none; height: auto; margin-top: 70px; background-color: black; box-shadow: 0 0px 60px rgba(255, 255, 255, 0.103); }
      .footer img { display: flex; width: 80%; justify-self: center; height: auto; }

      .best {
        display: inline;
        padding-left: 60px;
        font-size: clamp(38px, 1.5%, 20px);
        font-family: "Trebuchet MS", "Lucida Sans Unicode", "Lucida Grande", "Lucida Sans", Arial, sans-serif;
        color: white;
        filter: drop-shadow(0 0px 7px rgba(255, 255, 255, 0));
      }
      .fot { width: 100%; height: auto; max-height: 330px; background-color: rgba(138, 138, 138, 0.128); }
      .fot .im { width: 25%; height: auto; box-shadow: inset 0 0px 10px rgba(255, 255, 255, 0); }
      .fot .icon { float: right; width: 3.5%; height: auto; margin-top: 5px; margin-right: 8px; transition: 0.3s ease; }
      .fot .icon:hover { transform: scale(1.1); }

      /* ✅ Reviews section (simple + matching style) */
      .reviewsWrap{ width: min(1100px, 92%); margin: 28px auto 10px; }
      .reviewsCard{
        background: rgba(0,0,0,0.65);
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 18px;
        box-shadow: 0 0 30px rgba(255,0,0,0.08);
        overflow: hidden;
      }
      .reviewsHead{
        padding: 14px 16px;
        border-bottom: 1px solid rgba(255,255,255,0.07);
        display:flex; justify-content:space-between; align-items:center;
        font-family: Arial, Helvetica, sans-serif;
      }
      .reviewsHead b{font-size:18px}
      .reviewsHead span{opacity:.9;color:#ffd9d9}

      .revRow{
        padding: 14px 16px;
        border-bottom: 1px solid rgba(255,255,255,0.06);
        display:flex; gap:14px; align-items:flex-start;
        font-family: Arial, Helvetica, sans-serif;
        color:#fff;
      }
      .revRow:last-child{border-bottom:none}
      .revImg{
        width:70px;height:70px;border-radius:14px;overflow:hidden;
        border:1px solid rgba(255,255,255,0.10);
        background: rgba(255,255,255,0.03);
        flex:0 0 70px;
      }
      .revImg img{width:100%;height:100%;object-fit:cover;display:block}
      .revTop{display:flex; justify-content:space-between; gap:12px}
      .revName{font-weight:900; color:#fff; text-decoration:none}
      .revStars{margin-top:4px; font-weight:900; color:#ffd9d9}
      .revTxt{margin-top:8px; opacity:.95; line-height:1.6}
      .revMeta{margin-top:10px; display:flex; gap:10px; flex-wrap:wrap; font-size:12px; opacity:.95}
      .revPill{
        padding:6px 10px; border-radius:999px;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.08);
      }
      .revDate{opacity:.75; font-size:12px}
      .revEmpty{padding:18px; opacity:.9; font-family: Arial, Helvetica, sans-serif;}

      /* ✅ Buttons for like/dislike (added فقط) */
      .revBtn{
        cursor:pointer;
        color:#fff;
        font-family: Arial, Helvetica, sans-serif;
      }
      .revBtn:disabled{
        opacity:.45;
        cursor:not-allowed;
      }

      /* ✅ show 3 reviews then scroll */
      .reviewsBody{
        max-height: 330px; /* تقريبًا 3 عناصر */
        overflow-y: auto;
      }
      .reviewsBody::-webkit-scrollbar{ width: 10px; }
      .reviewsBody::-webkit-scrollbar-thumb{
        background: rgba(255,255,255,0.18);
        border-radius: 999px;
      }
      .reviewsBody::-webkit-scrollbar-track{
        background: rgba(0,0,0,0.15);
      }
    </style>
  <link rel="stylesheet" href="assets/four-guys.css?v=1">
<script src="assets/four-guys.js?v=1" defer></script>
</head>

  <body class="fg fg-public fg-page-home" data-page="home">
    <?php $fgBase = ""; include __DIR__ . "/Includes/header.php"; ?>

<main id="main-content"><section class="fg-hero"><div class="fg-hero-copy"><span class="fg-eyebrow"><i></i> BIG FLAVOR. ZERO COMPROMISE.</span><h1>Good food.<br>Great mood.<br><em>Four Guys.</em></h1><p>Smoky grills, stacked burgers, and all your favorites. <br>Come hungry. We’ll take care of the rest.</p><div class="fg-hero-actions"><a class="fg-primary" href="MenuD.php">Explore the menu <span>↗</span></a><a class="fg-text-link" href="About.html">Meet Four Guys <span>→</span></a></div><div class="fg-hero-note"><span>01 /</span> MADE TO SATISFY YOUR CRAVINGS</div></div><div class="fg-hero-visual"><span class="fg-orbit">FRESH OFF<br>THE GRILL</span><img src="B2.png" alt="Double cheeseburger with melted cheese" fetchpriority="high" width="1024" height="1024"><div class="fg-hero-caption"><span>THE DOUBLE CHEESEBURGER</span><a href="MenuD.php">Meet your next favorite ↗</a></div></div></section>
<div class="fg-ticker" aria-hidden="true"><span>FIRE UP YOUR APPETITE</span><b>✳</b><span>BIG BITES. BOLD FLAVOR.</span><b>✳</b><span>GOOD TIMES START HERE</span><b>✳</b></div>
<section class="fg-section fg-picks"><div class="fg-section-heading"><div><span class="fg-eyebrow">THE CRAVING STARTS HERE</span><h2>Go on. Pick a favorite.</h2></div><a class="fg-text-link" href="MenuD.php">View full menu ↗</a></div>    <div class="special">
      <?php
        renderTopCard('B2.png',     'Cheeseburger',    'A classic with merly cheese', $P);
        renderTopCard('chese2.png', 'Cheese Bites',    'Golden, crispy, and melted with cheese', $P);
        renderTopCard('fries.png',  'Louded Fries',    'Golden fries topped with melted cheese', $P);
        renderTopCard('R3.png',     'Chocolate Shake', 'Thick, creamy delight', $P);
      ?>
    </div>

</section><section class="fg-story-strip"><img src="S1.png" alt="Flame-grilled steak" loading="lazy"><div><span class="fg-eyebrow">IT’S MORE THAN A MEAL</span><h2>For the love<br>of the grill.</h2><p>A little fire. A lot of flavor. From the first bite to the last fry, we’re here to make your meal a good one.</p><a class="fg-text-link" href="About.html">Discover our story ↗</a></div></section>
    <!-- Latest Reviews -->
    <div class="reviewsWrap">
      <div class="reviewsCard">
        <div class="reviewsHead">
          <b>Latest Reviews</b>
          <span>Customer feedback</span>
        </div>

        <?php if (count($homeReviews) === 0): ?>
          <div class="revEmpty">No reviews yet.</div>
        <?php else: ?>
          <div class="reviewsBody">
            <?php foreach ($homeReviews as $rv): ?>
              <?php $rid = (int)$rv['review_id']; ?>
              <div class="revRow">
                <div class="revImg">
                  <img src="<?= h($rv['image_path']) ?>" alt="">
                </div>

                <div style="flex:1;">
                  <div class="revTop">
                    <div>
                      <span class="revName"><?= h($rv['product_name']) ?></span>
                      <div class="revStars"><?= h(starsTxt((int)$rv['rating'])) ?></div>
                    </div>
                    <div class="revDate"><?= h($rv['created_at']) ?></div>
                  </div>

                  <div class="revTxt"><?= nl2br(h($rv['comment'])) ?></div>

                  <div class="revMeta">
                    <?php if ($isClient): ?>
                      <button class="revPill revBtn" type="button"
                        onclick="voteReview(<?= $rid ?>,'like')">
                        👍 <span id="likeCount<?= $rid ?>"><?= (int)$rv['likes'] ?></span>
                      </button>

                      <button class="revPill revBtn" type="button"
                        onclick="voteReview(<?= $rid ?>,'dislike')">
                        👎 <span id="dislikeCount<?= $rid ?>"><?= (int)$rv['dislikes'] ?></span>
                      </button>
                    <?php else: ?>
                      <span class="revPill">👍 <?= (int)$rv['likes'] ?></span>
                      <span class="revPill">👎 <?= (int)$rv['dislikes'] ?></span>
                    <?php endif; ?>
                  </div>

                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </div>
    </div>

    

    <br /><br />
    <hr style="border-color: black" />

    

    <script>
  async function voteReview(review_id, vote){
    try{
      const body = new URLSearchParams();
      body.append('review_id', String(review_id));
      body.append('vote', String(vote));

      const res = await fetch('/Project/Backend/review_vote.php', {
        method: 'POST',
        credentials: 'same-origin', // ✅ مهم للسيشن
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: body.toString()
      });

      let text = await res.text();

      // ✅ شيل BOM لو موجود + نظّف البداية
      text = text.replace(/^\uFEFF/, '').trim();

      let data;
      try {
        data = JSON.parse(text);
      } catch (e) {
        alert('Server returned non-JSON:\n' + text.slice(0, 300));
        return;
      }

      if(!data.ok){
        alert(data.msg || 'Vote failed');
        return;
      }

      document.getElementById('likeCount' + review_id).textContent = data.likes;
      document.getElementById('dislikeCount' + review_id).textContent = data.dislikes;

    }catch(e){
      alert('Network error');
    }
  }
</script>

  </main><?php $fgBase = ""; include __DIR__ . "/Includes/footer.php"; ?>
</body>
</html>