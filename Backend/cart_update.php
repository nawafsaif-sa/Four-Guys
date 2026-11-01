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
$qty       = (int)($_POST['qty'] ?? 1);
if ($qty < 1) $qty = 1;

if ($item_id <= 0) {
  header("Location: ../cart.php?err=bad_item");
  exit;
}

/* تأكد item تابع لسلة مفتوحة (pending) وبدون order */
$stmt = mysqli_prepare($conn, "
  SELECT ci.id
  FROM cart_items ci
  JOIN carts c ON c.id = ci.cart_id
  LEFT JOIN orders o ON o.cart_id = c.id
  WHERE ci.id = ? AND c.client_id = ? AND c.status='pending' AND o.id IS NULL
  LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ii", $item_id, $client_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (!mysqli_fetch_assoc($res)) {
  header("Location: ../cart.php?err=not_allowed");
  exit;
}

$stmt = mysqli_prepare($conn, "UPDATE cart_items SET qty=?, updated_at=NOW() WHERE id=?");
mysqli_stmt_bind_param($stmt, "ii", $qty, $item_id);
mysqli_stmt_execute($stmt);

header("Location: ../cart.php?ok=qty_updated");
exit;