<?php
session_start();
require_once "db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: ../SignUp.php");
  exit;
}

$name     = trim($_POST['full_name'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$password = trim($_POST['password'] ?? '');

/* ✅ بيانات سؤال الأمان */
$sec_q = trim($_POST['security_question'] ?? '');
$sec_a = trim($_POST['security_answer'] ?? '');

if ($name === '' || $phone === '' || $password === '' || $sec_q === '' || $sec_a === '') {
  header("Location: ../SignUp.php?err=empty");
  exit;
}

/* منع تكرار رقم الجوال للعميل */
$chk = mysqli_prepare($conn, "
  SELECT id FROM users
  WHERE phone = ? AND role = 'client'
  LIMIT 1
");
mysqli_stmt_bind_param($chk, "s", $phone);
mysqli_stmt_execute($chk);
$res = mysqli_stmt_get_result($chk);

if ($res && mysqli_num_rows($res) > 0) {
  header("Location: ../SignUp.php?err=phone");
  exit;
}

/* إدخال العميل */
$stmt = mysqli_prepare($conn, "
  INSERT INTO users
    (full_name, phone, password_hash, role, security_question, security_answer)
  VALUES
    (?, ?, ?, 'client', ?, ?)
");
mysqli_stmt_bind_param(
  $stmt,
  "sssss",
  $name,
  $phone,
  $password,
  $sec_q,
  $sec_a
);
mysqli_stmt_execute($stmt);

/* تسجيل دخول مباشر */
$_SESSION['client_id']    = mysqli_insert_id($conn);
$_SESSION['client_name']  = $name;
$_SESSION['client_phone'] = $phone;
$_SESSION['role']         = 'client';

header("Location: ../home.php?signup=success");
exit;