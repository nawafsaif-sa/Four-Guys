<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/db.php";

$q = mysqli_query($conn, "
  SELECT COUNT(*) AS total_orders
  FROM orders
  WHERE DATE(created_at)=CURDATE() AND status='completed'
");

$row = $q ? mysqli_fetch_assoc($q) : null;

echo json_encode([
  "ok" => true,
  "total_orders" => (int)($row['total_orders'] ?? 0)
]);