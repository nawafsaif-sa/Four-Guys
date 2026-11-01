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

$client_id  = (int)$_SESSION['client_id'];
$order_id   = (int)($_POST['order_id'] ?? 0);
$product_id = (int)($_POST['product_id'] ?? 0);
$rating     = (int)($_POST['rating'] ?? 0);
$comment    = trim((string)($_POST['comment'] ?? ''));

if ($order_id <= 0 || $product_id <= 0) {
  header("Location: ../cart.php?err=bad_request");
  exit;
}
if ($rating < 1 || $rating > 5) $rating = 5;

/* 1) تأكد أن الطلب للعميل ومكتمل */
$stmt = mysqli_prepare($conn, "SELECT status FROM orders WHERE id=? AND user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $order_id, $client_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$o = mysqli_fetch_assoc($res);

if (!$o) {
  header("Location: ../cart.php?err=order_not_found");
  exit;
}
$st = strtolower((string)$o['status']);
if ($st === 'completed') $st = 'complete';
if ($st !== 'complete') {
  header("Location: ../cart.php?err=only_complete");
  exit;
}

/* 2) تأكد أن المنتج موجود فعلاً داخل هذا الطلب */
$stmt = mysqli_prepare($conn, "SELECT 1 FROM order_items WHERE order_id=? AND product_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $order_id, $product_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if (!mysqli_fetch_assoc($res)) {
  header("Location: ../cart.php?err=not_in_order");
  exit;
}

/* 3) منع التقييم المكرر لنفس المنتج من نفس العميل */
$stmt = mysqli_prepare($conn, "SELECT id FROM product_reviews WHERE product_id=? AND client_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $product_id, $client_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if ($exist = mysqli_fetch_assoc($res)) {
  header("Location: ../review_product.php?order_id={$order_id}&err=already_reviewed");
  exit;
}

/* 4) حفظ التقييم */
$stmt = mysqli_prepare($conn, "
  INSERT INTO product_reviews (product_id, client_id, rating, comment, created_at)
  VALUES (?, ?, ?, ?, NOW())
");
mysqli_stmt_bind_param($stmt, "iiis", $product_id, $client_id, $rating, $comment);

if (!mysqli_stmt_execute($stmt)) {
  header("Location: ../review_product.php?order_id={$order_id}&err=save_failed");
  exit;
}

header("Location: ../review_product.php?order_id={$order_id}&ok=Review saved");
exit;