<?php
session_start();
require_once __DIR__ . "/db.php";

if (!isset($_SESSION['client_id']) || (($_SESSION['role'] ?? '') !== 'client')) {
  header("Location: ../LoginD.php?need_login=1");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: ../cart.php");
  exit;
}

$client_id = (int)$_SESSION['client_id'];
$item_id   = (int)($_POST['item_id'] ?? 0);

if ($item_id <= 0) {
  header("Location: ../cart.php?err=bad_item");
  exit;
}

/* تأكد item تابع لسلة مفتوحة (pending) وبدون order */
$stmt = mysqli_prepare($conn, "
  SELECT ci.id, ci.cart_id
  FROM cart_items ci
  JOIN carts c ON c.id = ci.cart_id
  LEFT JOIN orders o ON o.cart_id = c.id
  WHERE ci.id = ? AND c.client_id = ? AND c.status='pending' AND o.id IS NULL
  LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ii", $item_id, $client_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);

if (!$row) {
  header("Location: ../cart.php?err=not_allowed");
  exit;
}

$cart_id = (int)$row['cart_id'];

$stmt = mysqli_prepare($conn, "DELETE FROM cart_items WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $item_id);
mysqli_stmt_execute($stmt);

/* إذا السلة صارت فاضية (اختياري) */
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM cart_items WHERE cart_id=?");
mysqli_stmt_bind_param($stmt, "i", $cart_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$c = (int)(mysqli_fetch_assoc($res)['c'] ?? 0);

if ($c === 0) {
  // نترك cart موجود (مافي مشكلة) أو تقدر تحذفه
  // mysqli_query($conn, "DELETE FROM carts WHERE id=".$cart_id);
}

header("Location: ../cart.php?ok=removed");
exit;