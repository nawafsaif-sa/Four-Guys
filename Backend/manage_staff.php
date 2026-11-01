<?php
session_start();
require_once "db.php";

/* حماية: فقط manager/admin */
if (!isset($_SESSION['staff_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['manager','admin'])) {
  header("Location: ../StLogin.php?error=1");
  exit;
}

$filter_role = $_GET['role'] ?? 'all';
$allowedFilter = ['all','manager','cashier','chef','waiter','admin'];
if (!in_array($filter_role, $allowedFilter)) $filter_role = 'all';

/* جلب الموظفين */
if ($filter_role === 'all') {
  $stmt = mysqli_prepare($conn, "SELECT id, full_name, email, phone, role FROM users WHERE role != 'client' ORDER BY id ASC");
} else {
  $stmt = mysqli_prepare($conn, "SELECT id, full_name, email, phone, role FROM users WHERE role = ? ORDER BY id ASC");
  mysqli_stmt_bind_param($stmt, "s", $filter_role);
}
mysqli_stmt_execute($stmt);
$res_staff = mysqli_stmt_get_result($stmt);

/* لو في تعديل */
$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$edit_staff = null;

if ($edit_id > 0) {
  $st2 = mysqli_prepare($conn, "SELECT id, full_name, email, phone, role FROM users WHERE id=? AND role != 'client' LIMIT 1");
  mysqli_stmt_bind_param($st2, "i", $edit_id);
  mysqli_stmt_execute($st2);
  $r2 = mysqli_stmt_get_result($st2);
  $edit_staff = mysqli_fetch_assoc($r2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Staff</title>
  <style>
    body{
      font-family:Arial;
      background:#fafafa;
      margin:0;
      padding:20px;
    }

    .wrap{
      max-width:1200px;
      margin:0 auto;
    }

    .topbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      margin:10px 0 10px;
    }

    .logout{
      background:#d32f2f;color:#fff;
      padding:10px 14px;border-radius:8px;
      text-decoration:none;font-weight:bold
    }
    .logout:hover{background:#b71c1c}

    .backbar{
      display:flex;
      justify-content:flex-start;
      margin-bottom:10px;
    }
    .backbar a{
      display:inline-block;
      padding:10px 16px;
      background:#1976d2;
      color:#fff;
      text-decoration:none;
      border-radius:8px;
      font-weight:bold;
    }
    .backbar a:hover{background:#125aa0}

    h1{margin:10px 0 15px}

    .msg{
      background:#fff;border:1px solid #ddd;border-radius:12px;
      padding:10px 12px;margin:10px 0;font-weight:800
    }
    .ok{border-color:#b9f6ca;background:#e9fff0;color:#1b5e20}
    .err{border-color:#ffbdbd;background:#ffe8e8;color:#b71c1c}

    .grid{
      display:grid;
      grid-template-columns:1.6fr 1fr;
      gap:16px;
      align-items:start;
    }

    .card{
      background:#fff;
      border:1px solid #ddd;
      border-radius:12px;
      padding:16px;
    }

    /* TABLE */
    table{
      width:100%;
      border-collapse:collapse;
      margin-top:10px;
      table-layout:fixed;
    }
    th,td{
      border:1px solid #ddd;
      padding:10px;
      text-align:center;
      word-wrap:break-word;
    }
    th{background:#eee}

    /* Email عمود 3: لا يطلع برا */
    th:nth-child(3), td:nth-child(3){
      overflow:hidden;
      text-overflow:ellipsis;
      white-space:nowrap;
    }

    input,select{
      width:100%;
      box-sizing:border-box;
      padding:10px;
      border:1px solid #ccc;
      border-radius:10px;
      margin-top:6px;
    }
    label{font-weight:800;display:block;margin-top:10px}

    .btn{
      padding:10px 14px;border:none;border-radius:10px;
      cursor:pointer;font-weight:900
    }
    .btn-green{background:#2e7d32;color:#fff}
    .btn-green:hover{background:#1b5e20}
    .btn-blue{background:#1976d2;color:#fff}
    .btn-blue:hover{background:#125aa0}
    .btn-red{background:#d32f2f;color:#fff}
    .btn-red:hover{background:#b71c1c}

    .small{font-size:13px;color:#666}

    .actions{
      display:flex;
      gap:8px;
      justify-content:center;
      flex-wrap:wrap;
    }
    a.link{color:#1976d2;text-decoration:none;font-weight:800}

    /* Responsive */
    @media (max-width: 980px){
      .grid{grid-template-columns:1fr;}
      .topbar{flex-direction:column;align-items:flex-start;gap:10px;}
    }
  </style>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="../assets/four-guys.css?v=1">
<script src="../assets/four-guys.js?v=1" defer></script>
</head>
<body class="fg fg-staff fg-page-manage_staff" data-page="manage_staff">
<?php $fgBase = "../"; include __DIR__ . "/../Includes/sidebar.php"; ?>
<main class="fg-workspace" id="main-content">

<div class="wrap">

  <div class="backbar">
    <a href="../manager.php">← Back to Manager Dashboard</a>
  </div>

  <div class="topbar">
    <div>Logged in as: <b><?= htmlspecialchars($_SESSION['role']) ?></b></div>
    <a class="logout" href="logout.php">Logout</a>
  </div>

  <h1>Employee Management</h1>

  <?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] === 'added'): ?>
      <div class="msg ok">✅ Staff added successfully</div>
    <?php elseif ($_GET['msg'] === 'updated'): ?>
      <div class="msg ok">✅ Staff updated successfully</div>
    <?php elseif ($_GET['msg'] === 'deleted'): ?>
      <div class="msg ok">✅ Staff deleted successfully</div>
    <?php else: ?>
      <div class="msg err">❌ Something went wrong</div>
    <?php endif; ?>
  <?php endif; ?>

  <div class="grid">

    <!-- قائمة الموظفين -->
    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <h2 style="margin:0;">Staff List</h2>

        <form method="GET" style="display:flex;gap:8px;align-items:center;">
          <span class="small">Filter:</span>
          <select name="role" onchange="this.form.submit()">
            <?php
              foreach (['all','manager','cashier','chef','waiter','admin'] as $r) {
                $sel = ($filter_role === $r) ? 'selected' : '';
                echo "<option value='{$r}' {$sel}>{$r}</option>";
              }
            ?>
          </select>
        </form>
      </div>

      <table>
        <tr>
          <th style="width:60px;">ID</th>
          <th>Full Name</th>
          <th>Email</th>
          <th style="width:120px;">Phone</th>
          <th style="width:100px;">Role</th>
          <th style="width:170px;">Actions</th>
        </tr>

        <?php while ($s = mysqli_fetch_assoc($res_staff)): ?>
          <tr>
            <td><?= (int)$s['id'] ?></td>
            <td><?= htmlspecialchars($s['full_name'] ?? '') ?></td>
            <td title="<?= htmlspecialchars($s['email'] ?? '') ?>"><?= htmlspecialchars($s['email'] ?? '') ?></td>
            <td><?= htmlspecialchars($s['phone'] ?? '') ?></td>
            <td><?= htmlspecialchars($s['role'] ?? '') ?></td>
            <td>
              <div class="actions">
                <a class="link" href="manage_staff.php?role=<?= urlencode($filter_role) ?>&edit_id=<?= (int)$s['id'] ?>">Edit</a>

                <form action="staff_delete.php" method="POST" style="margin:0;" onsubmit="return confirm('Delete this staff?');">
                  <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                  <button class="btn btn-red" type="submit">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
      </table>

      <div class="small" style="margin-top:10px;">
        * يعرض فقط الموظفين (role != client)
      </div>
    </div>

    <!-- إضافة / تعديل -->
    <div class="card">
      <?php if ($edit_staff): ?>
        <h2 style="margin-top:0;">Edit Staff</h2>

        <form action="staff_save.php" method="POST">
          <input type="hidden" name="mode" value="update">
          <input type="hidden" name="id" value="<?= (int)$edit_staff['id'] ?>">

          <label>Full Name</label>
          <input type="text" name="full_name" required value="<?= htmlspecialchars($edit_staff['full_name'] ?? '') ?>">

          <label>Email</label>
          <input type="email" name="email" required value="<?= htmlspecialchars($edit_staff['email'] ?? '') ?>">

          <label>Phone</label>
          <input type="text" name="phone" required value="<?= htmlspecialchars($edit_staff['phone'] ?? '') ?>">

          <label>Role</label>
          <select name="role" required>
            <?php
              foreach (['manager','cashier','chef','waiter','admin'] as $r) {
                $sel = ($edit_staff['role'] === $r) ? 'selected' : '';
                echo "<option value='{$r}' {$sel}>{$r}</option>";
              }
            ?>
          </select>

          <label>New Password (optional)</label>
          <input type="text" name="password" placeholder="leave empty to keep same">

          <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;">
            <button class="btn btn-blue" type="submit">Update</button>
            <a class="btn" style="background:#eee;color:#000;text-decoration:none;"
               href="manage_staff.php?role=<?= urlencode($filter_role) ?>">Cancel</a>
          </div>
        </form>

      <?php else: ?>
        <h2 style="margin-top:0;">Add Staff</h2>

        <form action="staff_save.php" method="POST">
          <input type="hidden" name="mode" value="add">

          <label>Full Name</label>
          <input type="text" name="full_name" required>

          <label>Email</label>
          <input type="email" name="email" required>

          <label>Phone</label>
          <input type="text" name="phone" required>

          <label>Role</label>
          <select name="role" required>
            <option value="cashier">cashier</option>
            <option value="chef">chef</option>
            <option value="waiter">waiter</option>
            <option value="manager">manager</option>
            <option value="admin">admin</option>
          </select>

          <label>Password (no hashing)</label>
          <input type="text" name="password" required>

          <div style="margin-top:12px;">
            <button class="btn btn-green" type="submit">Add</button>
          </div>
        </form>
      <?php endif; ?>
    </div>

  </div>
</div>

</main>
</body>
</html>
