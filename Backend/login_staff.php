<?php
session_start();
require_once "db.php";

$id = (int)($_POST['ID'] ?? 0);
$password = $_POST['Password'] ?? '';

$sql = "SELECT id, role 
        FROM users 
        WHERE id = '$id' 
          AND password_hash = '$password'
          AND role != 'client'";

$res = mysqli_query($conn, $sql);

if ($row = mysqli_fetch_assoc($res)) {

  $_SESSION['staff_id'] = $row['id'];
  $_SESSION['role']     = $row['role'];

  if ($row['role'] === 'manager') {
    header("Location: ../manager.php");
  } elseif ($row['role'] === 'chef') {
    header("Location: ../chef.php");
  } elseif ($row['role'] === 'cashier') {
    header("Location: ../cashier.php");
  } elseif ($row['role'] === 'waiter') {
    header("Location: ../waiter.php");
  }
  exit;
}

header("Location: ../StLogin.php?error=1");
exit;
