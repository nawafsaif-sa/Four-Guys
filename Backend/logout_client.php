<?php
session_start();

unset($_SESSION['client_id']);
unset($_SESSION['client_name']);
unset($_SESSION['client_phone']);

if (isset($_SESSION['role']) && $_SESSION['role'] === 'client') {
  unset($_SESSION['role']);
}


session_unset();
session_destroy();

header("Location: ../LoginD.php?ok=logged_out");
exit;