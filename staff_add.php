<?php
session_start();
require_once "Backend/db.php";

if (!isset($_SESSION['staff_id']) || ($_SESSION['role'] ?? '') !== 'manager') {
  header("Location: StLogin.php?error=1");
  exit;
}

$err = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name = trim($_POST['full_name'] ?? "");
  $email     = trim($_POST['email'] ?? "");
  $phone     = trim($_POST['phone'] ?? "");
  $pass      = trim($_POST['password'] ?? "");
  $role      = trim($_POST['role'] ?? "");

  if ($full_name === "" || $email === "" || $phone === "" || $pass === "" || $role === "") {
    $err = "All fields are required.";
  } elseif (!in_array($role, ['chef','cashier','waiter','manager'], true)) {
    $err = "Invalid role.";
  } else {
    $stmt = mysqli_prepare($conn, "
      INSERT INTO users (full_name, email, phone, password_hash, role)
      VALUES (?, ?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "sssss", $full_name, $email, $phone, $pass, $role);

    if (!mysqli_stmt_execute($stmt)) {
      $err = "DB Error: " . mysqli_error($conn);
    } else {
      header("Location: staff_list.php?msg=added");
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Add Staff</title>
  <style>
    body{font-family:Arial;background:#fafafa;margin:0}
    .wrap{width:420px;max-width:92%;margin:40px auto;background:#fff;border:1px solid #ddd;border-radius:12px;padding:16px}
    input,select{width:100%;padding:10px;border-radius:8px;border:1px solid #ccc;margin-top:6px}
    label{font-weight:800}
    .btn{width:100%;padding:12px;border:none;border-radius:10px;background:#2e7d32;color:#fff;font-weight:900;cursor:pointer;margin-top:12px}
    .btn:hover{filter:brightness(.95)}
    .back{display:inline-block;margin-top:10px;text-decoration:none;font-weight:800}
    .err{background:#ffe8e8;border:1px solid #ffbdbd;color:#b71c1c;padding:10px;border-radius:10px;margin-bottom:10px}
  </style>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/four-guys.css?v=1">
<script src="assets/four-guys.js?v=1" defer></script>
</head>
<body class="fg fg-staff fg-page-staff_add" data-page="staff_add">
<?php $fgBase = ""; include __DIR__ . "/Includes/sidebar.php"; ?>
<main class="fg-workspace" id="main-content">
  <div class="wrap">
    <h2>Add Staff</h2>

    <?php if ($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <form method="POST">
      <label>Full Name</label>
      <input name="full_name" required>

      <label style="margin-top:10px;">Email</label>
      <input name="email" type="email" required>

      <label style="margin-top:10px;">Phone</label>
      <input name="phone" required>

      <label style="margin-top:10px;">Password (no encryption)</label>
      <input name="password" type="text" required>

      <label style="margin-top:10px;">Role</label>
      <select name="role" required>
        <option value="chef">chef</option>
        <option value="cashier">cashier</option>
        <option value="waiter">waiter</option>
        <option value="manager">manager</option>
      </select>

      <button class="btn" type="submit">Create</button>
    </form>

    <a class="back" href="staff_list.php">← Back to Staff List</a>
  </div>
</main>
</body>
</html>