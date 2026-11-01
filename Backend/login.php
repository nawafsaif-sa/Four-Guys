<?php

session_start();
include("db.php");

$phone    = $_POST['phone'];
$password = $_POST['password'];

$sql = "SELECT * FROM users
        WHERE phone = '$phone'
        AND password_hash = '$password'
        AND role = 'client'";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) == 1) {

    $user = mysqli_fetch_assoc($result);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role']    = 'client';

    header("Location: ../homelight.html");
    exit;

} else {
    echo "Invalid phone number or password ❌";
}
?>
