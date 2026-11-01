<?php
session_start();

// حذف كل بيانات الجلسة
session_unset();
session_destroy();

// الرجوع لصفحة تسجيل دخول الموظفين
header("Location: ../StLogin.php");
exit;
?>
