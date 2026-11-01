<?php
session_start();
require_once "Backend/db.php";

/* حماية الصفحة */
if (!isset($_SESSION['staff_id']) || !in_array($_SESSION['role'], ['manager','admin'])) {
  header("Location: StLogin.php?error=1");
  exit;
}

$role = $_SESSION['role'];

/* ===== عدد الطلبات (بدون cancelled) ===== */
$total_orders = 0;
$q1 = mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE status != 'cancelled'");
if ($q1) {
  $r1 = mysqli_fetch_assoc($q1);
  $total_orders = (int)$r1['c'];
}

/* ===== الإيرادات (completed فقط) ===== */
$revenue = 0;
$q2 = mysqli_query($conn, "
  SELECT COALESCE(SUM(oi.qty * oi.unit_price),0) AS rev
  FROM orders o
  JOIN order_items oi ON oi.order_id = o.id
  WHERE o.status = 'completed'
");
if ($q2) {
  $r2 = mysqli_fetch_assoc($q2);
  $revenue = (float)$r2['rev'];
}

/* ===== الطلبات المكتملة اليوم ===== */
$completed_today = 0;
$q3 = mysqli_query($conn, "
  SELECT COUNT(*) AS c
  FROM orders
  WHERE status = 'completed'
    AND DATE(created_at) = CURDATE()
");
if ($q3) {
  $r3 = mysqli_fetch_assoc($q3);
  $completed_today = (int)($r3['c'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manager Dashboard</title>
<style>
body{font-family:Arial;background:#fafafa;text-align:center;margin:0}
h1{margin-top:30px}
.topbar{width:80%;margin:15px auto;display:flex;justify-content:space-between;align-items:center}
.logout{background:#d32f2f;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;font-weight:bold}
.logout:hover{background:#b71c1c}
.container{width:80%;margin:30px auto}
.row{display:flex;justify-content:center;flex-wrap:wrap}
.card{background:#fff;border:1px solid #ddd;border-radius:10px;padding:20px;margin:10px;width:240px}
.number{font-size:28px;font-weight:bold}
.button{
  background:#f1f1f1;border:none;padding:10px 20px;
  border-radius:6px;margin:5px;cursor:pointer;
  text-decoration:none;color:#000;display:inline-block
}
.button:hover{background:#ddd}
.button.blue{background:#1976d2;color:#fff}
.button.blue:hover{background:#0d47a1}
</style>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/four-guys.css?v=1">
<script src="assets/four-guys.js?v=1" defer></script>
</head>

<body class="fg fg-staff fg-page-manager" data-page="manager">
<?php $fgBase = ""; include __DIR__ . "/Includes/sidebar.php"; ?>
<main class="fg-workspace" id="main-content">

<div class="topbar">
  <div>Logged in as: <b><?= htmlspecialchars($role) ?></b></div>
  <a class="logout" href="Backend/logout.php">Logout</a>
</div>

<h1>Manager Dashboard</h1>

<div class="container">

  <!-- الإحصائيات -->
  <div class="row">
    <div class="card">
      <p>Total Orders</p>
      <p class="number"><?= $total_orders ?></p>
    </div>

    <div class="card">
      <p>Completed Today</p>
      <p class="number"><?= $completed_today ?></p>
    </div>

    <div class="card">
      <p>Revenue</p>
      <p class="number">$<?= number_format($revenue,2) ?></p>
    </div>
  </div>

  <!-- الطلبات -->
  <div class="row">
    <a class="button" href="Backend/orders_list.php">Order History</a>
    <a class="button" href="Backend/orders_list.php?status=pending">Pending Orders</a>
  </div>

  <!-- الإدارة -->
  <div class="row">

    <div class="card">
      <p><b>Menu Management</b></p>
      <a class="button blue" href="Backend/manage_menu.php">Manage Menu</a>
    </div>

    <div class="card">
      <p><b>Employee Management</b></p>
      <a class="button blue" href="Backend/manage_staff.php">Manage Staff</a>
    </div>

    <!-- ✅ زر رسائل Contact -->
    <div class="card">
      <p><b>Contact Messages</b></p>
      <a class="button blue" href="Backend/contact_messages.php">
        View Messages
      </a>
    </div>

  </div>

</div>
</main>
</body>
</html>