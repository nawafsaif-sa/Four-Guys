<?php
session_start();
require_once "db.php";

/* ===== صلاحية عامة: يسمح لـ cashier أو waiter أو client ===== */
if (!isset($_SESSION['role'])) {
  header("Location: ../StLogin.php?error=1");
  exit;
}

$role = $_SESSION['role'];
$allowed = ['cashier','waiter','client'];
if (!in_array($role, $allowed, true)) {
  die("Unauthorized");
}

/* المستخدم اللي يحاول يلغي */
$user_id = isset($_SESSION['staff_id']) ? (int)$_SESSION['staff_id'] : (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) die("Invalid session user");

$order_id = (int)($_POST['order_id'] ?? 0);
if ($order_id <= 0) {
  header("Location: ../" . ($role === 'cashier' ? "cashier.php?msg=notfound" : "Waiter.php?msg=notfound"));
  exit;
}

/* جلب الطلب */
$st = mysqli_prepare($conn, "SELECT id, user_id, status FROM orders WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($st, "i", $order_id);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);

if (!$res || mysqli_num_rows($res) !== 1) {
  header("Location: ../" . ($role === 'cashier' ? "cashier.php?msg=notfound" : "Waiter.php?msg=notfound"));
  exit;
}

$o = mysqli_fetch_assoc($res);

/* السماح بالإلغاء:
   - waiter/client: فقط لو الطلب تابع لهم
   - cashier: يقدر يلغي أي طلب pending (POS صلاحية)
*/
if ($role !== 'cashier') {
  if ((int)$o['user_id'] !== $user_id) {
    die("Unauthorized");
  }
}

/* فقط pending ينلغى */
if (($o['status'] ?? '') !== 'pending') {
  header("Location: ../" . ($role === 'cashier' ? "cashier.php?msg=notallowed" : "Waiter.php?msg=notallowed"));
  exit;
}

/* نفّذ الإلغاء */
$cancel = 'cancelled';
$st2 = mysqli_prepare($conn, "UPDATE orders SET status=? WHERE id=?");
mysqli_stmt_bind_param($st2, "si", $cancel, $order_id);
$ok = mysqli_stmt_execute($st2);

/* رجّع لكل صفحة حسب الدور */
$dest = ($role === 'cashier') ? "cashier.php" : (($role === 'waiter') ? "Waiter.php" : "client/orders.php");
header("Location: ../{$dest}?msg=" . ($ok ? "cancelled" : "err"));
exit;
