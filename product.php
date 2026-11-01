<?php
session_start();
$isClient = (isset($_SESSION['client_id']) && (($_SESSION['role'] ?? '') === 'client'));
require_once __DIR__ . "/Backend/db.php";

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$product_id = (int)($_GET['id'] ?? 0);
if ($product_id <= 0) { header("Location: MenuD.php"); exit; }

/* جلب المنتج */
$stmt = mysqli_prepare($conn, "SELECT id, name, description, price, image_path, is_active FROM products WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$p = mysqli_fetch_assoc($res);
if (!$p) { header("Location: MenuD.php"); exit; }

/* متوسط التقييم + العدد */
$avg_rating = 0.0;
$reviews_count = 0;

$stmt = mysqli_prepare($conn, "SELECT AVG(rating) AS avg_r, COUNT(*) AS cnt FROM product_reviews WHERE product_id=?");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($res)) {
  $avg_rating = (float)($row['avg_r'] ?? 0);
  $reviews_count = (int)($row['cnt'] ?? 0);
}

/* جلب الريفيوز + عدد اللايك/دسلايك */
$reviews = [];
$q = "
  SELECT
    pr.id,
    pr.client_id,
    pr.rating,
    pr.comment,
    pr.created_at,
    (SELECT COUNT(*) FROM review_votes rv WHERE rv.review_id = pr.id AND rv.vote='like')    AS likes,
    (SELECT COUNT(*) FROM review_votes rv WHERE rv.review_id = pr.id AND rv.vote='dislike') AS dislikes
  FROM product_reviews pr
  WHERE pr.product_id = ?
  ORDER BY pr.id DESC
";
$stmt = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($r = mysqli_fetch_assoc($res)) $reviews[] = $r;

/* لو العميل مسجل دخول: نجيب تصويته الحالي لكل ريفيو (مرة وحدة) */
$userVotes = []; // review_id => like/dislike
if ($isClient && count($reviews) > 0) {
  $ids = array_map(fn($x)=>(int)$x['id'], $reviews);
  $in = implode(",", $ids);
  $uid = (int)$_SESSION['client_id'];

  $r = mysqli_query($conn, "SELECT review_id, vote FROM review_votes WHERE user_id={$uid} AND review_id IN ({$in})");
  if ($r) {
    while ($v = mysqli_fetch_assoc($r)) {
      $userVotes[(int)$v['review_id']] = (string)$v['vote'];
    }
  }
}

