<?php
session_start();
require_once __DIR__ . "/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: ../ResetPassword.php");
  exit;
}

if (!isset($_SESSION['reset_user_id'])) {
  header("Location: ../ForgotPassword.php?e=invalid");
  exit;
}

$new = trim($_POST['new_password'] ?? '');
if ($new === '') {
  header("Location: ../ResetPassword.php?e=empty");
  exit;
}

$user_id = (int)$_SESSION['reset_user_id'];

/* تحديث كلمة المرور (بدون هاش مثل ما طلبت) */
$up = mysqli_prepare($conn, "UPDATE users SET password_hash = ? WHERE id = ? AND role='client' LIMIT 1");
mysqli_stmt_bind_param($up, "si", $new, $user_id);
mysqli_stmt_execute($up);

/* نظّف سيشن الاسترجاع */
unset($_SESSION['reset_user_id']);

/* رجّعه للّوغن */
header("Location: ../LoginD.php?reset=success");
exit;