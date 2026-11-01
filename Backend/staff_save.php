<?php
session_start();
require_once "db.php";

/* حماية */
if (!isset($_SESSION['staff_id']) || !in_array($_SESSION['role'], ['manager','admin'])) {
  header("Location: ../StLogin.php?error=1");
  exit;
}

$mode = $_POST['mode'] ?? '';

$full_name = trim($_POST['full_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$role      = trim($_POST['role'] ?? '');
$password  = trim($_POST['password'] ?? '');

$allowed_roles = ['manager','cashier','chef','waiter','admin'];

if ($full_name === '' || $email === '' || $phone === '' || !in_array($role, $allowed_roles)) {
  header("Location: manage_staff.php?msg=error");
  exit;
}

if ($mode === 'add') {
  if ($password === '') {
    header("Location: manage_staff.php?msg=error");
    exit;
  }

  $stmt = mysqli_prepare($conn, "INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?,?,?,?,?)");
  mysqli_stmt_bind_param($stmt, "sssss", $full_name, $email, $phone, $password, $role);
  $ok = mysqli_stmt_execute($stmt);

  header("Location: manage_staff.php?msg=" . ($ok ? "added" : "error"));
  exit;
}

if ($mode === 'update') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) {
    header("Location: manage_staff.php?msg=error");
    exit;
  }

  if ($password !== '') {
    $stmt = mysqli_prepare($conn, "
      UPDATE users
      SET full_name=?, email=?, phone=?, role=?, password_hash=?
      WHERE id=? AND role!='client'
    ");
    mysqli_stmt_bind_param($stmt, "sssssi", $full_name, $email, $phone, $role, $password, $id);
  } else {
    $stmt = mysqli_prepare($conn, "
      UPDATE users
      SET full_name=?, email=?, phone=?, role=?
      WHERE id=? AND role!='client'
    ");
    mysqli_stmt_bind_param($stmt, "ssssi", $full_name, $email, $phone, $role, $id);
  }

  $ok = mysqli_stmt_execute($stmt);
  header("Location: manage_staff.php?msg=" . ($ok ? "updated" : "error"));
  exit;
}

header("Location: manage_staff.php?msg=error");
exit;