<?php
$servername = "localhost";
$username = "harvards_admin"; // change this to your MySQL username
$password = "Rasul9898aa"; // change this to your MySQL password
$dbname = "harvards_dkb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
