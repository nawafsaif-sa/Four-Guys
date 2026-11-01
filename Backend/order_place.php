<?php
session_start();
require_once __DIR__ . "/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: ../cart.php"); exit; }

if (!isset($_SESSION['client_id']) || (($_SESSION['role'] ?? '') !== 'client')) {
  header("Location: ../LoginD.php?error=login_required");
  exit;
}

$user_id = (int)$_SESSION['client_id'];

/* ✅ جيب عناصر السلة مع بيانات المنتجات */
$sql = "
  SELECT ci.id AS cart_id, ci.product_id, ci.qty,
         p.name, p.price, p.is_active
  FROM cart_items ci
  JOIN products p ON p.id = ci.product_id
  WHERE ci.user_id = ?
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$items = [];
$total = 0.0;

while ($row = $res ? mysqli_fetch_assoc($res) : null) {
  if ((int)$row['is_active'] !== 1) continue; // لو منتج صار غير نشط تجاهله
  $qty = (int)$row['qty'];
  $price = (float)$row['price'];
  $total += ($price * $qty);
  $items[] = $row;
}

if (count($items) === 0) {
  header("Location: ../cart.php?err=empty");
  exit;
}

/* ✅ Transaction */
mysqli_begin_transaction($conn);

try {
  // create order
  $stmt = mysqli_prepare($conn, "INSERT INTO orders (user_id, status, total, created_at) VALUES (?, 'pending', ?, NOW())");
  $totalFmt = number_format($total, 2, '.', '');
  mysqli_stmt_bind_param($stmt, "id", $user_id, $totalFmt);
  mysqli_stmt_execute($stmt);

  $order_id = mysqli_insert_id($conn);
  if ($order_id <= 0) throw new Exception("order insert failed");

  // insert items
  $stmtItem = mysqli_prepare($conn, "
    INSERT INTO order_items (order_id, product_id, name, price, qty, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
  ");

  foreach ($items as $it) {
    $pid = (int)$it['product_id'];
    $name = $it['name'];
    $price = (float)$it['price'];
    $qty = (int)$it['qty'];

    mysqli_stmt_bind_param($stmtItem, "iisdi", $order_id, $pid, $name, $price, $qty);
    mysqli_stmt_execute($stmtItem);
  }

  // clear cart
  $stmt = mysqli_prepare($conn, "DELETE FROM cart_items WHERE user_id=?");
  mysqli_stmt_bind_param($stmt, "i", $user_id);
  mysqli_stmt_execute($stmt);

  mysqli_commit($conn);

  header("Location: ../cart.php?ok=placed&order_id=".$order_id);
  exit;

} catch (Exception $e) {
  mysqli_rollback($conn);
  header("Location: ../cart.php?err=place_failed");
  exit;
}