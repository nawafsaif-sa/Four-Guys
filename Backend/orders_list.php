<?php
session_start();
require_once "db.php";

/* حماية: manager أو admin فقط */
if (!isset($_SESSION['staff_id']) || !in_array($_SESSION['role'], ['manager','admin'])) {
  header("Location: ../StLogin.php?error=1");
  exit;
}

$statusFilter = $_GET['status'] ?? null;

/* الاستعلام */
$sql = "
  SELECT o.id, o.status, o.expected_prep_minutes, o.created_at,
         COALESCE(SUM(oi.qty * oi.unit_price),0) AS total
  FROM orders o
  LEFT JOIN order_items oi ON oi.order_id = o.id
";

if ($statusFilter) {
  $sql .= " WHERE o.status = '" . mysqli_real_escape_string($conn, $statusFilter) . "'";
}

$sql .= "
  GROUP BY o.id
  ORDER BY o.created_at DESC
";

$orders = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Order History</title>
  <style>
    body{font-family:Arial;background:#fafafa}
    h2{text-align:center}
    table{width:90%;margin:20px auto;border-collapse:collapse;background:#fff}
    th,td{border:1px solid #ddd;padding:10px;text-align:center}
    th{background:#f1f1f1}
    .back{margin:20px;text-align:center}
    a{padding:10px 16px;background:#1976d2;color:#fff;border-radius:6px;text-decoration:none}
    a:hover{background:#0d47a1}
  </style>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="../assets/four-guys.css?v=1">
<script src="../assets/four-guys.js?v=1" defer></script>
</head>
<body class="fg fg-staff fg-page-orders_list" data-page="orders_list">
<?php $fgBase = "../"; include __DIR__ . "/../Includes/sidebar.php"; ?>
<main class="fg-workspace" id="main-content">

<h2>Order History</h2>

<table>
  <tr>
    <th>Order #</th>
    <th>Status</th>
    <th>Prep</th>
    <th>Total</th>
    <th>Date</th>
  </tr>

  <?php while ($o = mysqli_fetch_assoc($orders)): ?>
    <tr>
      <td>#<?= (int)$o['id'] ?></td>
      <td><?= htmlspecialchars($o['status']) ?></td>
      <td><?= (int)$o['expected_prep_minutes'] ?> min</td>
      <td>$<?= number_format((float)$o['total'],2) ?></td>
      <td><?= $o['created_at'] ?></td>
    </tr>
  <?php endwhile; ?>
</table>

<div class="back">
  <a href="../manager.php">Back to Dashboard</a>
</div>

</main>
</body>
</html>
