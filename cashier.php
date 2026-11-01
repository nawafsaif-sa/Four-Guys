<?php


session_start();

require_once "Backend/db.php";

/* حماية الصفحة: لازم يكون cashier */
if (!isset($_SESSION['staff_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'cashier') {
  header("Location: StLogin.php?error=1");
  exit;
}

$cashier_id = (int)$_SESSION['staff_id'];

/* جلب المنتجات */
$products = [];
$res = mysqli_query($conn, "
  SELECT id, name, price, image_path, prep_minutes
  FROM products
  WHERE is_active = 1
  ORDER BY name ASC
");
if ($res) {
  while ($row = mysqli_fetch_assoc($res)) $products[] = $row;
}

/* جلب أحدث الطلبات + TOTAL */
$orders = mysqli_query($conn, "
  SELECT 
    o.id,
    o.user_id,
    o.customer_name,
    o.table_number,
    o.status,
    o.expected_prep_minutes,
    o.created_at,
    IFNULL(SUM(oi.qty * p.price), 0) AS total_amount
  FROM orders o
  LEFT JOIN order_items oi ON oi.order_id = o.id
  LEFT JOIN products p ON p.id = oi.product_id
  GROUP BY o.id
  ORDER BY o.created_at DESC
  LIMIT 20
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Four Guys | Cashier POS</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
*{box-sizing:border-box}
body{margin:0;font-family:Arial;background:#f6f7fb;color:#111}
.wrap{max-width:1280px;margin:0 auto;padding:16px}

.topbar{display:flex;justify-content:flex-end;padding:10px 0}
.logout-btn{
  padding:10px 16px;border-radius:12px;
  background:#d32f2f;color:#fff;
  text-decoration:none;font-weight:900
}

.pos-grid{
  display:grid;
  grid-template-columns:1.25fr .75fr;
  gap:16px;
}

.card{
  background:#fff;border-radius:16px;
  padding:14px;border:1px solid #ddd;
  box-shadow:0 2px 10px rgba(0,0,0,.06);
}

.order-title{margin:0;font-size:18px;font-weight:900}

.menu-items{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(190px,1fr));
  gap:14px;
}

.item{
  border:1px solid #eee;
  border-radius:14px;
  padding:12px;
  min-height:260px;
  display:flex;
  flex-direction:column;
}

.item img{
  width:100%;
  height:140px;
  object-fit:contain;
  background:#fafafa;
  border-radius:12px;
}

.name{font-weight:900;margin:6px 0}
.price{color:#b71c1c;font-weight:900}
.prep{font-size:12px;color:#666}

.qty-box{
  display:flex;justify-content:space-between;
  align-items:center;margin-top:10px
}

.qty-box input{
  width:80px;padding:8px;border-radius:10px;
  border:1px solid #ccc
}

.create-bar{text-align:right;margin-top:12px}
.create-bar button{
  padding:12px 18px;border:none;
  border-radius:14px;
  background:#b71c1c;color:#fff;
  font-weight:900;cursor:pointer
}

.orders-box table{
  width:100%;
  border-collapse:collapse;
  margin-top:10px;
}

.orders-box th,.orders-box td{
  border:1px solid #eee;
  padding:8px;
  text-align:center;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}

.orders-box th{background:#fafafa;font-weight:900}

.total{
  font-weight:900;
  color:#b71c1c;
}
</style>
<link rel="stylesheet" href="assets/four-guys.css?v=1">
<script src="assets/four-guys.js?v=1" defer></script>
</head>

<body class="fg fg-staff fg-page-cashier" data-page="cashier">
<?php $fgBase = ""; include __DIR__ . "/Includes/sidebar.php"; ?>
<main class="fg-workspace" id="main-content">
<div class="wrap">

<div class="topbar">
  <a class="logout-btn" href="Backend/logout.php">Logout</a>
</div>

<div class="fg-work-heading"><span class="fg-eyebrow">FOUR GUYS OPERATIONS</span><h1>Cashier workspace</h1><p>Create an order and keep service moving.</p></div><div class="pos-grid">

<!-- LEFT -->
<section class="card">
<h2 class="order-title">Full Menu</h2>

<form action="Backend/create_order.php" method="POST">

<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:10px 0">
  <input type="text" name="customer_name" placeholder="Customer Name (optional)">
  <input type="number" name="table_number" required placeholder="Table Number">
</div>

<div class="menu-items">
<?php foreach ($products as $p): ?>
  <div class="item">
    <img src="<?= htmlspecialchars($p['image_path'] ?: 'Burger.png') ?>">
    <div class="name"><?= htmlspecialchars($p['name']) ?></div>
    <div class="price">$<?= number_format($p['price'],2) ?></div>
    <div class="prep"><?= (int)$p['prep_minutes'] ?> min</div>

    <div class="qty-box">
      <strong>Qty</strong>
      <input type="number" name="qty[<?= (int)$p['id'] ?>]" min="0" value="0">
    </div>
  </div>
<?php endforeach; ?>
</div>

<div class="create-bar">
  <button type="submit">Create Order</button>
</div>
</form>
</section>

<!-- RIGHT -->
<section class="card">
<h2 class="order-title">Recent Orders</h2>

<div class="orders-box">
<table>
<tr>
  <th style="width:70px">Order</th>
  <th>Customer</th>
  <th style="width:70px">Table</th>
  <th style="width:90px">Status</th>
  <th style="width:70px">Prep</th>
  <th style="width:100px">Total</th>
  <th style="width:170px">Actions</th>
</tr>

<?php if ($orders && mysqli_num_rows($orders)>0): ?>
<?php while($o=mysqli_fetch_assoc($orders)): ?>
<tr>
  <td>#<?= (int)$o['id'] ?></td>
  <td><?= htmlspecialchars($o['customer_name'] ?: '—') ?></td>
  <td><?= $o['table_number'] ?: '—' ?></td>
  <td><?= htmlspecialchars($o['status']) ?></td>
  <td><?= (int)$o['expected_prep_minutes'] ?>m</td>
  <td class="total">$<?= number_format((float)$o['total_amount'],2) ?></td>
  <td>
    <?php if ($o['status']==='pending'): ?>
      <form action="Backend/order_cancel.php" method="POST">
        <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
        <button style="background:#d32f2f;color:#fff;border:none;border-radius:10px;padding:6px 10px">Cancel</button>
      </form>
    <?php elseif ($o['status']==='ready'): ?>
      <form action="Backend/cashier_complete_order.php" method="POST">
        <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
        <button style="background:#2e7d32;color:#fff;border:none;border-radius:10px;padding:6px 10px">Complete</button>
      </form>
    <?php else: ?>
      —
    <?php endif; ?>
  </td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="7">No orders</td></tr>
<?php endif; ?>

</table>
</div>
</section>

</div>
</div>
</main>
</body>
</html>