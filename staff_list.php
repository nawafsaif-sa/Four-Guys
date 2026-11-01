<?php
session_start();
require_once "Backend/db.php";

/* حماية: فقط المدير */
if (!isset($_SESSION['staff_id']) || ($_SESSION['role'] ?? '') !== 'manager') {
  header("Location: StLogin.php?error=1");
  exit;
}

/* حذف موظف */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
  $delete_id = (int)$_POST['delete_id'];

  // لا تحذف نفسك
  if ($delete_id === (int)$_SESSION['staff_id']) {
    header("Location: staff_list.php?msg=self");
    exit;
  }

  // لا تحذف عملاء (لو موجودين) + لا تحذف مديرين ثانيين احتياطًا
  $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id=? AND role IN ('chef','cashier','waiter')");
  mysqli_stmt_bind_param($stmt, "i", $delete_id);
  mysqli_stmt_execute($stmt);

  header("Location: staff_list.php?msg=deleted");
  exit;
}

/* جلب الموظفين */
$res = mysqli_query($conn, "
  SELECT id, full_name, email, phone, role
  FROM users
  WHERE role IN ('manager','chef','cashier','waiter')
  ORDER BY FIELD(role,'manager','chef','cashier','waiter'), id ASC
");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Staff Management</title>
  <style>
    body{font-family:Arial;background:#fafafa;margin:0}
    .top{width:90%;margin:18px auto;display:flex;justify-content:space-between;align-items:center}
    .btn{padding:10px 14px;border-radius:8px;border:none;cursor:pointer;font-weight:800;text-decoration:none;display:inline-block}
    .btn-add{background:#2e7d32;color:#fff}
    .btn-logout{background:#d32f2f;color:#fff}
    .wrap{width:90%;margin:0 auto 30px;background:#fff;border:1px solid #ddd;border-radius:12px;padding:16px}
    table{width:100%;border-collapse:collapse}
    th,td{border:1px solid #ddd;padding:10px;text-align:center}
    th{background:#f5f5f5}
    .small{font-size:13px;color:#666}
    .msg{width:90%;margin:10px auto;background:#fff;border:1px solid #ddd;border-radius:10px;padding:10px}
    .danger{background:#d32f2f;color:#fff}
    .edit{background:#333;color:#fff}
  </style>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/four-guys.css?v=1">
<script src="assets/four-guys.js?v=1" defer></script>
</head>
<body class="fg fg-staff fg-page-staff_list" data-page="staff_list">
<?php $fgBase = ""; include __DIR__ . "/Includes/sidebar.php"; ?>
<main class="fg-workspace" id="main-content">

<div class="top">
  <div>
    <b>Manager Panel</b> <span class="small">| Staff Management</span>
  </div>
  <div style="display:flex;gap:10px;">
    <a class="btn btn-add" href="staff_add.php">+ Add Staff</a>
    <a class="btn btn-logout" href="Backend/logout.php">Logout</a>
  </div>
</div>

<?php if (isset($_GET['msg'])): ?>
  <div class="msg">
    <?php
      if ($_GET['msg'] === 'deleted') echo "✅ Staff deleted";
      elseif ($_GET['msg'] === 'self') echo "❌ You cannot delete your own account";
      elseif ($_GET['msg'] === 'updated') echo "✅ Staff updated";
      elseif ($_GET['msg'] === 'added') echo "✅ Staff added";
    ?>
  </div>
<?php endif; ?>

<div class="wrap">
  <table>
    <tr>
      <th>ID</th>
      <th>Full Name</th>
      <th>Email</th>
      <th>Phone</th>
      <th>Role</th>
      <th>Actions</th>
    </tr>

    <?php while ($u = mysqli_fetch_assoc($res)): ?>
      <tr>
        <td><?= (int)$u['id'] ?></td>
        <td><?= htmlspecialchars($u['full_name']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><?= htmlspecialchars($u['phone']) ?></td>
        <td><b><?= htmlspecialchars($u['role']) ?></b></td>
        <td style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
          <a class="btn edit" href="staff_edit.php?id=<?= (int)$u['id'] ?>">Edit</a>

          <?php if ($u['role'] !== 'manager'): ?>
            <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this staff?');">
              <input type="hidden" name="delete_id" value="<?= (int)$u['id'] ?>">
              <button class="btn danger" type="submit">Delete</button>
            </form>
          <?php else: ?>
            <span class="small">—</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>
</div>

</main>
</body>
</html>