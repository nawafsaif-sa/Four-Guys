<?php
session_start();
require_once "db.php";

/* حماية */
if (!isset($_SESSION['staff_id']) || !in_array($_SESSION['role'], ['manager','admin'])) {
  header("Location: ../StLogin.php?error=1");
  exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
  header("Location: manage_staff.php?msg=error");
  exit;
}

/* لا تحذف نفسك */
if ($id === (int)$_SESSION['staff_id']) {
  header("Location: manage_staff.php?msg=error");
  exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id=? AND role!='client'");
mysqli_stmt_bind_param($stmt, "i", $id);
$ok = mysqli_stmt_execute($stmt);

header("Location: manage_staff.php?msg=" . ($ok ? "deleted" : "error"));
exit;