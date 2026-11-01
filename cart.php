<?php
session_start();
$isClient = (isset($_SESSION['client_id']) && (($_SESSION['role'] ?? '') === 'client'));
if (!$isClient) { header("Location: LoginD.php"); exit; }

require_once __DIR__ . "/Backend/db.php";
$client_id = (int)$_SESSION['client_id'];

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

/* =========================
   1) GET OPEN CART (editable)
   pending cart that has NO order yet
   ========================= */
$cart_id = 0;
$stmt = mysqli_prepare($conn, "
  SELECT c.id
  FROM carts c
  LEFT JOIN orders o ON o.cart_id = c.id
  WHERE c.client_id = ? AND c.status='pending' AND o.id IS NULL
  ORDER BY c.id DESC
  LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $client_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($res)) $cart_id = (int)$row['id'];

/* =========================
   2) LOAD CART ITEMS
   ========================= */
$items = [];
$subtotal = 0.0;

if ($cart_id > 0) {
  $stmt = mysqli_prepare($conn, "
    SELECT 
      ci.id   AS item_id,
      ci.qty,
      ci.unit_price,
      p.id    AS product_id,
      p.name,
      p.description,
      p.image_path,
      p.is_active
    FROM cart_items ci
    JOIN products p ON p.id = ci.product_id
    WHERE ci.cart_id = ?
    ORDER BY ci.id DESC
  ");
  mysqli_stmt_bind_param($stmt, "i", $cart_id);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);

  while ($r = mysqli_fetch_assoc($res)) {
    $line = ((float)$r['unit_price']) * ((int)$r['qty']);
    $subtotal += $line;
    $r['line_total'] = $line;
    $items[] = $r;
  }
}

/* =========================
   3) LOAD CLIENT ORDERS (My Orders)
   orders.user_id = client_id عندك
   ========================= */
