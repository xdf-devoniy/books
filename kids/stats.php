<?php include 'partials/header.php'; ?>
<h2>Kun Statistikasi</h2>
<form action="stats.php" method="GET">
    <div class="form-group">
        <label for="date">Sana bo'yicha filtr:</label>
        <input type="date" class="form-control" id="date" name="date" required>
    </div>
    <div style="margin-top: 10px;"></div>
    <button type="submit" class="btn btn-primary">Filter</button>
</form>
<div style="margin-top: 50px;"></div>
<?php
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

include 'db.php';



echo "<h3> $date kuni sotilgan kitoblar</h3>";
$result_sold = $conn->query("SELECT b.name, s.quantity, s.sale_type, b.price FROM sales s JOIN books b ON s.book_id = b.id WHERE s.sale_date = '$date'");
echo "<table class='table table-striped'><thead><tr><th>Kitob nomi</th><th>Soni</th><th>Sotuv turi</th></tr></thead><tbody>";
$total_sold = 0;
$total_sales = 0;
while ($row = $result_sold->fetch_assoc()) {
    echo "<tr><td>{$row['name']}</td><td>" . number_format($row['quantity']) . "</td><td>{$row['sale_type']}</td></tr>";
    $total_sold += $row['quantity'];
    $total_sales += $row['quantity'] * $row['price'];
}
echo "</tbody></table>";

echo "<h3>Sotilgan kitoblar: " . number_format($total_sold) . "</h3>";
echo "<h3>Umumiy savdo: " . number_format($total_sales) . "</h3>";

$conn->close();
?>

<?php include 'partials/footer.php'; ?>
