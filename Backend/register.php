<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("db.php");

/* Get data from form */
$full_name = $_POST['full_name'];
$email     = $_POST['email'];
$phone     = $_POST['phone'];
$password  = $_POST['password'];

/* Simple insert query */
$sql = "INSERT INTO users (full_name, email, phone, password_hash, role)
        VALUES ('$full_name', '$email', '$phone', '$password', 'client')";

$result = mysqli_query($conn, $sql);

if ($result) {
    echo "User registered successfully ✅";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>
