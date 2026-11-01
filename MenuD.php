<?php
session_start();
$isClient = (isset($_SESSION['client_id']) && (($_SESSION['role'] ?? '') === 'client'));

require_once __DIR__ . "/Backend/db.php"; // تأكد المسار

// ✅ كل صور الكروت الموجودة في الصفحة (لا نغير أماكنها)
$images = [
  'B3.png','B2.png','B1.png',
  'S3.png','S2.png','S1.png',
  'F1.png','fries.png','F3.png',
  'D1.png','D2.png','D3.png',
  'R1.png','R2.png','R3.png',
  'SI1.png','chese2.png','SI2.png',
];

// ✅ جلب كل المنتجات دفعة واحدة حسب image_path
$escaped = array_map(function($x) use ($conn){
  return "'" . mysqli_real_escape_string($conn, $x) . "'";
}, $images);

/* ✅ نفس استعلامك بس زدنا AVG + COUNT من product_reviews */
$sql = "
  SELECT
    p.id, p.name, p.description, p.price, p.image_path, p.is_active,
    COALESCE(AVG(pr.rating), 0) AS avg_rating,
    COUNT(pr.id) AS reviews_count
  FROM products p
  LEFT JOIN product_reviews pr ON pr.product_id = p.id
  WHERE p.image_path IN (" . implode(",", $escaped) . ")
  GROUP BY p.id, p.name, p.description, p.price, p.image_path, p.is_active
";
$res = mysqli_query($conn, $sql);

$P = []; // map: image_path => product row
if ($res) {
  while ($row = mysqli_fetch_assoc($res)) {
    $P[$row['image_path']] = $row;
  }
}

// ✅ دالة ترسم الكرت فقط إذا المنتج موجود و is_active = 1
function renderCard($img, $fallbackName, $fallbackDesc, $P) {
  if (!isset($P[$img])) return;
  if ((int)$P[$img]['is_active'] !== 1) return;

  $id   = (int)$P[$img]['id'];
  $name = htmlspecialchars($P[$img]['name'] ?? $fallbackName);
  $desc = htmlspecialchars($P[$img]['description'] ?? $fallbackDesc);
  $imgEsc = htmlspecialchars($img);

  $priceVal = (float)($P[$img]['price'] ?? 0);
  $priceTxt = number_format($priceVal, 2);

  // ✅ متوسط التقييم + عدد الريفيوز
  $avg = (float)($P[$img]['avg_rating'] ?? 0);
  $cnt = (int)($P[$img]['reviews_count'] ?? 0);
  $avgTxt = number_format($avg, 1);

  echo '
    <article class="fg-food-card">
      <a class="fg-food-photo" href="product.php?id='.$id.'"><img src="'.$imgEsc.'" alt="'.$name.'" loading="lazy" width="480" height="400"></a>
      <div class="fg-food-content"><div class="fg-food-meta"><span>FRESH FROM OUR KITCHEN</span><span class="fg-rating">★ '.$avgTxt.' <small>('.$cnt.')</small></span></div>
      <h2><a href="product.php?id='.$id.'">'.$name.'</a></h2><p>'.$desc.'</p>
      <div class="fg-food-bottom"><strong>'.$priceTxt.' <small>SAR</small></strong>
      <form action="Backend/cart_add.php" method="POST"><input type="hidden" name="product_id" value="'.$id.'"><input type="hidden" name="qty" value="1"><button type="submit" aria-label="Add '.$name.' to cart">Add <span aria-hidden="true">+</span></button></form></div></div>
    </article>
  ';
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <title>Menu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <style>
      * { box-sizing: border-box; margin: 0; padding: 0; }

      body {
        overflow-y: auto;
        background: linear-gradient(0deg, rgba(0, 0, 0, 0.265)),
          url(Theme.png) center / cover;
      }
      nav {
        display: flex;
        width: 100%;
        height: auto;
        max-height: 130px;
        background-color: rgb(0, 0, 0);
        justify-content: space-between;
        align-items: center;
      }
      nav img { display: block; height: 100%; width: 17%; padding-left: 30px; }
      nav h6 { color: rgba(132, 255, 0, 0); font-size: clamp(26px, 1.2vw, 18px); }
      nav a {
        text-decoration: none;
        font-family: Cambria, Cochin, Georgia, Times, "Times New Roman", serif;
        color: rgb(255, 255, 255);
        padding-right: 50px;
      }
      nav a:hover { color: rgba(139, 0, 0, 0.687); filter: drop-shadow(0 0px 40px rgb(255, 255, 255)); }

      .speach {
        justify-items: center;
        align-items: center;
        font-family: Cambria, Cochin, Georgia, Times, "Times New Roman", serif;
        color: rgb(255, 255, 255);
        padding-top: 3px;
        padding-bottom: 5px;
        box-shadow: 0 20px 20px rgba(255, 255, 255, 0.26);
        background-color: black;
      }
      .speach h1 { font-size: 400%; font-weight: 500; }
      .speach h2 { font-size: clamp(18px, 2.6vw, 40px); color: brown; margin-bottom: 10px; }
      .speach h3 { margin-bottom: 30px; }

      .all { display: flex; height: auto; width: 100%; justify-content: center; gap: 40px; }

      .leftC {
        float: left;
        display: flex;
        width: 46%;
        height: auto;
        align-items: center;
        box-shadow: 0 0px 60px inset rgba(254, 0, 0, 0.037);
        margin-top: 14px;
        gap: 10px;
      }
      .leftC .picc {
        width: 40%;
        height: auto;
        background-color: rgba(255, 255, 255, 0.043);
        border-radius: 19px;
        border: 3px groove rgba(255, 0, 0, 0);
        justify-items: center;
        transition: 0.3s ease;
        overflow: hidden;
      }
      .picc:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 35px rgba(255, 0, 0, 0.25),
          0 4px 10px rgba(255, 255, 255, 0.08) inset;
        transform: scale(1.1);
      }
      .picc img { width: 100%; height: auto; aspect-ratio: 3/3; display: block; }

      .picc h2 {
        color: rgba(255, 253, 243, 0.918);
        font-size: 17px;
        font-family: Cambria, Cochin, Georgia, Times, "Times New Roman", serif;
        margin-top: 8px;
      }
      .picc h3 { color: rgba(255, 255, 253, 0.918); padding-top: 10px; margin-bottom: 10px; font-size: 10px; }

      .picc .butto {
        display: block;
        background: rgba(211, 0, 0, 0.687);
        border-style: groove;
        border-radius: 29px;
        border-width: 1px;
        border-color: rgba(255, 0, 0, 0);
        color: rgb(255, 250, 243);
        font-size: clamp(14px, 1.8vw, 18px);
        font-family: Cambria, Cochin, Georgia, Times, "Times New Roman", serif;
        padding: 10px 12px;
        margin-top: 16px;
        width: 70%;
        box-shadow: 0 0px 30px rgba(168, 0, 0, 0.788);
        margin-bottom: 10px;
        cursor: pointer;
      }

      .type { width: 100%; height: auto; display: flex; align-items: center; margin-top: 30px; }
      .type h1 { display: flex; margin-top: 20px; margin-left: 40px; color: rgb(255, 255, 255); font-family: Cambria, Cochin, Georgia, Times, "Times New Roman", serif; }
      .type hr { display: flex; width: 30%; height: 1px; margin-left: 9px; margin-top: 40px; margin-right: 65px; border: 1px groove red; }
    </style>
  <link rel="stylesheet" href="assets/four-guys.css?v=1">
