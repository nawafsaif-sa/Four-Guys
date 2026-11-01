<?php
session_start();
$isClient = (isset($_SESSION['client_id']) && (($_SESSION['role'] ?? '') === 'client'));
if (!$isClient) { header("Location: LoginD.php"); exit; }

require_once __DIR__ . "/Backend/db.php";

$client_id = (int)$_SESSION['client_id'];

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

/* اجلب آخر 10 طلبات للعميل */
$stmt = mysqli_prepare($conn, "
  SELECT id, status, created_at
  FROM orders
  WHERE client_id=?
  ORDER BY id DESC
  LIMIT 10
");
mysqli_stmt_bind_param($stmt, "i", $client_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$orders = [];
while ($o = mysqli_fetch_assoc($res)) {
  $orders[] = $o;
}

$ok = $_GET['ok'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>My Orders</title>
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
      display:flex; width:100%; max-height:130px; background:#000;
      justify-content:space-between; align-items:center; gap:24px; padding-right:20px;
    }
    nav img{display:block;height:100%;width:17%;padding-left:30px}
    nav h6{font-size:clamp(18px, 1.2vw, 18px); font-weight:400;}
    nav a{color:#fff;text-decoration:none;transition:.3s}
    nav a:hover{color: rgba(139,0,0,.9); filter: drop-shadow(0 0px 40px rgb(255,255,255));}

    .wrap{width:min(1100px,92%); margin:26px auto 60px;}
    .box{
      background: rgba(0,0,0,0.65);
      border:1px solid rgba(255,255,255,0.07);
      border-radius: 18px;
      box-shadow: 0 0 30px rgba(255,0,0,0.08);
      overflow:hidden;
      margin-bottom: 16px;
    }
    .head{
      padding: 14px 16px;
      border-bottom: 1px solid rgba(255,255,255,0.07);
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap: 10px;
      flex-wrap: wrap;
    }
    .head b{font-size: 18px}
    .badge{
      padding: 6px 10px;
      border-radius: 999px;
      background: rgba(255,255,255,0.06);
      border:1px solid rgba(255,255,255,0.08);
      font-size: 12px;
      font-family: Arial, Helvetica, sans-serif;
    }
    .items{padding: 12px 16px;}
    .it{
      display:flex; justify-content:space-between; gap:12px;
      padding: 10px 0;
      border-bottom:1px dashed rgba(255,255,255,0.12);
      font-family: Arial, Helvetica, sans-serif;
    }
    .it:last-child{border-bottom:none}
    .muted{opacity:.85}
    .ok{
      margin-bottom: 12px;
      padding: 10px 12px;
      border-radius: 12px;
      border:1px solid rgba(0,255,128,.35);
      background: rgba(0,255,128,.10);
      font-family: Arial, Helvetica, sans-serif;
      font-weight: 900;
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
      cursor:pointer;
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      justify-content:center;
    }
  </style>
<link rel="stylesheet" href="../assets/four-guys.css?v=1">
<script src="../assets/four-guys.js?v=1" defer></script>
</head>
<body class="fg fg-public fg-page-my_orders" data-page="my_orders">
  <?php $fgBase = "../"; include __DIR__ . "/../Includes/header.php"; ?>

  <div class="wrap">
    <?php if ($ok): ?>
      <div class="ok">✅ Order confirmed and sent to staff</div>
    <?php endif; ?>

    <?php if (count($orders) === 0): ?>
      <div class="box">
        <div class="head"><b>No orders yet</b><span class="badge">Start from Menu</span></div>
        <div class="items">
          <a class="btn" href="MenuD.php">Go to Menu</a>
        </div>
      </div>
    <?php endif; ?>

    <?php foreach ($orders as $o): ?>
      <?php
        $order_id = (int)$o['id'];

        $stmt = mysqli_prepare($conn, "
          SELECT oi.qty, oi.unit_price, p.name
          FROM order_items oi
          JOIN products p ON p.id = oi.product_id
          WHERE oi.order_id=?
          ORDER BY oi.id ASC
        ");
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        mysqli_stmt_execute($stmt);
        $rr = mysqli_stmt_get_result($stmt);

        $total = 0;
        $lines = [];
        while ($r = mysqli_fetch_assoc($rr)) {
          $line = ((int)$r['qty']) * ((float)$r['unit_price']);
          $total += $line;
          $lines[] = $r + ['line_total'=>$line];
        }
      ?>

      <div class="box">
        <div class="head">
          <b>Order #<?= $order_id ?></b>
          <span class="badge">Status: <?= h($o['status']) ?></span>
          <span class="badge"><?= h($o['created_at']) ?></span>
          <span class="badge">Total: <?= number_format((float)$total, 2) ?> SAR</span>
        </div>

        <div class="items">
          <?php foreach ($lines as $ln): ?>
            <div class="it">
              <div>
                <div><?= h($ln['name']) ?></div>
                <div class="muted">Qty: <?= (int)$ln['qty'] ?> × <?= number_format((float)$ln['unit_price'], 2) ?> SAR</div>
              </div>
              <div><?= number_format((float)$ln['line_total'], 2) ?> SAR</div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php $fgBase = "../"; include __DIR__ . "/../Includes/footer.php"; ?>
</body>
</html>