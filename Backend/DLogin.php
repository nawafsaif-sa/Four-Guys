<?php
// Backend/DLogin.php
session_start();
require_once "db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: ../LoginD.php");
  exit;
}

$phone    = trim($_POST['phone'] ?? '');
$password = trim($_POST['password'] ?? '');
$next     = trim($_POST['next'] ?? ($_GET['next'] ?? ''));

if ($phone === '' || $password === '') {
  header("Location: ../LoginD.php?err=invalid");
  exit;
}

$stmt = mysqli_prepare($conn, "
  SELECT id, full_name, email, phone, password_hash, role
  FROM users
  WHERE phone = ?
  LIMIT 1
");
if (!$stmt) {
  header("Location: ../LoginD.php?err=db");
  exit;
}

mysqli_stmt_bind_param($stmt, "s", $phone);
mysqli_stmt_execute($stmt);
$res  = mysqli_stmt_get_result($stmt);
$user = $res ? mysqli_fetch_assoc($res) : null;

if (!$user) {
  header("Location: ../LoginD.php?err=invalid");
  exit;
}

if (($user['role'] ?? '') !== 'client') {
  header("Location: ../LoginD.php?err=not_client");
  exit;
}

/* بدون هاش (مثل نظامك) */
if ((string)($user['password_hash'] ?? '') !== $password) {
  header("Location: ../LoginD.php?err=invalid");
  exit;
}

/* تسجيل جلسة العميل */
$_SESSION['client_id']    = (int)$user['id'];
$_SESSION['client_name']  = $user['full_name'] ?? '';
$_SESSION['client_phone'] = $user['phone'] ?? '';
$_SESSION['role']         = 'client';

/* رجوع للصفحة المطلوبة */
if ($next !== '') {
  // حماية بسيطة: لازم يرجع داخل نفس المشروع
  if (strpos($next, '/Project/') !== false) {
    header("Location: " . $next);
    exit;
  }
}

/* الافتراضي */
header("Location: ../home.php?login=success");
exit;