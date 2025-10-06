<?php
include '../db.php';

$book_id = $_POST['book'];
$quantity = $_POST['quantity'];
$sale_type = $_POST['sale_type'];
$sale_date = date('Y-m-d');

$sql_check = "SELECT quantity FROM inventory WHERE book_id = $book_id";
$result_check = $conn->query($sql_check);
$row_check = $result_check->fetch_assoc();

if ($row_check['quantity'] >= $quantity) {
    $sql_update = "UPDATE inventory SET quantity = quantity - $quantity WHERE book_id = $book_id";
    $conn->query($sql_update);

    $sql = "INSERT INTO sales (book_id, quantity, sale_type, sale_date) VALUES ($book_id, $quantity, '$sale_type', '$sale_date')";
    if ($conn->query($sql) === TRUE) {
        header('Location: ../index.php');
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
} else {
    echo "<script>alert('There is no books found');window.location.href='../sell.php';</script>";
}

$conn->close();
?>
