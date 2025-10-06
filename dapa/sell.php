<?php include 'partials/header.php'; ?>
<h2 style="color:red">Kitob Sotish</h2>
<form action="actions/sell_book.php" method="POST">
    <div class="form-group">
        <label for="book">Kitobni tanlang:</label>
        <select class="form-control" id="book" name="book">
            <?php
            include 'db.php';
            $result = $conn->query("SELECT id, name FROM books");
            while ($row = $result->fetch_assoc()) {
                echo "<option value='{$row['id']}'>{$row['name']}</option>";
            }
            ?>
        </select>
    </div>
    <div class="form-group">
        <label for="quantity">Soni:</label>
        <input type="number" class="form-control" id="quantity" name="quantity" required>
    </div>
    <div class="form-group">
        <label for="sale_type">Sotuv turi:</label>
        <select class="form-control" id="sale_type" name="sale_type">
            <option value="Click">Click</option>
            <option value="Cash">Naqd</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary" style="margin-top:10px">Kitobni sotish</button>
</form>
<?php include 'partials/footer.php'; ?>
