<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['client_id']) || ($_SESSION['role'] ?? '') !== 'client') {
  $next = urlencode($_SERVER['REQUEST_URI']);
  header("Location: ../LoginD.php?err=login_required&next=$next");
  exit;
}