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
$order_id  = (int)($_POST['order_id'] ?? 0);

if ($order_id <= 0) {
  header("Location: ../cart.php?err=bad_order");
  exit;
}

/* 1) تأكد أن الطلب تابع للعميل */
$stmt = mysqli_prepare($conn, "
  SELECT id, status
  FROM orders
  WHERE id = ? AND user_id = ?
  LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ii", $order_id, $client_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($res);

if (!$order) {
  header("Location: ../cart.php?err=not_found");
  exit;
}

/* 2) يسمح بالإلغاء فقط إذا الحالة pending */
if (strtolower($order['status']) !== 'pending') {
  header("Location: ../cart.php?err=not_pending");
  exit;
}

/* 3) إلغاء الطلب */
$newStatus = 'cancelled'; // إذا جدولك يستخدم canceled غيّرها هنا فقط

$stmt = mysqli_prepare($conn, "
  UPDATE orders
  SET status = ?
  WHERE id = ? AND user_id = ? AND status = 'pending'
  LIMIT 1
");
mysqli_stmt_bind_param($stmt, "sii", $newStatus, $order_id, $client_id);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) <= 0) {
  header("Location: ../cart.php?err=cancel_failed");
  exit;
}

header("Location: ../cart.php?ok=Order cancelled successfully");
exit;