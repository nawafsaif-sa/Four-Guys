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

mysqli_begin_transaction($conn);

try {
  /* 1) هات السلة المفتوحة: pending + بدون order */
  $cart_id = 0;
  $stmt = mysqli_prepare($conn, "
    SELECT c.id
    FROM carts c
    LEFT JOIN orders o ON o.cart_id = c.id
    WHERE c.client_id = ? AND c.status='pending' AND o.id IS NULL
    ORDER BY c.id DESC
    LIMIT 1
  ");
  mysqli_stmt_bind_param($stmt, "i", $client_id);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  if ($row = mysqli_fetch_assoc($res)) $cart_id = (int)$row['id'];

  if ($cart_id <= 0) {
    mysqli_commit($conn);
    header("Location: ../cart.php?err=no_open_cart");
    exit;
  }

  /* 2) تأكد فيها عناصر */
  $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM cart_items WHERE cart_id=?");
  mysqli_stmt_bind_param($stmt, "i", $cart_id);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $count = (int)(mysqli_fetch_assoc($res)['c'] ?? 0);

  if ($count <= 0) {
    mysqli_commit($conn);
    header("Location: ../cart.php?err=empty_cart");
    exit;
  }

  /* 3) إذا بالغلط فيه order لنفس cart (احتياط) */
  $stmt = mysqli_prepare($conn, "SELECT id FROM orders WHERE cart_id=? LIMIT 1");
  mysqli_stmt_bind_param($stmt, "i", $cart_id);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  if ($ex = mysqli_fetch_assoc($res)) {
    mysqli_commit($conn);
    header("Location: ../cart.php?ok=already_confirmed");
    exit;
  }

  /* 4) حساب expected_prep_minutes = MAX(products.prep_minutes) */
  $expected = 0;
  $stmt = mysqli_prepare($conn, "
    SELECT COALESCE(MAX(p.prep_minutes),0) AS mx
    FROM cart_items ci
    JOIN products p ON p.id = ci.product_id
    WHERE ci.cart_id = ?
  ");
  mysqli_stmt_bind_param($stmt, "i", $cart_id);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $expected = (int)(mysqli_fetch_assoc($res)['mx'] ?? 0);

  /* 5) إنشاء order (status = pending) 
        orders.user_id عندك NOT NULL => نخزن client_id فيها
        table_number NULL (أونلاين)
        customer_name NULL (اختياري)
  */
  $status = 'pending';
  $customer_name = null;
  $table_number  = null;

  $stmt = mysqli_prepare($conn, "
    INSERT INTO orders (user_id, customer_name, table_number, status, expected_prep_minutes, created_at, cart_id)
    VALUES (?, ?, ?, ?, ?, NOW(), ?)
  ");
  mysqli_stmt_bind_param($stmt, "isisii", $client_id, $customer_name, $table_number, $status, $expected, $cart_id);
  mysqli_stmt_execute($stmt);

  $order_id = (int)mysqli_insert_id($conn);
  if ($order_id <= 0) throw new Exception("order_insert_failed");

  /* 6) نقل عناصر السلة إلى order_items (مع prep_minutes) */
  $stmt = mysqli_prepare($conn, "
    SELECT ci.product_id, ci.qty, ci.unit_price, p.prep_minutes
    FROM cart_items ci
    JOIN products p ON p.id = ci.product_id
    WHERE ci.cart_id = ?
  ");
  mysqli_stmt_bind_param($stmt, "i", $cart_id);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);

  $ins = mysqli_prepare($conn, "
    INSERT INTO order_items (order_id, product_id, qty, unit_price, prep_minutes)
    VALUES (?, ?, ?, ?, ?)
  ");

  while ($r = mysqli_fetch_assoc($res)) {
    $pid  = (int)$r['product_id'];
    $qty  = (int)$r['qty'];
    $up   = (float)$r['unit_price'];
    $prep = (int)$r['prep_minutes'];
    mysqli_stmt_bind_param($ins, "iiidi", $order_id, $pid, $qty, $up, $prep);
    mysqli_stmt_execute($ins);
  }

  /* 7) تفريغ السلة (حذف cart_items) */
  $stmt = mysqli_prepare($conn, "DELETE FROM cart_items WHERE cart_id=?");
  mysqli_stmt_bind_param($stmt, "i", $cart_id);
  mysqli_stmt_execute($stmt);

  /* 8) cart status يبقى pending (مثل اتفاقنا) */
  // (لا نغيّر status)

  mysqli_commit($conn);
  header("Location: ../cart.php?ok=confirmed");
  exit;

} catch (Exception $e) {
  mysqli_rollback($conn);
  header("Location: ../cart.php?err=confirm_failed");
  exit;
}