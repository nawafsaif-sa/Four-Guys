<?php
session_start();
require_once __DIR__ . "/db.php";

if (!isset($_SESSION['client_id']) || (($_SESSION['role'] ?? '') !== 'client')) {
  header("Location: ../LoginD.php?need_login=1");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: ../MenuD.php");
  exit;
}

$client_id  = (int)$_SESSION['client_id'];
$product_id = (int)($_POST['product_id'] ?? 0);
$qty        = (int)($_POST['qty'] ?? 1);
if ($qty < 1) $qty = 1;

if ($product_id <= 0) {
  header("Location: ../MenuD.php?err=bad_product");
  exit;
}

/* 1) تحقق من المنتج + active + السعر */
$stmt = mysqli_prepare($conn, "SELECT id, price, is_active FROM products WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$p = mysqli_fetch_assoc($res);

if (!$p || (int)$p['is_active'] !== 1) {
  header("Location: ../MenuD.php?err=product");
  exit;
}
$unit_price = (float)$p['price'];

/* 2) هات السلة المفتوحة: pending + بدون order (orders.cart_id IS NULL) أو أنشئ واحدة */
$cart_id = 0;
$stmt = mysqli_prepare($conn, "
  SELECT c.id
  FROM carts c
  LEFT JOIN orders o ON o.cart_id = c.id
  WHERE c.client_id = ? AND c.status = 'pending' AND o.id IS NULL
  ORDER BY c.id DESC
  LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $client_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($res)) {
  $cart_id = (int)$row['id'];
} else {
  $stmt = mysqli_prepare($conn, "
    INSERT INTO carts (client_id, status, created_at, updated_at)
    VALUES (?, 'pending', NOW(), NOW())
  ");
  mysqli_stmt_bind_param($stmt, "i", $client_id);
  mysqli_stmt_execute($stmt);
  $cart_id = (int)mysqli_insert_id($conn);
}

/* 3) تأكيد أخير أن الكارت لا يزال مفتوح (pending + بدون order) */
$stmt = mysqli_prepare($conn, "
  SELECT c.id
  FROM carts c
  LEFT JOIN orders o ON o.cart_id = c.id
  WHERE c.id = ? AND c.client_id = ? AND c.status='pending' AND o.id IS NULL
  LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ii", $cart_id, $client_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if (!mysqli_fetch_assoc($res)) {
  header("Location: ../cart.php?err=closed");
  exit;
}

/* 4) هل المنتج موجود مسبقًا في cart_items؟ */
$stmt = mysqli_prepare($conn, "SELECT id, qty FROM cart_items WHERE cart_id=? AND product_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $cart_id, $product_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if ($ci = mysqli_fetch_assoc($res)) {
  $newQty = (int)$ci['qty'] + $qty;

  $stmt = mysqli_prepare($conn, "
    UPDATE cart_items
    SET qty=?, unit_price=?, updated_at=NOW()
    WHERE id=?
  ");
  mysqli_stmt_bind_param($stmt, "idi", $newQty, $unit_price, $ci['id']);
  mysqli_stmt_execute($stmt);

} else {
  $stmt = mysqli_prepare($conn, "
    INSERT INTO cart_items (cart_id, product_id, qty, unit_price, created_at, updated_at)
    VALUES (?, ?, ?, ?, NOW(), NOW())
  ");
  mysqli_stmt_bind_param($stmt, "iiid", $cart_id, $product_id, $qty, $unit_price);
  mysqli_stmt_execute($stmt);
}

/* 5) حدّث updated_at للكارت */
$stmt = mysqli_prepare($conn, "UPDATE carts SET updated_at=NOW() WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $cart_id);
mysqli_stmt_execute($stmt);

header("Location: ../cart.php?ok=added");
exit;