function stars($val){
  $val = max(0, min(5, (int)round($val)));
  return str_repeat("★", $val) . str_repeat("☆", 5-$val);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title><?= h($p['name']) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{
      overflow-y:auto;
      background:linear-gradient(0deg, rgba(0,0,0,0.45)), url(theme.png) center/cover;
      color:#fff;
      font-family: Cambria, Cochin, Georgia, Times, "Times New Roman", serif;
    }
    nav{
      display:flex; width:100%; max-height:130px;
      background:#000; justify-content:space-between; align-items:center;
      gap:24px; padding-right: 20px;
    }
    nav img{display:block;height:100%;width:17%;padding-left:30px}
    nav h6{font-size:clamp(18px, 1.2vw, 18px); font-weight: 400;}
    nav a{ text-decoration:none; color:#fff; transition:.3s ease; }
    nav a:hover{ color: rgba(139, 0, 0, 0.9); filter: drop-shadow(0 0px 40px rgb(255, 255, 255)); }

    .wrap{ width:min(1100px, 92%); margin: 26px auto 60px; }

    .card{
      background: rgba(0,0,0,0.65);
      border:1px solid rgba(255,255,255,0.07);
      border-radius: 18px;
      box-shadow: 0 0 30px rgba(255,0,0,0.08);
      overflow:hidden;
      margin-bottom: 18px;
    }
    .head{
      padding: 16px;
      border-bottom: 1px solid rgba(255,255,255,0.07);
      display:flex; justify-content:space-between; align-items:center;
      gap: 14px;
      font-family: Arial, Helvetica, sans-serif;
    }
    .head b{font-size:18px}
    .content{padding:16px}

    .product{
      display:flex; gap:16px; align-items:center;
    }
    .thumb{
      width:140px;height:140px;border-radius:18px;overflow:hidden;
      border:1px solid rgba(255,255,255,0.10);
      background: rgba(255,255,255,0.03);
      flex:0 0 140px;
    }
    .thumb img{width:100%;height:100%;object-fit:cover;display:block}
    .pname{font-size:28px;font-weight:900;margin-bottom:6px}
    .pdesc{opacity:.9;font-family:Arial,Helvetica,sans-serif;margin-bottom:10px}
    .meta{display:flex;gap:10px;flex-wrap:wrap;font-family:Arial,Helvetica,sans-serif}
    .badge{
      padding:6px 10px; border-radius:999px;
      background: rgba(255,255,255,0.06);
      border:1px solid rgba(255,255,255,0.08);
      font-size:12px;
    }

    .reviewRow{
      padding: 14px 16px;
      border-bottom: 1px solid rgba(255,255,255,0.06);
      font-family: Arial, Helvetica, sans-serif;
    }
    .reviewRow:last-child{border-bottom:none}
    .top{
      display:flex; justify-content:space-between; align-items:center;
      gap:12px; margin-bottom:8px;
    }
    .stars{font-weight:900;color:#ffd9d9}
    .date{opacity:.8;font-size:12px}
    .comment{opacity:.95;line-height:1.6}

    .actions{
      margin-top:10px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;
    }
    .btn{
      background: rgba(211, 0, 0, 0.82);
      border-style: groove;
      border-radius: 16px;
      border-width: 1px;
      border-color: rgba(255, 0, 0, 0);
      color: rgb(255, 250, 243);
      font-size: 13px;
      font-family: Arial, Helvetica, sans-serif;
      padding: 10px 12px;
      box-shadow: 0 0px 20px rgba(168, 0, 0, 0.55);
      transition: 0.25s ease;
      cursor:pointer;
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap: 8px;
    }
    .btn:hover{transform: translateY(-1px);}
    .btn.gray{background: rgba(255,255,255,0.10); box-shadow:none;}
    .btn.small{padding: 9px 10px; border-radius: 14px;}
    .activeVote{
      outline: 2px solid rgba(255,255,255,0.35);
      filter: drop-shadow(0 0 10px rgba(255,255,255,0.08));
    }

    .empty{padding:18px; opacity:.9; font-family: Arial, Helvetica, sans-serif;}
  </style>
<link rel="stylesheet" href="assets/four-guys.css?v=1">
<script src="assets/four-guys.js?v=1" defer></script>
</head>

<body class="fg fg-public fg-page-product" data-page="product">
  <?php $fgBase = ""; include __DIR__ . "/Includes/header.php"; ?>

  <div class="wrap">

    <!-- PRODUCT -->
    <div class="card">
      <div class="head">
        <b>Product</b>
        <span style="opacity:.9;">
          <?= number_format($avg_rating, 1) ?> / 5 • <?= (int)$reviews_count ?> review(s)
        </span>
      </div>
      <div class="content">
        <div class="product">
          <div class="thumb"><img src="<?= h($p['image_path']) ?>" alt=""></div>
          <div>
            <div class="pname"><?= h($p['name']) ?></div>
            <div class="pdesc"><?= h($p['description']) ?></div>
            <div class="meta">
              <span class="badge">Price: <?= number_format((float)$p['price'], 2) ?> SAR</span>
              <span class="badge">Stars: <?= h(stars((int)round($avg_rating))) ?></span>
              <?php if ((int)$p['is_active'] !== 1): ?>
                <span class="badge" style="border-color:rgba(255,0,0,.35); background:rgba(255,0,0,.10);">Not available</span>
              <?php endif; ?>
            </div>

            <div style="margin-top:12px;">
              <a class="btn gray" href="MenuD.php">← Back to Menu</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- REVIEWS -->
    <div class="card" id="reviews">
      <div class="head">
        <b>Reviews</b>
        <span style="opacity:.85;">Like/Dislike comments</span>
      </div>

      <?php if (count($reviews) === 0): ?>
        <div class="empty">No reviews yet.</div>
      <?php else: ?>
        <?php foreach ($reviews as $rv): ?>
          <?php
            $rid = (int)$rv['id'];
            $myVote = $userVotes[$rid] ?? '';
          ?>
          <div class="reviewRow">
            <div class="top">
              <div class="stars"><?= h(stars((int)$rv['rating'])) ?></div>
              <div class="date"><?= h($rv['created_at']) ?></div>
            </div>

            <div class="comment"><?= nl2br(h($rv['comment'])) ?></div>

            <div class="actions">
              <span class="badge">👍 <?= (int)$rv['likes'] ?></span>
              <span class="badge">👎 <?= (int)$rv['dislikes'] ?></span>

              <?php if ($isClient): ?>
                <form action="Backend/review_vote.php" method="POST" style="display:inline;">
                  <input type="hidden" name="review_id" value="<?= $rid ?>">
                  <input type="hidden" name="product_id" value="<?= (int)$product_id ?>">
                  <input type="hidden" name="vote" value="like">
                  <button class="btn small gray <?= ($myVote==='like')?'activeVote':'' ?>" type="submit">Like</button>
                </form>

                <form action="Backend/review_vote.php" method="POST" style="display:inline;">
                  <input type="hidden" name="review_id" value="<?= $rid ?>">
                  <input type="hidden" name="product_id" value="<?= (int)$product_id ?>">
                  <input type="hidden" name="vote" value="dislike">
                  <button class="btn small gray <?= ($myVote==='dislike')?'activeVote':'' ?>" type="submit">Dislike</button>
                </form>
              <?php else: ?>
                <span style="opacity:.8;">(Login to vote)</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
<?php $fgBase = ""; include __DIR__ . "/Includes/footer.php"; ?>
</body>
</html>