<?php
session_start();
require_once "db.php";

/* حماية: فقط manager/admin */
if (!isset($_SESSION['staff_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['manager','admin'])) {
  header("Location: ../StLogin.php?error=1");
  exit;
}

/* فلترة (اختياري) */
$filter = $_GET['filter'] ?? 'all';
$allowed = ['all','active','inactive'];
if (!in_array($filter, $allowed)) $filter = 'all';

/* جلب المنتجات */
$sql = "SELECT id, name, price, prep_minutes, image_path, is_active
        FROM products ";
if ($filter === 'active')   $sql .= "WHERE is_active=1 ";
if ($filter === 'inactive') $sql .= "WHERE is_active=0 ";
$sql .= "ORDER BY id DESC";

$res = mysqli_query($conn, $sql);

/* وضع التعديل */
$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$edit = null;
if ($edit_id > 0) {
  $st = mysqli_prepare($conn, "SELECT id, name, price, prep_minutes, image_path, is_active FROM products WHERE id=? LIMIT 1");
  mysqli_stmt_bind_param($st, "i", $edit_id);
  mysqli_stmt_execute($st);
  $r = mysqli_stmt_get_result($st);
  $edit = mysqli_fetch_assoc($r);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Menu</title>
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

    /* اسم المنتج لا يكسر التصميم */
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
    .small{font-size:13px;color:#666}

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

    .actions{
      display:flex;
      gap:8px;
      justify-content:center;
      flex-wrap:wrap;
    }
    a.link{color:#1976d2;text-decoration:none;font-weight:800}

    img.thumb{
      width:60px;height:60px;
      object-fit:contain;
      background:#fff;
      border:1px solid #eee;
      border-radius:10px;
      padding:4px;
    }

    @media (max-width: 980px){
      .grid{grid-template-columns:1fr;}
      .topbar{flex-direction:column;align-items:flex-start;gap:10px;}
    }
  </style>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="../assets/four-guys.css?v=1">
<script src="../assets/four-guys.js?v=1" defer></script>
</head>
<body class="fg fg-staff fg-page-manage_menu" data-page="manage_menu">
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

  <h1>Menu Management</h1>

  <?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg']==='added'): ?>
      <div class="msg ok">✅ Product added</div>
    <?php elseif ($_GET['msg']==='updated'): ?>
      <div class="msg ok">✅ Product updated</div>
    <?php elseif ($_GET['msg']==='deleted'): ?>
      <div class="msg ok">✅ Product deleted</div>
    <?php else: ?>
      <div class="msg err">❌ Something went wrong</div>
    <?php endif; ?>
  <?php endif; ?>

  <div class="grid">

    <!-- قائمة المنتجات -->
    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <h2 style="margin:0;">Products</h2>

        <form method="GET" style="display:flex;gap:8px;align-items:center;">
          <span class="small">Filter:</span>
          <select name="filter" onchange="this.form.submit()">
            <?php
              foreach (['all'=>'All','active'=>'Active','inactive'=>'Inactive'] as $k=>$v) {
                $sel = ($filter===$k) ? 'selected' : '';
                echo "<option value='{$k}' {$sel}>{$v}</option>";
              }
            ?>
          </select>
        </form>
      </div>

      <table>
        <tr>
          <th style="width:60px;">ID</th>
          <th style="width:90px;">Image</th>
          <th>Name</th>
          <th style="width:110px;">Price</th>
          <th style="width:110px;">Prep</th>
          <th style="width:90px;">Active</th>
          <th style="width:170px;">Actions</th>
        </tr>

        <?php if ($res && mysqli_num_rows($res) > 0): ?>
          <?php while ($p = mysqli_fetch_assoc($res)): ?>
            <tr>
              <td><?= (int)$p['id'] ?></td>
              <td>
                <img class="thumb" src="../<?= htmlspecialchars($p['image_path'] ?: 'Burger.png') ?>" alt="">
              </td>
              <td title="<?= htmlspecialchars($p['name'] ?? '') ?>"><?= htmlspecialchars($p['name'] ?? '') ?></td>
              <td>$<?= number_format((float)$p['price'], 2) ?></td>
              <td><?= (int)$p['prep_minutes'] ?> min</td>
              <td><?= ((int)$p['is_active']===1) ? 'Yes' : 'No' ?></td>
              <td>
                <div class="actions">
                  <a class="link" href="manage_menu.php?filter=<?= urlencode($filter) ?>&edit_id=<?= (int)$p['id'] ?>">Edit</a>

                  <form action="menu_delete.php" method="POST" style="margin:0" onsubmit="return confirm('Delete this product?');">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button class="btn btn-red" type="submit">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="7">No products</td></tr>
        <?php endif; ?>
      </table>
    </div>

    <!-- إضافة / تعديل -->
    <div class="card">
      <?php if ($edit): ?>
        <h2 style="margin-top:0;">Edit Product</h2>

        <form action="menu_save.php" method="POST">
          <input type="hidden" name="mode" value="update">
          <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">

          <label>Name</label>
          <input type="text" name="name" required value="<?= htmlspecialchars($edit['name'] ?? '') ?>">

          <label>Price</label>
          <input type="number" step="0.01" name="price" required value="<?= htmlspecialchars($edit['price'] ?? '0') ?>">

          <label>Prep Minutes</label>
          <input type="number" name="prep_minutes" required value="<?= (int)$edit['prep_minutes'] ?>">

          <label>Image Path</label>
          <input type="text" name="image_path" value="<?= htmlspecialchars($edit['image_path'] ?? '') ?>" placeholder="e.g. B1.png">

          <label>Active</label>
          <select name="is_active">
            <option value="1" <?= ((int)$edit['is_active']===1)?'selected':'' ?>>Yes</option>
            <option value="0" <?= ((int)$edit['is_active']===0)?'selected':'' ?>>No</option>
          </select>

          <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;">
            <button class="btn btn-blue" type="submit">Update</button>
            <a class="btn" style="background:#eee;color:#000;text-decoration:none;"
               href="manage_menu.php?filter=<?= urlencode($filter) ?>">Cancel</a>
          </div>
        </form>

      <?php else: ?>
        <h2 style="margin-top:0;">Add Product</h2>

        <form action="menu_save.php" method="POST">
          <input type="hidden" name="mode" value="add">

          <label>Name</label>
          <input type="text" name="name" required>

          <label>Price</label>
          <input type="number" step="0.01" name="price" required>

          <label>Prep Minutes</label>
          <input type="number" name="prep_minutes" required>

          <label>Image Path</label>
          <input type="text" name="image_path" placeholder="e.g. B1.png">

          <label>Active</label>
          <select name="is_active">
            <option value="1" selected>Yes</option>
            <option value="0">No</option>
          </select>

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
