<?php
session_start();
require_once "db.php";

/* حماية الصفحة */
if (!isset($_SESSION['staff_id']) || !in_array($_SESSION['role'], ['manager','admin'])) {
  header("Location: ../StLogin.php?error=1");
  exit;
}

/* جلب كل رسائل Contact */
$messages = mysqli_query($conn, "
  SELECT id, name, email, subject, message, created_at
  FROM contact_messages
  ORDER BY created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contact Messages</title>
<style>
body{font-family:Arial;background:#fafafa;margin:0}
h1{text-align:center;margin:30px 0}
.container{width:90%;margin:auto}
table{width:100%;border-collapse:collapse;background:#fff}
th,td{border:1px solid #ddd;padding:10px;text-align:center;vertical-align:top}
th{background:#f1f1f1}
.back{
  display:inline-block;margin:20px;
  background:#1976d2;color:#fff;
  padding:10px 16px;border-radius:6px;
  text-decoration:none;font-weight:bold
}
.back:hover{background:#0d47a1}
.msg{text-align:left;white-space:pre-wrap}
.subj{text-align:left;font-weight:900}
.small{font-size:12px;color:#666}
</style>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="../assets/four-guys.css?v=1">
<script src="../assets/four-guys.js?v=1" defer></script>
</head>

<body class="fg fg-staff fg-page-contact_messages" data-page="contact_messages">
<?php $fgBase = "../"; include __DIR__ . "/../Includes/sidebar.php"; ?>
<main class="fg-workspace" id="main-content">

<a class="back" href="../manager.php">← Back to Dashboard</a>

<h1>Contact Messages</h1>

<div class="container">
<table>
<tr>
  <th style="width:70px;">ID</th>
  <th style="width:160px;">Name</th>
  <th style="width:220px;">Email</th>
  <th>Subject</th>
  <th>Message</th>
  <th style="width:180px;">Date</th>
</tr>

<?php if ($messages && mysqli_num_rows($messages) > 0): ?>
  <?php while ($m = mysqli_fetch_assoc($messages)): ?>
    <tr>
      <td><?= (int)$m['id'] ?></td>
      <td><?= htmlspecialchars($m['name']) ?></td>
      <td>
        <?= htmlspecialchars($m['email']) ?><br>
        <span class="small"></span>
      </td>
      <td class="subj"><?= htmlspecialchars($m['subject']) ?></td>
      <td class="msg"><?= htmlspecialchars($m['message']) ?></td>
      <td><?= htmlspecialchars($m['created_at']) ?></td>
    </tr>
  <?php endwhile; ?>
<?php else: ?>
  <tr>
    <td colspan="6">No messages found</td>
  </tr>
<?php endif; ?>

</table>
</div>

</main>
</body>
</html>