<?php
session_start();
$isClient = (isset($_SESSION['client_id']) && (($_SESSION['role'] ?? '') === 'client'));
if (!$isClient) { header("Location: LoginD.php"); exit; }

require_once __DIR__ . "/Backend/db.php";
$client_id = (int)$_SESSION['client_id'];

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$order_id = (int)($_GET['order_id'] ?? 0);
if ($order_id <= 0) {
  header("Location: cart.php?err=bad_order");
  exit;
}

/* 1) تأكد أن الطلب للعميل + مكتمل */
$stmt = mysqli_prepare($conn, "SELECT id, status, created_at FROM orders WHERE id=? AND user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $order_id, $client_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($res);

if (!$order) {
  header("Location: cart.php?err=order_not_found");
  exit;
}

$st = strtolower((string)$order['status']);
if ($st === 'completed') $st = 'complete';

if ($st !== 'complete') {
  header("Location: cart.php?err=only_completed_can_review");
  exit;
}

/* 2) هات منتجات الطلب */
$q = "
  SELECT 
    oi.product_id,
    oi.qty,
    p.name,
    p.image_path
  FROM order_items oi
  JOIN products p ON p.id = oi.product_id
  WHERE oi.order_id = ?
  ORDER BY oi.id ASC
";
$stmt = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$items = [];
while ($r = mysqli_fetch_assoc($res)) {
  $items[] = $r;
}

if (count($items) === 0) {
  header("Location: cart.php?err=no_items_to_review");
  exit;
}

/* 3) هات تقييمات العميل السابقة لهذه المنتجات (إذا موجودة) */
$productIds = array_map(fn($x)=>(int)$x['product_id'], $items);
$in = implode(",", $productIds);

$existing = []; // product_id => [rating, comment]
if ($in !== "") {
  $rq = "
    SELECT product_id, rating, comment
    FROM product_reviews
    WHERE client_id = ? AND product_id IN ($in)
  ";
  $stmt = mysqli_prepare($conn, $rq);
  mysqli_stmt_bind_param($stmt, "i", $client_id);
  mysqli_stmt_execute($stmt);
  $rres = mysqli_stmt_get_result($stmt);
  while ($e = mysqli_fetch_assoc($rres)) {
    $existing[(int)$e['product_id']] = $e;
  }
}

$ok  = $_GET['ok']  ?? '';
$err = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Rate Order #<?= (int)$order_id ?></title>
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
      margin-bottom: 16px;
    }
    .head{
      padding: 16px 18px;
      border-bottom: 1px solid rgba(255,255,255,0.07);
      display:flex; justify-content:space-between; align-items:center;
      gap:10px;
    }
    .head h1{ font-size: clamp(22px, 2.6vw, 34px); }
    .head p{ opacity:.9; color:#ffd9d9; font-family: Arial, Helvetica, sans-serif; font-size: 13px; margin-top:6px; }

    .alert{
      margin: 12px 18px 0;
      padding: 10px 12px;
      border-radius: 12px; font-weight: 900;
      font-family: Arial, Helvetica, sans-serif;
      border:1px solid rgba(255,255,255,0.12);
      background: rgba(255,255,255,0.06);
    }
    .alert.ok{border-color: rgba(0,255,128,.35); background: rgba(0,255,128,.10);}
    .alert.err{border-color: rgba(255,0,0,.35); background: rgba(255,0,0,.10);}

    .row{
      display:flex; gap:14px;
      padding: 14px 18px;
      border-bottom: 1px solid rgba(255,255,255,0.06);
      align-items:flex-start;
    }
    .row:last-child{border-bottom:none}

    .thumb{
      width: 92px; height: 92px; border-radius: 14px; overflow:hidden;
      flex: 0 0 92px;
      border:1px solid rgba(255,255,255,0.08);
      background: rgba(255,255,255,0.03);
    }
    .thumb img{width:100%;height:100%;object-fit:cover;display:block}

    .info{flex:1}
    .name{ font-size: 18px; font-weight: 900; margin-bottom: 6px; }
    .small{ opacity:.85; font-family: Arial, Helvetica, sans-serif; font-size: 13px; }

    .box{
      width: 360px;
      background: rgba(255,255,255,0.05);
      border:1px solid rgba(255,255,255,0.08);
      border-radius: 16px;
      padding: 12px;
      font-family: Arial, Helvetica, sans-serif;
    }
    @media(max-width:980px){ .box{ width:100%; } .row{flex-wrap:wrap;} }

    .label{ font-size: 12px; opacity:.9; margin: 6px 0; }
    select, textarea{
      width: 100%;
      border-radius: 12px;
      border:1px solid rgba(255,255,255,0.10);
      background: rgba(0,0,0,0.35);
      color:#fff;
      padding: 10px 10px;
      outline:none;
      font-size: 14px;
    }
    textarea{ min-height: 90px; resize: vertical; }

    .btn{
      background: rgba(211, 0, 0, 0.82);
      border-style: groove;
      border-radius: 16px;
      border-width: 1px;
      border-color: rgba(255, 0, 0, 0);
      color: rgb(255, 250, 243);
      font-size: 14px;
      font-family: Arial, Helvetica, sans-serif;
      padding: 12px 14px;
      box-shadow: 0 0px 20px rgba(168, 0, 0, 0.55);
      transition: 0.25s ease;
      cursor:pointer;
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap: 8px;
    }
    .btn:hover{transform: translateY(-1px); filter: drop-shadow(0 0 10px rgba(255,255,255,0.07));}
    .btn.wide{width:100%; font-weight: 900;}
    .btn.gray{ background: rgba(255,255,255,0.10); box-shadow:none; }
  </style>
<link rel="stylesheet" href="assets/four-guys.css?v=1">
<script src="assets/four-guys.js?v=1" defer></script>
</head>

<body class="fg fg-public fg-page-rate_order" data-page="rate_order">
  <?php $fgBase = ""; include __DIR__ . "/Includes/header.php"; ?>

  <div class="wrap">

    <div class="card">
      <div class="head">
        <div>
          <h1>Rate Order #<?= (int)$order_id ?></h1>
          <p>Only completed orders can be reviewed • <?= h($order['created_at']) ?></p>
        </div>
        <a class="btn gray" href="cart.php">← Back</a>
      </div>

      <?php if ($ok): ?>
        <div class="alert ok">✅ <?= h($ok) ?></div>
      <?php endif; ?>
      <?php if ($err): ?>
        <div class="alert err">❌ <?= h($err) ?></div>
      <?php endif; ?>

      <form action="Backend/review_submit.php" method="POST">
        <input type="hidden" name="order_id" value="<?= (int)$order_id ?>">

        <?php foreach ($items as $it): ?>
          <?php
            $pid = (int)$it['product_id'];
            $prevRating  = isset($existing[$pid]) ? (int)$existing[$pid]['rating'] : 5;
            $prevComment = isset($existing[$pid]) ? (string)$existing[$pid]['comment'] : '';
          ?>
          <div class="row">
            <div class="thumb">
              <img src="<?= h($it['image_path']) ?>" alt="">
            </div>

            <div class="info">
              <div class="name"><?= h($it['name']) ?></div>
              <div class="small">Quantity in this order: x<?= (int)$it['qty'] ?></div>
              <?php if (isset($existing[$pid])): ?>
                <div class="small" style="margin-top:6px;color:#ffd9d9;">(You already reviewed this product before — you can update it)</div>
              <?php endif; ?>
            </div>

            <div class="box">
              <input type="hidden" name="product_ids[]" value="<?= $pid ?>">

              <div class="label">Rating (1–5)</div>
              <select name="rating_<?= $pid ?>">
                <?php for($i=5;$i>=1;$i--): ?>
                  <option value="<?= $i ?>" <?= ($prevRating===$i?'selected':'') ?>><?= $i ?> ⭐</option>
                <?php endfor; ?>
              </select>

              <div class="label">Comment (optional)</div>
              <textarea name="comment_<?= $pid ?>" placeholder="Write your comment..."><?= h($prevComment) ?></textarea>
            </div>
          </div>
        <?php endforeach; ?>

        <div style="padding: 14px 18px;">
          <button class="btn wide" type="submit">Save Reviews</button>
        </div>
      </form>
    </div>

  </div>
<?php $fgBase = ""; include __DIR__ . "/Includes/footer.php"; ?>
</body>
</html>