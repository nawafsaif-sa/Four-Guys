<?php
session_start();
require_once "Backend/db.php";

/* ===== حماية الصفحة: لازم waiter ===== */
if (!isset($_SESSION['staff_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'waiter') {
  header("Location: StLogin.php?error=1");
  exit;
}

$staff_id = (int)$_SESSION['staff_id'];

/* منتجات متاحة */
$products = mysqli_query($conn, "
  SELECT id, name, price, prep_minutes
  FROM products
  WHERE is_active = 1
  ORDER BY name ASC
");

/* طلبات الويتر (بدون cancelled / completed) */
$orders = mysqli_query($conn, "
  SELECT id, customer_name, table_number, status, expected_prep_minutes, created_at
  FROM orders
  WHERE user_id = $staff_id
    AND status IN ('pending','preparing','ready')
  ORDER BY created_at DESC
  LIMIT 20
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Waiter Dashboard</title>
  <style>
    *{box-sizing:border-box}
    body{
      font-family:Arial;
      background:#fafafa;
      margin:0;
      color:#111;
    }

    .wrap{
      max-width:1200px;
      margin:0 auto;
      padding:18px;
    }

    .topbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
      margin-bottom:14px;
    }
    .logout{
      text-decoration:none;
      background:#d32f2f;
      color:#fff;
      padding:10px 16px;
      border-radius:10px;
      font-weight:bold
    }
    .logout:hover{background:#b71c1c}

    h1{
      margin:8px 0 18px;
      font-size:30px;
      text-align:center;
    }

    .msg{
      background:#fff;
      border:1px solid #ddd;
      border-radius:12px;
      padding:10px 12px;
      margin:0 0 16px;
      font-weight:800;
    }

    /* ✅ GRID بدل flex */
    .grid{
      display:grid;
      grid-template-columns: 1fr 1.15fr;
      gap:16px;
      align-items:start;
    }

    .card{
      background:#fff;
      border:1px solid #e6e6e6;
      border-radius:14px;
      padding:16px;
      box-shadow:0 2px 10px rgba(0,0,0,.05);
    }

    .section-title{
      font-weight:900;
      font-size:18px;
      margin:0 0 10px;
    }

    label{font-weight:800;display:block;margin-top:10px}
    input,select{
      width:100%;
      padding:10px;
      margin-top:6px;
      border-radius:10px;
      border:1px solid #ccc;
      outline:none;
    }
    input:focus,select:focus{border-color:#1976d2}

    table{
      width:100%;
      border-collapse:collapse;
      margin-top:10px;
      table-layout:fixed;
    }
    th,td{
      border:1px solid #e6e6e6;
      padding:9px;
      text-align:center;
      font-size:14px;
      word-wrap:break-word;
    }
    th{
      background:#f5f5f5;
      font-weight:900;
    }

    /* ✅ اسم المنتج لا يكسر الجدول */
    .menu-table th:nth-child(1),
    .menu-table td:nth-child(1){
      text-align:left;
      padding-left:12px;
      overflow:hidden;
      text-overflow:ellipsis;
      white-space:nowrap;
    }

    .btn{
      border:none;
      padding:10px 14px;
      border-radius:10px;
      cursor:pointer;
      font-weight:900;
    }
    .btn-green{background:#2e7d32;color:#fff}
    .btn-green:hover{background:#1b5e20}
    .btn-red{background:#d32f2f;color:#fff}
    .btn-red:hover{background:#b71c1c}

    .muted{color:#666;font-size:14px;line-height:1.6}
    .small{font-size:13px;color:#777}
    .right{text-align:right}

    /* ✅ صندوق تفاصيل العناصر */
    .items-wrap{
      margin:10px 0 0;
      border:1px solid #eee;
      border-radius:12px;
      background:#fbfbfb;
      padding:10px;
    }
    .items-table th{background:#efefef}
    .items-table td:nth-child(1){
      text-align:left;
      padding-left:12px;
      overflow:hidden;
      text-overflow:ellipsis;
      white-space:nowrap;
    }

    /* ✅ زر الكنسل يكون وسط */
    .action-cell{display:flex;justify-content:center}

    /* ✅ Responsive */
    @media (max-width: 980px){
      .grid{grid-template-columns:1fr}
      h1{text-align:left}
    }
  </style>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/four-guys.css?v=1">
<script src="assets/four-guys.js?v=1" defer></script>
</head>

<body class="fg fg-staff fg-page-waiter" data-page="waiter">
<?php $fgBase = ""; include __DIR__ . "/Includes/sidebar.php"; ?>
<main class="fg-workspace" id="main-content">
  <div class="wrap">

    <div class="topbar">
      <div>Logged in as: <b><?= htmlspecialchars($_SESSION['role']) ?></b></div>
      <a class="logout" href="Backend/logout.php">Logout</a>
    </div>

    <h1>Waiter Dashboard</h1>

    <?php if (isset($_GET['msg'])): ?>
      <div class="msg">
        <?php
          if ($_GET['msg'] === 'created') echo "✅ Order created successfully";
          elseif ($_GET['msg'] === 'empty') echo "❌ Please select at least one item (qty > 0)";
          elseif ($_GET['msg'] === 'table_required') echo "❌ Table number is required";
          elseif ($_GET['msg'] === 'cancelled') echo "✅ Order cancelled";
          elseif ($_GET['msg'] === 'notallowed') echo "❌ You can cancel only pending orders";
          elseif ($_GET['msg'] === 'notfound') echo "❌ Order not found";
          else echo "❌ Something went wrong";
        ?>
      </div>
    <?php endif; ?>

    <div class="grid">

      <!-- Take New Order -->
      <div class="card">
        <div class="section-title">Take New Order</div>

        <form action="Backend/create_order.php" method="POST">
          <label>Customer Name:</label>
          <input type="text" name="customer_name" placeholder="Customer Name" />

          <label>Table Number:</label>
          <input type="number" name="table_number" placeholder="Table Number" min="1" required />

          <table class="menu-table">
            <tr>
              <th>Item</th>
              <th style="width:110px;">Price</th>
              <th style="width:110px;">Prep</th>
              <th style="width:110px;">Qty</th>
            </tr>

            <?php if ($products && mysqli_num_rows($products) > 0): ?>
              <?php while ($p = mysqli_fetch_assoc($products)): ?>
                <tr>
                  <td title="<?= htmlspecialchars($p['name']) ?>"><?= htmlspecialchars($p['name']) ?></td>
                  <td>$<?= number_format((float)$p['price'], 2) ?></td>
                  <td><?= (int)$p['prep_minutes'] ?> min</td>
                  <td>
                    <input type="number" name="qty[<?= (int)$p['id'] ?>]" min="0" value="0">
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="4" class="muted">No products available.</td>
              </tr>
            <?php endif; ?>
          </table>

          <div style="margin-top:12px;text-align:right;">
            <button class="btn btn-green" type="submit">Submit Order</button>
          </div>
        </form>
      </div>

      <!-- My Recent Orders -->
      <div class="card">
        <div class="section-title">My Recent Orders</div>

        <?php if (!$orders || mysqli_num_rows($orders) == 0): ?>
          <div class="muted">No orders yet.</div>
        <?php else: ?>

          <?php while ($o = mysqli_fetch_assoc($orders)): ?>
            <?php
              $orderId = (int)$o['id'];
              $itemsRes = mysqli_query($conn, "
                SELECT p.name, oi.unit_price, oi.qty
                FROM order_items oi
                JOIN products p ON p.id = oi.product_id
                WHERE oi.order_id = $orderId
              ");
            ?>

            <table>
              <tr>
                <th style="width:90px;">Order #</th>
                <th>Customer</th>
                <th style="width:90px;">Table</th>
                <th style="width:110px;">Status</th>
                <th style="width:130px;">Prep</th>
                <th style="width:110px;">Action</th>
              </tr>

              <tr>
                <td>#<?= $orderId ?></td>
                <td><?= htmlspecialchars($o['customer_name'] ?: '—') ?></td>
                <td><?= $o['table_number'] ? (int)$o['table_number'] : '—' ?></td>
                <td><?= htmlspecialchars($o['status']) ?></td>
                <td><?= (int)$o['expected_prep_minutes'] ?> min</td>
                <td class="action-cell">
                  <?php if ($o['status'] === 'pending'): ?>
                    <form action="Backend/order_cancel.php" method="POST" style="margin:0;">
                      <input type="hidden" name="order_id" value="<?= $orderId ?>">
                      <button class="btn btn-red" type="submit">Cancel</button>
                    </form>
                  <?php else: ?>
                    <span class="small">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            </table>

            <div class="items-wrap">
              <table class="items-table">
                <tr>
                  <th>Product</th>
                  <th style="width:110px;">Price</th>
                  <th style="width:90px;">Qty</th>
                  <th style="width:120px;">Total</th>
                </tr>

                <?php
                  $orderTotal = 0;
                  if ($itemsRes && mysqli_num_rows($itemsRes) > 0):
                    while ($it = mysqli_fetch_assoc($itemsRes)):
                      $price = (float)$it['unit_price'];
                      $q = (int)$it['qty'];
                      $lineTotal = $price * $q;
                      $orderTotal += $lineTotal;
                ?>
                    <tr>
                      <td title="<?= htmlspecialchars($it['name']) ?>"><?= htmlspecialchars($it['name']) ?></td>
                      <td>$<?= number_format($price, 2) ?></td>
                      <td><?= $q ?></td>
                      <td>$<?= number_format($lineTotal, 2) ?></td>
                    </tr>
                <?php
                    endwhile;
                  else:
                ?>
                    <tr>
                      <td colspan="4" class="muted">No items found for this order.</td>
                    </tr>
                <?php endif; ?>

                <tr>
                  <td colspan="3" class="right" style="font-weight:900;">Order Total</td>
                  <td style="font-weight:900;">$<?= number_format($orderTotal, 2) ?></td>
                </tr>
              </table>
            </div>

            <div style="height:14px;"></div>
          <?php endwhile; ?>

        <?php endif; ?>
      </div>

    </div>
  </div>
</main>
</body>
</html>
