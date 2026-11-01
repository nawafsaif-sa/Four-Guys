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

/* 1) تأكد أن الطلب للعميل وهو مكتمل فقط */
$stmt = mysqli_prepare($conn, "
  SELECT id, status, created_at
  FROM orders
  WHERE id=? AND user_id=? 
  LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ii", $order_id, $client_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($res);

if (!$order) {
  header("Location: cart.php?err=not_found");
  exit;
}

$st = strtolower((string)$order['status']);
if ($st === 'completed') $st = 'complete';

if ($st !== 'complete') {
  header("Location: cart.php?err=only_complete");
  exit;
}

/* 2) اجلب منتجات الطلب (order_items + products) */
$products = [];
$stmt = mysqli_prepare($conn, "
  SELECT 
    oi.product_id,
    oi.qty,
    p.name,
    p.image_path
  FROM order_items oi
  JOIN products p ON p.id = oi.product_id
  WHERE oi.order_id = ?
  ORDER BY oi.id ASC
");
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

while ($r = mysqli_fetch_assoc($res)) {
  $products[] = $r;
}

if (count($products) === 0) {
  header("Location: cart.php?err=no_items");
  exit;
}

/* 3) اعرف المنتجات اللي العميل قيّمها مسبقاً (حتى ما نخليه يكرر) */
$reviewed = []; // product_id => true
$ids = array_map(fn($x)=>(int)$x['product_id'], $products);
$in  = implode(",", $ids);

$q = "
  SELECT product_id
  FROM product_reviews
  WHERE client_id = $client_id
    AND product_id IN ($in)
";
$r = mysqli_query($conn, $q);
if ($r) {
  while ($x = mysqli_fetch_assoc($r)) {
    $reviewed[(int)$x['product_id']] = true;
  }
}

$ok  = $_GET['ok']  ?? '';
$err = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Rate Order</title>
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

    .title{
      background: rgba(0,0,0,0.75);
      border:1px solid rgba(255,255,255,0.07);
      border-radius: 16px;
      padding: 18px;
      box-shadow: 0 0 30px rgba(255,0,0,0.08);
      margin-bottom: 18px;
    }
    .title h1{ font-size: clamp(24px, 3vw, 38px); font-weight: 700; }
    .title p{ margin-top: 6px; opacity: .9; color: #ffd9d9; font-family: Arial, Helvetica, sans-serif; }

    .alert{
      margin-top: 12px; padding: 10px 12px;
      border-radius: 12px; font-weight: 900;
      font-family: Arial, Helvetica, sans-serif;
      border:1px solid rgba(255,255,255,0.12);
      background: rgba(255,255,255,0.06);
    }
    .alert.ok{border-color: rgba(0,255,128,.35); background: rgba(0,255,128,.10);}
    .alert.err{border-color: rgba(255,0,0,.35); background: rgba(255,0,0,.10);}

    .card{
      background: rgba(0,0,0,0.65);
      border:1px solid rgba(255,255,255,0.07);
      border-radius: 18px;
      box-shadow: 0 0 30px rgba(255,0,0,0.08);
      overflow:hidden;
    }
    .itemsHeader{
      padding: 14px 16px;
      border-bottom: 1px solid rgba(255,255,255,0.07);
      display:flex; justify-content:space-between; align-items:center;
      font-family: Arial, Helvetica, sans-serif;
    }
    .itemsHeader b{font-size: 18px}

    .row{
      display:flex; gap: 14px;
      padding: 14px 16px;
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
    .name{ font-size: 18px; font-weight: 700; margin-bottom: 6px; }
    .meta{opacity:.9; font-size: 12px; font-family: Arial, Helvetica, sans-serif;}

    .formBox{
      margin-top: 10px;
      display:flex;
      flex-direction:column;
      gap:10px;
      font-family: Arial, Helvetica, sans-serif;
    }

    .stars{
      display:flex;
      gap:8px;
      align-items:center;
      flex-wrap:wrap;
    }
    .stars label{
      cursor:pointer;
      padding: 6px 10px;
      border-radius: 999px;
      border:1px solid rgba(255,255,255,0.12);
      background: rgba(255,255,255,0.06);
      font-weight:900;
      font-size:12px;
      user-select:none;
      transition:.2s ease;
    }
    .stars label:hover{transform: translateY(-1px);}
    .stars input{display:none;}
    .stars input:checked + span{
      border-color: rgba(255,200,0,.35);
      background: rgba(255,200,0,.12);
    }

    textarea{
      width: 100%;
      min-height: 80px;
      resize: vertical;
      padding: 10px 12px;
      border-radius: 14px;
      border:1px solid rgba(255,255,255,0.10);
      background: rgba(255,255,255,0.07);
      color:#fff;
      outline:none;
    }

    .btn{
      background: rgba(211, 0, 0, 0.82);
      border-style: groove;
      border-radius: 16px;
      border-width: 1px;
      border-color: rgba(255, 0, 0, 0);
      color: rgb(255, 250, 243);
      font-size: 14px;
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
    .btn:hover{transform: translateY(-1px); filter: drop-shadow(0 0 10px rgba(255,255,255,0.07));}
    .btn.small{padding: 10px 12px; border-radius: 14px; font-size: 13px;}
    .btn.gray{background: rgba(255,255,255,0.10); box-shadow:none;}
    .btnsLine{display:flex; gap:10px; flex-wrap:wrap;}
    .done{
      margin-top: 8px;
      display:inline-block;
      padding: 6px 10px;
      border-radius: 999px;
      border:1px solid rgba(0,255,128,.35);
      background: rgba(0,255,128,.10);
      font-family: Arial, Helvetica, sans-serif;
      font-weight:900;
      font-size:12px;
    }
  </style>
<link rel="stylesheet" href="assets/four-guys.css?v=1">
<script src="assets/four-guys.js?v=1" defer></script>
</head>

<body class="fg fg-public fg-page-review_product" data-page="review_product">
  <?php $fgBase = ""; include __DIR__ . "/Includes/header.php"; ?>

  <div class="wrap">
    <div class="title">
      <h1>Rate Order #<?= (int)$order_id ?></h1>
      <p>You can rate products only after the order is <b>complete</b>.</p>

      <?php if ($ok): ?>
        <div class="alert ok">✅ <?= h($ok) ?></div>
      <?php endif; ?>

      <?php if ($err): ?>
        <div class="alert err">❌ <?= h($err) ?></div>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="itemsHeader">
        <b>Products in this order</b>
        <span><?= count($products) ?> item(s)</span>
      </div>

      <?php foreach ($products as $p): ?>
        <?php
          $pid = (int)$p['product_id'];
          $already = isset($reviewed[$pid]);
        ?>
        <div class="row">
          <div class="thumb">
            <img src="<?= h($p['image_path']) ?>" alt="">
          </div>

          <div class="info">
            <div class="name"><?= h($p['name']) ?></div>
            <div class="meta">Qty: <?= (int)$p['qty'] ?></div>

            <?php if ($already): ?>
              <div class="done">Reviewed ✔</div>
            <?php else: ?>
              <form class="formBox" action="Backend/review_store.php" method="POST">
                <input type="hidden" name="order_id" value="<?= (int)$order_id ?>">
                <input type="hidden" name="product_id" value="<?= (int)$pid ?>">

                <div class="stars">
                  <?php for($i=1; $i<=5; $i++): ?>
                    <label>
                      <input type="radio" name="rating" value="<?= $i ?>" <?= $i===5 ? 'checked' : '' ?>>
                      <span><?= str_repeat("★", $i) ?></span>
                    </label>
                  <?php endfor; ?>
                </div>

                <textarea name="comment" placeholder="Write your comment (optional)"></textarea>

                <div class="btnsLine">
                  <button class="btn small" type="submit">Submit Review</button>
                  <a class="btn small gray" href="cart.php">Back</a>
                </div>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>

    </div>
  </div>
<?php $fgBase = ""; include __DIR__ . "/Includes/footer.php"; ?>
</body>
</html>