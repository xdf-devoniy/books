<?php
include '../db.php';

$book_id = $_POST['book'];
$quantity = $_POST['quantity'];
$added_date = date('Y-m-d');

// Check if the book already exists in the inventory
$sql_check = "SELECT * FROM inventory WHERE book_id = $book_id";
$result_check = $conn->query($sql_check);

if ($result_check->num_rows > 0) {
    // If the book exists, update the quantity
    $sql_update = "UPDATE inventory SET quantity = quantity + $quantity WHERE book_id = $book_id";
    if ($conn->query($sql_update) === TRUE) {
        header('Location: ../index.php');
    } else {
        echo "Error: " . $sql_update . "<br>" . $conn->error;
    }
} else {
    // If the book does not exist, insert a new record
    $sql = "INSERT INTO inventory (book_id, quantity, added_date) VALUES ($book_id, $quantity, '$added_date')";
    if ($conn->query($sql) === TRUE) {
        header('Location: ../index.php');
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>
