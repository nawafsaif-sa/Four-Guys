<?php
session_start();
require_once "Backend/db.php";

/* حماية: chef فقط */
if (!isset($_SESSION['staff_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'chef') {
  header("Location: StLogin.php?error=1");
  exit;
}

/* جلب الطلبات للشيف: pending + preparing + ready (ويختفي completed) */
$orders = mysqli_query($conn, "
  SELECT id, status, expected_prep_minutes, created_at
  FROM orders
  WHERE status IN ('pending','preparing','ready')
  ORDER BY created_at ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Chef Dashboard</title>
  <style>
    body{font-family:Arial;background:#fafafa;margin:0;padding:0}
    .topbar{width:88%;margin:18px auto 0;display:flex;justify-content:space-between;align-items:center}
    .logout{text-decoration:none;background:#d32f2f;color:#fff;padding:10px 16px;border-radius:6px;font-weight:bold}
    .logout:hover{background:#b71c1c}
    h1{width:88%;margin:18px auto 10px;font-size:30px}
    .msg{width:88%;margin:10px auto;background:#fff;border:1px solid #ddd;border-radius:10px;padding:10px 12px}
    .ok{border-color:#b9f6ca;background:#e9fff0;color:#1b5e20;font-weight:bold}
    .err{border-color:#ffbdbd;background:#ffe8e8;color:#b71c1c;font-weight:bold}
    .wrap{width:88%;margin:18px auto 30px}
    .card{background:#fff;border:1px solid #ddd;border-radius:12px;padding:18px 18px 14px;margin-bottom:16px;box-shadow:0 0 6px rgba(0,0,0,.05)}
    .title{font-size:34px;font-weight:900;margin:0 0 10px}
    .meta{font-size:18px;margin-bottom:12px}
    .meta b{font-weight:900}
    .actions{display:flex;gap:12px;margin:10px 0 14px}
    .btn{border:none;padding:10px 16px;border-radius:8px;cursor:pointer;font-weight:900;font-size:16px}
    .btn-orange{background:#ff9800;color:#fff}
    .btn-orange:hover{background:#e38900}
    .btn-green{background:#81c784;color:#fff}
    .btn-green:hover{background:#66bb6a}
    .btn:disabled{opacity:.45;cursor:not-allowed}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{border:1px solid #ddd;padding:10px;text-align:center}
    th{background:#eee;font-size:18px}
    .ready-badge{display:inline-block;background:#e8f5e9;border:1px solid #c8e6c9;color:#1b5e20;
      font-weight:900;border-radius:999px;padding:4px 10px;margin-left:10px;font-size:14px}
  </style>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/four-guys.css?v=1">
<script src="assets/four-guys.js?v=1" defer></script>
</head>
<body class="fg fg-staff fg-page-chef" data-page="chef">
<?php $fgBase = ""; include __DIR__ . "/Includes/sidebar.php"; ?>
<main class="fg-workspace" id="main-content">

<div class="topbar">
  <div>Logged in as: <b><?= htmlspecialchars($_SESSION['role']) ?></b></div>
  <a class="logout" href="Backend/logout.php">Logout</a>
</div>

<h1>Chef Dashboard</h1>

<?php if (isset($_GET['msg'])): ?>
  <?php if ($_GET['msg'] === 'ok'): ?>
    <div class="msg ok">✅ Updated</div>
  <?php elseif ($_GET['msg'] === 'notallowed'): ?>
    <div class="msg err">❌ Not allowed for this status</div>
  <?php elseif ($_GET['msg'] === 'notfound'): ?>
    <div class="msg err">❌ Order not found</div>
  <?php else: ?>
    <div class="msg err">❌ Error</div>
  <?php endif; ?>
<?php endif; ?>

<div class="wrap">

  <?php if (!$orders || mysqli_num_rows($orders) === 0): ?>
    <div class="msg">No orders.</div>
  <?php else: ?>

    <?php while ($o = mysqli_fetch_assoc($orders)): ?>
      <?php
        $orderId = (int)$o['id'];
        $status  = $o['status'];

        $itemsRes = mysqli_query($conn, "
          SELECT p.name, oi.qty
          FROM order_items oi
          JOIN products p ON p.id = oi.product_id
          WHERE oi.order_id = $orderId
        ");
      ?>

      <div class="card">
        <div class="title">
          Order #<?= $orderId ?>
          <?php if ($status === 'ready'): ?>
            <span class="ready-badge">READY</span>
          <?php endif; ?>
        </div>

        <div class="meta">
          Status: <b><?= htmlspecialchars(ucfirst($status)) ?></b>
          | Expected Prep: <b><?= (int)$o['expected_prep_minutes'] ?> min</b>
        </div>

        <div class="actions">
          <!-- Start Cooking: pending -> preparing -->
          <form action="Backend/chef_update_status.php" method="POST" style="margin:0;">
            <input type="hidden" name="order_id" value="<?= $orderId ?>">
            <input type="hidden" name="action" value="start">
            <button class="btn btn-orange" type="submit" <?= ($status !== 'pending') ? 'disabled' : '' ?>>
              Start Cooking
            </button>
          </form>

          <!-- Mark Ready: preparing -> ready -->
          <form action="Backend/chef_update_status.php" method="POST" style="margin:0;">
            <input type="hidden" name="order_id" value="<?= $orderId ?>">
            <input type="hidden" name="action" value="ready">
            <button class="btn btn-green" type="submit" <?= ($status !== 'preparing') ? 'disabled' : '' ?>>
              Mark Ready
            </button>
          </form>
        </div>

        <table>
          <tr>
            <th>Item</th>
            <th>Quantity</th>
          </tr>

          <?php if ($itemsRes && mysqli_num_rows($itemsRes) > 0): ?>
            <?php while ($it = mysqli_fetch_assoc($itemsRes)): ?>
              <tr>
                <td><?= htmlspecialchars($it['name']) ?></td>
                <td><?= (int)$it['qty'] ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="2">No items</td></tr>
          <?php endif; ?>
        </table>
      </div>

    <?php endwhile; ?>

  <?php endif; ?>

</div>
</main>
</body>
</html>