<script src="assets/four-guys.js?v=1" defer></script>
</head>

  <body class="fg fg-public fg-page-menud" data-page="menud">
    <?php $fgBase = ""; include __DIR__ . "/Includes/header.php"; ?>

<main id="main-content"><section class="fg-menu-hero"><div><span class="fg-eyebrow">SOMETHING FOR EVERY CRAVING</span><h1>Meet your<br><em>next favorite.</em></h1><p>Bold burgers, smoky grills, and sides worth sharing.</p></div><img src="B3.png" alt="Triple cheeseburger" width="600" height="600" fetchpriority="high"></section><div class="fg-menu-tools"><nav class="fg-categories" aria-label="Menu categories"><a href="#burgers">Burgers</a><a href="#grill">From the grill</a><a href="#fries">Fries</a><a href="#desserts">Something sweet</a><a href="#drinks">Drinks & shakes</a><a href="#sides">On the side</a></nav><label class="fg-search"><span aria-hidden="true">⌕</span><input id="fg-menu-search" type="search" placeholder="Find your craving…" aria-label="Search the menu"></label></div><p class="fg-search-empty" hidden>No dishes match your search. Try another name.</p><section class="fg-menu-section" id="burgers"><div class="fg-section-heading"><div><span class="fg-eyebrow">FOUR GUYS FAVORITES</span><h2>Burgers</h2><p>Stacked high. Gone fast.</p></div></div><div class="fg-menu-grid"><?php renderCard('B3.png', '', '', $P); ?><?php renderCard('B2.png', '', '', $P); ?><?php renderCard('B1.png', '', '', $P); ?></div></section><section class="fg-menu-section" id="grill"><div class="fg-section-heading"><div><span class="fg-eyebrow">FOUR GUYS FAVORITES</span><h2>From the grill</h2><p>A little smoke. A lot of flavor.</p></div></div><div class="fg-menu-grid"><?php renderCard('S3.png', '', '', $P); ?><?php renderCard('S2.png', '', '', $P); ?><?php renderCard('S1.png', '', '', $P); ?></div></section><section class="fg-menu-section" id="fries"><div class="fg-section-heading"><div><span class="fg-eyebrow">FOUR GUYS FAVORITES</span><h2>Fries</h2><p>Every great bite needs a sidekick.</p></div></div><div class="fg-menu-grid"><?php renderCard('F1.png', '', '', $P); ?><?php renderCard('fries.png', '', '', $P); ?><?php renderCard('F3.png', '', '', $P); ?></div></section><section class="fg-menu-section" id="desserts"><div class="fg-section-heading"><div><span class="fg-eyebrow">FOUR GUYS FAVORITES</span><h2>Something sweet</h2><p>Save room for a happy ending.</p></div></div><div class="fg-menu-grid"><?php renderCard('D1.png', '', '', $P); ?><?php renderCard('D2.png', '', '', $P); ?><?php renderCard('D3.png', '', '', $P); ?></div></section><section class="fg-menu-section" id="drinks"><div class="fg-section-heading"><div><span class="fg-eyebrow">FOUR GUYS FAVORITES</span><h2>Drinks & shakes</h2><p>Cool down. Sip happy.</p></div></div><div class="fg-menu-grid"><?php renderCard('R1.png', '', '', $P); ?><?php renderCard('R2.png', '', '', $P); ?><?php renderCard('R3.png', '', '', $P); ?></div></section><section class="fg-menu-section" id="sides"><div class="fg-section-heading"><div><span class="fg-eyebrow">FOUR GUYS FAVORITES</span><h2>On the side</h2><p>Good things come on the side.</p></div></div><div class="fg-menu-grid"><?php renderCard('SI1.png', '', '', $P); ?><?php renderCard('chese2.png', '', '', $P); ?><?php renderCard('SI2.png', '', '', $P); ?></div></section></main><?php $fgBase = ""; include __DIR__ . "/Includes/footer.php"; ?>
</body>
</html>