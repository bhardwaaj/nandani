<?php
$host = "localhost";
$db = "clinic_app";
$user = "root";
$pass = ""; // your MySQL password

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
session_start();
?> 