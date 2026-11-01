<?php
session_start();
require_once "db.php";

/* حماية */
if (!isset($_SESSION['staff_id']) || !in_array($_SESSION['role'], ['manager','admin'])) {
  header("Location: ../StLogin.php?error=1");
  exit;
}

$mode = $_POST['mode'] ?? '';

$name         = trim($_POST['name'] ?? '');
$price        = (float)($_POST['price'] ?? 0);
$prep_minutes = (int)($_POST['prep_minutes'] ?? 0);
$image_path   = trim($_POST['image_path'] ?? '');
$is_active    = (int)($_POST['is_active'] ?? 1);

/* تحقق بسيط */
if ($name === '' || $price < 0 || $prep_minutes < 0) {
  header("Location: manage_menu.php?msg=error");
  exit;
}

if ($mode === 'add') {
  $stmt = mysqli_prepare($conn,
    "INSERT INTO products (name, price, prep_minutes, image_path, is_active)
     VALUES (?,?,?,?,?)"
  );
  mysqli_stmt_bind_param($stmt, "sdisi", $name, $price, $prep_minutes, $image_path, $is_active);
  $ok = mysqli_stmt_execute($stmt);

  header("Location: manage_menu.php?msg=" . ($ok ? "added" : "error"));
  exit;
}

if ($mode === 'update') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) {
    header("Location: manage_menu.php?msg=error");
    exit;
  }

  $stmt = mysqli_prepare($conn,
    "UPDATE products
     SET name=?, price=?, prep_minutes=?, image_path=?, is_active=?
     WHERE id=?"
  );
  mysqli_stmt_bind_param($stmt, "sdisii", $name, $price, $prep_minutes, $image_path, $is_active, $id);
  $ok = mysqli_stmt_execute($stmt);

  header("Location: manage_menu.php?msg=" . ($ok ? "updated" : "error"));
  exit;
}

header("Location: manage_menu.php?msg=error");
exit;
