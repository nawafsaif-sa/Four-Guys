<?php
session_start();
require_once "db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: ../ForgotPassword.php");
  exit;
}

$phone = trim($_POST['phone'] ?? '');
$q     = trim($_POST['security_question'] ?? '');
$a     = trim($_POST['security_answer'] ?? '');

if ($phone === '' || $q === '' || $a === '') {
  header("Location: ../ForgotPassword.php?e=empty");
  exit;
}

/* لازم يكون client + نفس السؤال + نفس الجواب */
$stmt = mysqli_prepare($conn, "
  SELECT id, security_question, security_answer
  FROM users
  WHERE phone = ? AND role = 'client'
  LIMIT 1
");
mysqli_stmt_bind_param($stmt, "s", $phone);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$u   = $res ? mysqli_fetch_assoc($res) : null;

if (!$u) {
  header("Location: ../ForgotPassword.php?e=notfound");
  exit;
}

/* لو بيانات السؤال/الجواب فاضية عند المستخدم */
if (empty($u['security_question']) || empty($u['security_answer'])) {
  header("Location: ../ForgotPassword.php?e=notfound");
  exit;
}

/* لازم يطابق السؤال */
if ($q !== $u['security_question']) {
  header("Location: ../ForgotPassword.php?e=wrong");
  exit;
}

/* نطبّع الجواب (مسافات + حروف صغيرة) عشان ما يتعب المستخدم */
$inputAns = mb_strtolower(trim($a), 'UTF-8');
$dbAns    = mb_strtolower(trim($u['security_answer']), 'UTF-8');

if ($inputAns !== $dbAns) {
  header("Location: ../ForgotPassword.php?e=wrong");
  exit;
}

/* ✅ نجاح: خزّن user_id في السيشن وودّه لصفحة تغيير الباسورد */
$_SESSION['reset_user_id'] = (int)$u['id'];
header("Location: ../ResetPassword.php");
exit;