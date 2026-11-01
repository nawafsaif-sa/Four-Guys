<?php
session_start();
require_once "db.php";

/* حماية: cashier فقط */
if (!isset($_SESSION['staff_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'cashier') {
  header("Location: ../StLogin.php?error=1");
  exit;
}

$order_id = (int)($_POST['order_id'] ?? 0);
if ($order_id <= 0) {
  header("Location: ../cashier.php?msg=err");
  exit;
}

/* لازم يكون جاهز عشان يكمله */
$st = mysqli_prepare($conn, "SELECT status FROM orders WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($st, "i", $order_id);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);

if (!$res || mysqli_num_rows($res) !== 1) {
  header("Location: ../cashier.php?msg=notfound");
  exit;
}

$current = mysqli_fetch_assoc($res)['status'] ?? '';

if ($current !== 'ready') {
  header("Location: ../cashier.php?msg=notallowed");
  exit;
}

$newStatus = 'completed';

$st2 = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ?");
mysqli_stmt_bind_param($st2, "si", $newStatus, $order_id);
$ok = mysqli_stmt_execute($st2);

header("Location: ../cashier.php?msg=" . ($ok ? "completed" : "err"));
exit;