$orders = [];
$stmt = mysqli_prepare($conn, "
  SELECT id, status, expected_prep_minutes, created_at
  FROM orders
  WHERE user_id = ?
  ORDER BY id DESC
  LIMIT 20
");
mysqli_stmt_bind_param($stmt, "i", $client_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($o = mysqli_fetch_assoc($res)) {
  $orders[] = $o;
}

/* =========================
   4) Load items for those orders (bulk)
   ========================= */
$orderItemsMap = []; // order_id => items[]
if (count($orders) > 0) {
  $ids = array_map(fn($x)=>(int)$x['id'], $orders);
  $in = implode(",", $ids);

  $q = "
    SELECT 
      oi.order_id,
      oi.qty,
      oi.unit_price,
      oi.prep_minutes,
      p.name,
      p.image_path
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id IN ($in)
    ORDER BY oi.order_id DESC, oi.id ASC
  ";
  $r = mysqli_query($conn, $q);
  if ($r) {
    while ($it = mysqli_fetch_assoc($r)) {
      $oid = (int)$it['order_id'];
      if (!isset($orderItemsMap[$oid])) $orderItemsMap[$oid] = [];
      $orderItemsMap[$oid][] = $it;
    }
  }
}

$ok  = $_GET['ok']  ?? '';
$err = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Cart</title>
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
    .title p{
      margin-top: 6px; opacity: .9; color: #ffd9d9;
      font-family: Arial, Helvetica, sans-serif;
    }
    .alert{
      margin-top: 12px; padding: 10px 12px;
      border-radius: 12px; font-weight: 900;
      font-family: Arial, Helvetica, sans-serif;
      border:1px solid rgba(255,255,255,0.12);
      background: rgba(255,255,255,0.06);
    }
    .alert.ok{border-color: rgba(0,255,128,.35); background: rgba(0,255,128,.10);}
    .alert.err{border-color: rgba(255,0,0,.35); background: rgba(255,0,0,.10);}

    .grid{ display:grid; grid-template-columns: 1fr 360px; gap: 18px; }
    @media(max-width: 980px){ .grid{grid-template-columns:1fr;} }

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
    }
    .itemsHeader b{font-size: 18px}
    .itemsHeader span{opacity:.9;color:#ffd9d9; font-family: Arial, Helvetica, sans-serif;}

    .item{
      display:flex; gap: 14px;
      padding: 14px 16px;
      border-bottom: 1px solid rgba(255,255,255,0.06);
      align-items:center;
    }
    .item:last-child{border-bottom:none}
    .thumb{
      width: 92px; height: 92px; border-radius: 14px; overflow:hidden;
      flex: 0 0 92px;
      border:1px solid rgba(255,255,255,0.08);
      background: rgba(255,255,255,0.03);
    }
    .thumb img{width:100%;height:100%;object-fit:cover;display:block}
    .info{flex:1}
    .name{ font-size: 18px; font-weight: 700; margin-bottom: 4px; }
    .desc{opacity:.85; font-family: Arial, Helvetica, sans-serif; font-size: 13px; margin-bottom: 8px;}
    .meta{
      display:flex; gap: 10px; flex-wrap: wrap; align-items:center;
      font-family: Arial, Helvetica, sans-serif; opacity:.95;
    }
    .badge{
      padding: 6px 10px; border-radius: 999px;
      background: rgba(255,255,255,0.06);
      border:1px solid rgba(255,255,255,0.08);
      font-size: 12px;
    }

    .qtyBox{
      display:flex; gap: 10px;
      align-items:center; justify-content:flex-end;
      min-width: 240px;
      font-family: Arial, Helvetica, sans-serif;
    }
    .qtyWrap{
      display:flex; gap: 6px; align-items:center;
      background: rgba(255,255,255,0.06);
      border:1px solid rgba(255,255,255,0.10);
      padding: 6px;
      border-radius: 14px;
    }
    .qtyBtn{
      width: 34px; height: 34px;
      border-radius: 10px;
      border: 1px solid rgba(255,255,255,0.12);
      background: rgba(255,255,255,0.08);
      color:#fff;
      font-weight: 900;
      cursor:pointer;
      transition:.2s ease;
    }
    .qtyBtn:hover{transform: translateY(-1px);}
    .qtyInput{
      width: 60px;
      padding: 10px 8px;
      border-radius: 12px;
      border:1px solid rgba(255,255,255,0.10);
      background: rgba(255,255,255,0.07);
      color:#fff;
      outline:none;
      font-weight: 900;
      text-align:center;
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
    .btn.wide{width:100%; padding: 14px 14px; font-size: 15px; font-weight: 900;}

    .summary{ padding: 16px; }
    .sumRow{
      display:flex; justify-content:space-between; align-items:center;
      padding: 10px 0;
      border-bottom: 1px dashed rgba(255,255,255,0.14);
      font-family: Arial, Helvetica, sans-serif;
    }
    .sumRow:last-child{border-bottom:none}
    .sumTotal{ font-size: 20px; font-weight: 900; color:#ffd9d9; font-family: Arial, Helvetica, sans-serif; }

    .note{
      margin-top: 12px;
      opacity:.9; font-size: 12px; line-height: 1.5;
      font-family: Arial, Helvetica, sans-serif;
      color: rgba(255,255,255,0.85);
      background: rgba(255,255,255,0.05);
      border:1px solid rgba(255,255,255,0.08);
      padding: 12px;
      border-radius: 14px;
    }
    .empty{
      padding: 22px 16px;
      text-align:center;
      font-family: Arial, Helvetica, sans-serif;
      opacity: .9;
    }

    .ordersBox{ margin-top: 18px; }
    .orderRow{
      padding: 14px 16px;
      border-bottom: 1px solid rgba(255,255,255,0.06);
      font-family: Arial, Helvetica, sans-serif;
    }
    .orderTop{
      display:flex; justify-content:space-between; align-items:center;
      gap: 10px;
      margin-bottom: 10px;
    }
    .orderId{ font-weight: 900; }
    .statusPill{
      padding: 6px 10px;
      border-radius: 999px;
      border:1px solid rgba(255,255,255,0.12);
      background: rgba(255,255,255,0.06);
      text-transform: uppercase;
      font-size: 12px;
      font-weight: 900;
    }
    .statusPill.pending{border-color: rgba(255,200,0,.35); background: rgba(255,200,0,.10);}
    .statusPill.preparing{border-color: rgba(0,170,255,.35); background: rgba(0,170,255,.10);}
    .statusPill.ready{border-color: rgba(0,255,128,.35); background: rgba(0,255,128,.10);}
    .statusPill.complete{border-color: rgba(255,255,255,.20); background: rgba(255,255,255,.08);}

    .orderItems{
      display:flex; flex-wrap: wrap; gap: 10px;
      opacity: .95;
    }
    .oi{
      display:flex; align-items:center; gap:8px;
      padding: 8px 10px;
      border-radius: 14px;
      border:1px solid rgba(255,255,255,0.10);
      background: rgba(0,0,0,0.35);
    }
    .oi img{
      width: 34px; height: 34px;
      border-radius: 10px;
      object-fit: cover;
      border:1px solid rgba(255,255,255,0.10);
    }
    .oi b{font-size: 13px;}
    .oi span{font-size: 12px; opacity:.9;}
  </style>
<link rel="stylesheet" href="assets/four-guys.css?v=1">
<script src="assets/four-guys.js?v=1" defer></script>
</head>

<body class="fg fg-public fg-page-cart" data-page="cart">
  <?php $fgBase = ""; include __DIR__ . "/Includes/header.php"; ?>

  <div class="wrap">
    <div class="title">
      <h1>Your Cart</h1>
      <p>Cart status: <b style="color:#fff;">pending</b> (Confirm to send to kitchen)</p>

      <?php if ($ok): ?>
        <div class="alert ok">✅ <?= h($ok) ?></div>
      <?php endif; ?>

      <?php if ($err): ?>
        <div class="alert err">❌ <?= h($err) ?></div>
      <?php endif; ?>
    </div>

    <div class="grid">
      <!-- LEFT: ITEMS -->
      <div class="card">
        <div class="itemsHeader">
          <b>Items</b>
          <span><?= count($items) ?> item(s)</span>
        </div>

        <?php if ($cart_id <= 0 || count($items) === 0): ?>
          <div class="empty">
            Your cart is empty.
            <br><br>
            <a class="btn" href="MenuD.php">Go to Menu</a>
          </div>
        <?php else: ?>

          <?php foreach ($items as $it): ?>
            <div class="item">
              <div class="thumb">
                <img src="<?= h($it['image_path']) ?>" alt="">
              </div>

              <div class="info">
                <div class="name"><?= h($it['name']) ?></div>
                <div class="desc"><?= h($it['description']) ?></div>

                <div class="meta">
                  <span class="badge">Unit: <?= number_format((float)$it['unit_price'], 2) ?> SAR</span>
                  <span class="badge">Line: <?= number_format((float)$it['line_total'], 2) ?> SAR</span>
                  <?php if ((int)$it['is_active'] !== 1): ?>
                    <span class="badge" style="border-color:rgba(255,0,0,.35); background:rgba(255,0,0,.10);">Not available</span>
                  <?php endif; ?>
                </div>
              </div>

              <div class="qtyBox">
                <!-- UPDATE FORM (No Ajax) -->
                <form class="qtyForm" action="Backend/cart_update.php" method="POST" style="display:flex; gap:10px; align-items:center;">
                  <input type="hidden" name="item_id" value="<?= (int)$it['item_id'] ?>">
                  <div class="qtyWrap">
                    <button class="qtyBtn" type="button" data-action="dec">-</button>
                    <input class="qtyInput" type="number" name="qty" min="1" value="<?= (int)$it['qty'] ?>" />
                    <button class="qtyBtn" type="button" data-action="inc">+</button>
                  </div>
                </form>

                <!-- REMOVE FORM -->
                <form action="Backend/cart_remove.php" method="POST">
                  <input type="hidden" name="item_id" value="<?= (int)$it['item_id'] ?>">
                  <button class="btn small" type="submit">Remove</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>

        <?php endif; ?>
      </div>

      <!-- RIGHT: SUMMARY -->
      <div class="card">
        <div class="itemsHeader">
          <b>Summary</b>
          <span>No delivery</span>
        </div>

        <div class="summary">
          <div class="sumRow">
            <span>Subtotal</span>
            <b><?= number_format((float)$subtotal, 2) ?> SAR</b>
          </div>
          <div class="sumRow">
            <span>Delivery</span>
            <b>0.00 SAR</b>
          </div>
          <div class="sumRow">
            <span>Total</span>
            <span class="sumTotal"><?= number_format((float)$subtotal, 2) ?> SAR</span>
          </div>

          <a class="btn wide" href="MenuD.php">Continue Shopping</a>

          <form action="Backend/order_confirm.php" method="POST" style="margin-top:12px;">
            <button class="btn wide" type="submit" <?= ($cart_id<=0 || count($items)===0) ? 'disabled style="opacity:.45;cursor:not-allowed;"' : '' ?>>
              Confirm Order
            </button>
          </form>

          <div class="note">Confirm your order to send it to our kitchen. You can follow its progress below.</div>
        </div>
      </div>
    </div>

    <!-- MY ORDERS -->
    <div class="ordersBox card">
      <div class="itemsHeader">
        <b>My Orders</b>
        <span><?= count($orders) ?> order(s)</span>
      </div>

      <?php if (count($orders) === 0): ?>
        <div class="empty">No orders yet.</div>
      <?php else: ?>

        <?php foreach ($orders as $o): ?>
          <?php
            $oid = (int)$o['id'];
            $st  = strtolower((string)$o['status']);
            if ($st === 'completed') $st = 'complete'; // احتياط لو كانت عندك completed
            $pillClass = in_array($st, ['pending','preparing','ready','complete']) ? $st : 'pending';

            $list = $orderItemsMap[$oid] ?? [];
          ?>
          <div class="orderRow">
            <div class="orderTop">
              <div>
                <div class="orderId">Order #<?= $oid ?></div>
                <div style="opacity:.85; font-size:12px; margin-top:4px;">
                  Prep ETA: <?= (int)$o['expected_prep_minutes'] ?> min • <?= h($o['created_at']) ?>
                </div>
              </div>
             <div style="display:flex; gap:10px; align-items:center;">
  <div class="statusPill <?= h($pillClass) ?>"><?= h($st) ?></div>

  <?php if ($st === 'pending'): ?>
  <form action="Backend/order_cancel_client.php" method="POST" style="margin-top:10px;">
    <input type="hidden" name="order_id" value="<?= (int)$oid ?>">
    <button class="btn small" type="submit">Cancel</button>
  </form>
<?php endif; ?>

<?php if ($st === 'complete'): ?>
  <a class="btn small" style="margin-top:10px;"
     href="rate_order.php?order_id=<?= (int)$oid ?>">
     ⭐ Rate
  </a>
<?php endif; ?>
</div>
            </div>

            <div class="orderItems">
              <?php foreach ($list as $it): ?>
                <div class="oi">
                  <img src="<?= h($it['image_path']) ?>" alt="">
                  <div>
                    <b><?= h($it['name']) ?></b><br>
                    <span>x<?= (int)$it['qty'] ?> • <?= number_format((float)$it['unit_price'],2) ?> SAR</span>
                  </div>
                </div>
              <?php endforeach; ?>

              <?php if (count($list) === 0): ?>
                <span style="opacity:.8;">(No items)</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>

      <?php endif; ?>
    </div>

  </div>

  <script>
    // JS بسيط: + / - وتحديث الكمية تلقائياً (بدون Ajax)
    (function(){
      let timers = new WeakMap();

      function clampQty(v){
        v = parseInt(v || "1", 10);
        if (isNaN(v) || v < 1) v = 1;
        return v;
      }

      document.querySelectorAll(".qtyForm").forEach(function(form){
        const input = form.querySelector(".qtyInput");
        const btns  = form.querySelectorAll(".qtyBtn");

        function scheduleSubmit(){
          if (timers.has(form)) clearTimeout(timers.get(form));
          timers.set(form, setTimeout(function(){
            form.submit();
          }, 350));
        }

        btns.forEach(function(btn){
          btn.addEventListener("click", function(){
            const act = btn.getAttribute("data-action");
            let v = clampQty(input.value);
            if (act === "inc") v++;
            if (act === "dec") v = Math.max(1, v-1);
            input.value = v;
            scheduleSubmit();
          });
        });

        input.addEventListener("change", function(){
          input.value = clampQty(input.value);
          scheduleSubmit();
        });
      });
    })();
  </script>
<?php $fgBase = ""; include __DIR__ . "/Includes/footer.php"; ?>
</body>
</html>