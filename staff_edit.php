<?php
session_start();
require_once "Backend/db.php";

if (!isset($_SESSION['staff_id']) || ($_SESSION['role'] ?? '') !== 'manager') {
  header("Location: StLogin.php?error=1");
  exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: staff_list.php"); exit; }

$err = "";

/* جلب الموظف */
$stmt = mysqli_prepare($conn, "SELECT id, full_name, email, phone, password_hash, role FROM users WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);

if (!$user) { header("Location: staff_list.php?msg=notfound"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name = trim($_POST['full_name'] ?? "");
  $email     = trim($_POST['email'] ?? "");
  $phone     = trim($_POST['phone'] ?? "");
  $pass      = trim($_POST['password'] ?? "");
  $role      = trim($_POST['role'] ?? "");

  if ($full_name === "" || $email === "" || $phone === "" || $role === "") {
    $err = "Full name / email / phone / role are required.";
  } elseif (!in_array($role, ['chef','cashier','waiter','manager'], true)) {
    $err = "Invalid role.";
  } else {
    // لو ترك كلمة المرور فاضية ما نغيرها
    if ($pass === "") {
      $stmt2 = mysqli_prepare($conn, "
        UPDATE users SET full_name=?, email=?, phone=?, role=? WHERE id=?
      ");
      mysqli_stmt_bind_param($stmt2, "ssssi", $full_name, $email, $phone, $role, $id);
    } else {
      $stmt2 = mysqli_prepare($conn, "
        UPDATE users SET full_name=?, email=?, phone=?, password_hash=?, role=? WHERE id=?
      ");
      mysqli_stmt_bind_param($stmt2, "sssssi", $full_name, $email, $phone, $pass, $role, $id);
    }

    if (!mysqli_stmt_execute($stmt2)) {
      $err = "DB Error: " . mysqli_error($conn);
    } else {
      header("Location: staff_list.php?msg=updated");
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Staff</title>
  <style>
    body{font-family:Arial;background:#fafafa;margin:0}
    .wrap{width:420px;max-width:92%;margin:40px auto;background:#fff;border:1px solid #ddd;border-radius:12px;padding:16px}
    input,select{width:100%;padding:10px;border-radius:8px;border:1px solid #ccc;margin-top:6px}
    label{font-weight:800}
    .btn{width:100%;padding:12px;border:none;border-radius:10px;background:#333;color:#fff;font-weight:900;cursor:pointer;margin-top:12px}
    .btn:hover{filter:brightness(.95)}
    .back{display:inline-block;margin-top:10px;text-decoration:none;font-weight:800}
    .err{background:#ffe8e8;border:1px solid #ffbdbd;color:#b71c1c;padding:10px;border-radius:10px;margin-bottom:10px}
    .small{font-size:13px;color:#666}
  </style>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/four-guys.css?v=1">
<script src="assets/four-guys.js?v=1" defer></script>
</head>
<body class="fg fg-staff fg-page-staff_edit" data-page="staff_edit">
<?php $fgBase = ""; include __DIR__ . "/Includes/sidebar.php"; ?>
<main class="fg-workspace" id="main-content">
  <div class="wrap">
    <h2>Edit Staff (ID: <?= (int)$user['id'] ?>)</h2>

    <?php if ($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <form method="POST">
      <label>Full Name</label>
      <input name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>

      <label style="margin-top:10px;">Email</label>
      <input name="email" type="email" value="<?= htmlspecialchars($user['email']) ?>" required>

      <label style="margin-top:10px;">Phone</label>
      <input name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>

      <label style="margin-top:10px;">Password (leave empty to keep)</label>
      <input name="password" type="text" value="">
      <div class="small">If you leave it empty, the password will not change.</div>

      <label style="margin-top:10px;">Role</label>
      <select name="role" required>
        <?php foreach (['manager','chef','cashier','waiter'] as $r): ?>
          <option value="<?= $r ?>" <?= ($user['role'] === $r ? 'selected' : '') ?>>
            <?= $r ?>
          </option>
        <?php endforeach; ?>
      </select>

      <button class="btn" type="submit">Save Changes</button>
    </form>

    <a class="back" href="staff_list.php">← Back to Staff List</a>
  </div>
</main>
</body>
</html>