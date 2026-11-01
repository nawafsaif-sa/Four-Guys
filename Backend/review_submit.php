<?php
session_start();
if (!isset($_SESSION['client_id']) || (($_SESSION['role'] ?? '') !== 'client')) {
  header("Location: ../LoginD.php?need_login=1");
  exit;
}

require_once __DIR__ . "/db.php";
$client_id = (int)$_SESSION['client_id'];

function go($order_id, $ok = "", $err = ""){
  $q = "order_id=".(int)$order_id;
  if ($ok !== "")  $q .= "&ok=" . urlencode($ok);
  if ($err !== "") $q .= "&err=" . urlencode($err);
  header("Location: ../rate_order.php?$q");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: ../cart.php");
  exit;
}

$order_id = (int)($_POST['order_id'] ?? 0);
if ($order_id <= 0) {
  header("Location: ../cart.php?err=bad_order");
  exit;
}

/* تأكد أن الطلب للعميل + مكتمل */
$stmt = mysqli_prepare($conn, "SELECT id, status FROM orders WHERE id=? AND user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $order_id, $client_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($res);

if (!$order) go($order_id, "", "Order not found.");

$st = strtolower((string)$order['status']);
if ($st === 'completed') $st = 'complete';
if ($st !== 'complete') go($order_id, "", "Only completed orders can be reviewed.");

$product_ids = $_POST['product_ids'] ?? [];
if (!is_array($product_ids) || count($product_ids) === 0) {
  go($order_id, "", "No products found to review.");
}

/* (اختياري/أمان) تأكد المنتجات فعلاً ضمن الطلب */
$ids = array_map('intval', $product_ids);
$in  = implode(",", $ids);

$allowed = [];
$r = mysqli_query($conn, "SELECT DISTINCT product_id FROM order_items WHERE order_id=".(int)$order_id." AND product_id IN ($in)");
if ($r) {
  while ($x = mysqli_fetch_assoc($r)) $allowed[(int)$x['product_id']] = true;
}

mysqli_begin_transaction($conn);

try {
  $saved = 0;

  foreach ($ids as $pid) {
    if ($pid <= 0) continue;
    if (!isset($allowed[$pid])) continue; // مو ضمن الطلب

    $ratingKey  = "rating_" . $pid;
    $commentKey = "comment_" . $pid;

    $rating  = (int)($_POST[$ratingKey] ?? 5);
    if ($rating < 1) $rating = 1;
    if ($rating > 5) $rating = 5;

    $comment = trim((string)($_POST[$commentKey] ?? ''));
    if ($comment === '') $comment = null;

    /* هل فيه تقييم سابق لنفس المنتج من نفس العميل؟ */
    $stmt = mysqli_prepare($conn, "SELECT id FROM product_reviews WHERE product_id=? AND client_id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $pid, $client_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $ex = mysqli_fetch_assoc($res);

    if ($ex) {
      // UPDATE
      if ($comment === null) {
        $stmt = mysqli_prepare($conn, "UPDATE product_reviews SET rating=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ii", $rating, $ex['id']);
      } else {
        $stmt = mysqli_prepare($conn, "UPDATE product_reviews SET rating=?, comment=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "isi", $rating, $comment, $ex['id']);
      }
      mysqli_stmt_execute($stmt);
      $saved++;
    } else {
      // INSERT
      $stmt = mysqli_prepare($conn, "INSERT INTO product_reviews (product_id, client_id, rating, comment, created_at) VALUES (?,?,?,?, NOW())");
      mysqli_stmt_bind_param($stmt, "iiis", $pid, $client_id, $rating, $comment);
      mysqli_stmt_execute($stmt);
      $saved++;
    }
  }

  mysqli_commit($conn);
  go($order_id, "Saved ($saved) review(s).");
} catch (Throwable $e) {
  mysqli_rollback($conn);
  go($order_id, "", "Failed to save reviews.");
}