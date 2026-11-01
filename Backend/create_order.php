<?php
session_start();
require_once "db.php";

/* ===== صلاحية عامة: يسمح فقط لـ cashier أو waiter أو client ===== */
if (!isset($_SESSION['role'])) {
  header("Location: ../StLogin.php?error=1");
  exit;
}

$role = $_SESSION['role'];
$allowed = ['cashier','waiter','client'];
if (!in_array($role, $allowed, true)) {
  die("Unauthorized");
}

/* المستخدم اللي أنشأ الطلب */
$user_id = isset($_SESSION['staff_id']) ? (int)$_SESSION['staff_id'] : (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) die("Invalid session user");

/* بيانات الطلب */
$qty = $_POST['qty'] ?? [];
if (!is_array($qty)) {
  header("Location: ../" . ($role === 'cashier' ? "cashier.php?msg=empty" : "Waiter.php?msg=empty"));
  exit;
}

/* بيانات العميل/الطاولة */
$customer_name = trim($_POST['customer_name'] ?? '');
$table_number  = (int)($_POST['table_number'] ?? 0);

if ($customer_name === '') $customer_name = null;

/* رقم الطاولة إجباري */
if ($table_number <= 0) {
  header("Location: ../" . ($role === 'cashier' ? "cashier.php?msg=table_required" : "Waiter.php?msg=table_required"));
  exit;
}

/* جهّز قائمة المنتجات المطلوبة */
$productIds = [];
foreach ($qty as $pid => $q) {
  $pid = (int)$pid;
  $q   = (int)$q;
  if ($pid > 0 && $q > 0) $productIds[] = $pid;
}
$productIds = array_values(array_unique($productIds));

if (count($productIds) === 0) {
  header("Location: ../" . ($role === 'cashier' ? "cashier.php?msg=empty" : "Waiter.php?msg=empty"));
  exit;
}

/* جلب بيانات المنتجات من DB (بدون stock) */
$idList = implode(",", $productIds);
$res = mysqli_query($conn, "
  SELECT id, price, prep_minutes, is_active
  FROM products
  WHERE id IN ($idList)
");

if (!$res) {
  die("DB error while loading products");
}

$products = [];
$maxPrep = 0;

while ($p = mysqli_fetch_assoc($res)) {
  $pid = (int)$p['id'];
  $products[$pid] = $p;

  $prep = (int)$p['prep_minutes'];
  if ($prep > $maxPrep) $maxPrep = $prep;
}

/* تحقق من صلاحية كل منتج */
foreach ($productIds as $pid) {
  if (!isset($products[$pid])) die("Product not found: $pid");
  if ((int)$products[$pid]['is_active'] !== 1) die("Inactive product: $pid");
}

/* ===== إنشاء الطلب ===== */
mysqli_begin_transaction($conn);

try {
  /* ✅ منع تكرار الطاولة إذا عليها طلب نشط */
  $activeStatuses = ['pending','preparing','ready']; // عدّلها إذا عندك statuses مختلفة

  $check = mysqli_prepare(
    $conn,
    "SELECT id FROM orders
     WHERE table_number = ?
       AND status IN ('pending','preparing','ready')
     LIMIT 1
     FOR UPDATE"
  );
  mysqli_stmt_bind_param($check, "i", $table_number);
  mysqli_stmt_execute($check);
  $rCheck = mysqli_stmt_get_result($check);
  $exists = mysqli_fetch_assoc($rCheck);

  if ($exists) {
    mysqli_rollback($conn);
    header("Location: ../" . ($role === 'cashier' ? "cashier.php?msg=table_busy" : "Waiter.php?msg=table_busy"));
    exit;
  }

  $status = 'pending';

  $stmt = mysqli_prepare($conn, "
    INSERT INTO orders (user_id, customer_name, table_number, status, expected_prep_minutes, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
  ");
  mysqli_stmt_bind_param($stmt, "isisi", $user_id, $customer_name, $table_number, $status, $maxPrep);
  mysqli_stmt_execute($stmt);

  $order_id = mysqli_insert_id($conn);
  if ($order_id <= 0) throw new Exception("Order insert failed");

  /* إدخال عناصر الطلب */
  $stmtItem = mysqli_prepare($conn, "
    INSERT INTO order_items (order_id, product_id, qty, unit_price, prep_minutes)
    VALUES (?, ?, ?, ?, ?)
  ");

  foreach ($productIds as $pid) {
    $q = (int)$qty[$pid];
    $price = (float)$products[$pid]['price'];
    $prep  = (int)$products[$pid]['prep_minutes'];

    mysqli_stmt_bind_param($stmtItem, "iiidi", $order_id, $pid, $q, $price, $prep);
    mysqli_stmt_execute($stmtItem);
  }

  mysqli_commit($conn);

  if ($role === 'cashier') {
    header("Location: ../cashier.php?msg=created");
  } elseif ($role === 'waiter') {
    header("Location: ../Waiter.php?msg=created");
  } else {
    header("Location: ../client/orders.php?msg=created");
  }
  exit;

} catch (Exception $e) {
  mysqli_rollback($conn);
  die("Error: " . $e->getMessage());
}
