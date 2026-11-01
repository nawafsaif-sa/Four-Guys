<?php
session_start();
require_once "db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: ../contact.php");
  exit;
}

/* ✅ لازم يكون مسجل دخول */
if (!isset($_SESSION['client_id']) || (($_SESSION['role'] ?? '') !== 'client')) {
  header("Location: ../contact.php?err=login");
  exit;
}

$name    = trim($_POST['name'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $phone === '' || $subject === '' || $message === '') {
  header("Location: ../contact.php?err=empty");
  exit;
}

/* ✅ نخزن الجوال في عمود email (بدون تغيير الداتابيس) */
$stmt = mysqli_prepare($conn, "
  INSERT INTO contact_messages (name, email, subject, message, created_at)
  VALUES (?, ?, ?, ?, NOW())
");
if (!$stmt) {
  header("Location: ../contact.php?err=db");
  exit;
}

mysqli_stmt_bind_param($stmt, "ssss", $name, $phone, $subject, $message);
mysqli_stmt_execute($stmt);

header("Location: ../contact.php?ok=1");
exit;