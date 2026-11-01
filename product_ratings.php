<?php
session_start();
require_once __DIR__ . "/Backend/db.php";
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$q = "
  SELECT 
    p.id, p.name, p.image_path,
    COALESCE(ROUND(AVG(r.rating),2), 0) AS avg_rating,
    COUNT(r.id) AS reviews_count
  FROM products p
  LEFT JOIN product_reviews r ON r.product_id = p.id
  WHERE p.is_active = 1
  GROUP BY p.id
  ORDER BY avg_rating DESC, reviews_count DESC, p.id DESC
";
$res = mysqli_query($conn, $q);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Product Ratings</title>
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <style>
    body{background:linear-gradient(0deg, rgba(0,0,0,0.45)), url(theme.png) center/cover;color:#fff;font-family:Arial}
    .wrap{width:min(1100px,92%);margin:26px auto 60px;}
    .card{background:rgba(0,0,0,.65);border:1px solid rgba(255,255,255,.07);border-radius:18px;overflow:hidden}
    .row{display:flex;gap:14px;align-items:center;padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.06)}
    .row:last-child{border-bottom:none}
    .thumb{width:72px;height:72px;border-radius:14px;overflow:hidden;border:1px solid rgba(255,255,255,.08)}
    .thumb img{width:100%;height:100%;object-fit:cover}
    .name{font-weight:900}
    .muted{opacity:.85;font-size:13px}
    .right{margin-left:auto;text-align:right}
    a{color:#fff;text-decoration:none}
    .btn{display:inline-block;padding:10px 12px;border-radius:14px;background:rgba(211,0,0,.82)}
  </style>
<link rel="stylesheet" href="assets/four-guys.css?v=1">
<script src="assets/four-guys.js?v=1" defer></script>
</head>
<body class="fg fg-public fg-page-product_ratings" data-page="product_ratings">
<?php $fgBase = ""; include __DIR__ . "/Includes/header.php"; ?>
  <div class="wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
      <h2 style="margin:0;">Products Ratings</h2>
      <a class="btn" href="home.php">Back</a>
    </div>

    <div class="card">
      <?php while($p = mysqli_fetch_assoc($res)): ?>
        <div class="row">
          <div class="thumb"><img src="<?= h($p['image_path']) ?>" alt=""></div>
          <div>
            <div class="name"><?= h($p['name']) ?></div>
            <div class="muted"><?= (int)$p['reviews_count'] ?> review(s)</div>
          </div>
          <div class="right">
            <div class="name">⭐ <?= h($p['avg_rating']) ?> / 5</div>
            <div class="muted">Average</div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
<?php $fgBase = ""; include __DIR__ . "/Includes/footer.php"; ?>
</body>
</html>