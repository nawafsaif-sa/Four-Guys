<?php
session_start();
require_once "db.php";

/* حماية: chef فقط */
if (!isset($_SESSION['staff_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'chef') {
  header("Location: ../StLogin.php?error=1");
  exit;
}

if (!isset($_POST['order_id'], $_POST['action'])) {
  header("Location: ../chef.php?msg=err");
  exit;
}

$order_id = (int)$_POST['order_id'];
$action   = $_POST['action'];

if ($order_id <= 0) {
  header("Location: ../chef.php?msg=err");
  exit;
}

/* جلب الحالة الحالية */
$st = mysqli_prepare($conn, "SELECT status FROM orders WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($st, "i", $order_id);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);

if (!$res || mysqli_num_rows($res) !== 1) {
  header("Location: ../chef.php?msg=notfound");
  exit;
}

$current = mysqli_fetch_assoc($res)['status'] ?? '';

/* انتقالات الشيف فقط */
if ($action === 'start') {
  if ($current !== 'pending') {
    header("Location: ../chef.php?msg=notallowed");
    exit;
  }
  $newStatus = 'preparing';
}
elseif ($action === 'ready') {
  if ($current !== 'preparing') {
    header("Location: ../chef.php?msg=notallowed");
    exit;
  }
  $newStatus = 'ready';
}
else {
  header("Location: ../chef.php?msg=err");
  exit;
}

/* تحديث */
$st2 = mysqli_prepare($conn, "UPDATE orders SET status = ? WHERE id = ?");
mysqli_stmt_bind_param($st2, "si", $newStatus, $order_id);
$ok = mysqli_stmt_execute($st2);

header("Location: ../chef.php?msg=" . ($ok ? "ok" : "err"));
exit;
