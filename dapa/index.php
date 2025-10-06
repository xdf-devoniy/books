<?php 
session_start();

// Define the password
$correct_password = 'iyul0624';

// Check if the logout button has been clicked
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Check if the password form has been submitted
if (isset($_POST['password'])) {
    if ($_POST['password'] === $correct_password) {
        $_SESSION['logged_in'] = true;
    } else {
        $error = "Incorrect password!";
    }
}

// If the user is not logged in, show the login form
if (!isset($_SESSION['logged_in'])) {
    ?>
    <form action="" method="POST">
        <label for="password">Enter password:</label>
        <input type="password" name="password" id="password" required>
        <button type="submit">Submit</button>
    </form>
    <?php
    if (isset($error)) {
        echo "<p style='color:red;'>$error</p>";
    }
    exit; // Stop executing the rest of the page until logged in
}

// The user is logged in, show the protected content
include 'partials/header.php';
?>
<style>
    .lock-icon {
        position: absolute;
        top: 10px;
        right: 10px;
        cursor: pointer;
    }
</style>

<div class="lock-icon">
    <a href="?logout=true">
       <i class="bi bi-lock">Lock</i>
    </a>
</div>

<div style="margin-top:50px"></div>
<h2>Hozirda mavjud Kitoblar: Harvard School</h2>
<form action="index.php" method="GET">
    
<div style="margin-top:50px"></div>
<table class="table table-striped mt-3">
    <thead>
        <tr>
            <th>Kitob nomi</th>
            <th>Soni</th>
            <th>Umumiy qiymati (UZS)</th>
        </tr>
    </thead>
    <tbody>
        <?php
        include 'db.php';
        $date = isset($_GET['date']) ? $_GET['date'] : null;
        $query = "SELECT b.name, i.quantity, b.price FROM inventory i JOIN books b ON i.book_id = b.id";
        if ($date) {
            $query .= " WHERE i.added_date <= '$date'";
        }
        $result = $conn->query($query);
        $total_books = 0;
        $total_value = 0;
        while ($row = $result->fetch_assoc()) {
            $total_value_per_book = $row['quantity'] * $row['price'];
            echo "<tr><td>{$row['name']}</td><td>" . number_format($row['quantity']) . "</td><td>" . number_format($total_value_per_book) . "</td></tr>";
            $total_books += $row['quantity'];
            $total_value += $total_value_per_book;
        }
        ?>
    </tbody>
    <tfoot>
        <tr>
            <th>Umumiy Kitoblar soni</th>
            <th><?php echo number_format($total_books); ?></th>
            <th>Umumiy Qiymati: <?php echo number_format($total_value); ?></th>
        </tr>
    </tfoot>
</table>
<?php include 'partials/footer.php'; ?>
