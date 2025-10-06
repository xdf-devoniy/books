<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../add.php');
    exit;
}

$title = trim($_POST['title'] ?? '');
$author = trim($_POST['author'] ?? '');
$category = trim($_POST['category'] ?? '');
$priceInput = trim($_POST['price'] ?? '');
$quantityInput = trim($_POST['quantity'] ?? '');
$description = trim($_POST['description'] ?? '');

$_SESSION['form_data'] = [
    'title' => $title,
    'author' => $author,
    'category' => $category,
    'price' => $priceInput,
    'quantity' => $quantityInput,
    'description' => $description,
];

$errors = [];

if ($title === '') {
    $errors[] = 'Please provide the book name.';
}

if ($priceInput === '' || !is_numeric($priceInput) || (float) $priceInput < 0) {
    $errors[] = 'Enter a valid price (0 or greater).';
}

if ($quantityInput === '' || !ctype_digit((string) $quantityInput) || (int) $quantityInput < 0) {
    $errors[] = 'Quantity must be a non-negative whole number.';
}

if ($errors !== []) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => implode(' ', $errors),
    ];
    header('Location: ../add.php');
    exit;
}

$price = round((float) $priceInput, 2);
$quantity = (int) $quantityInput;

try {
    $db = get_db();
    $db->begin_transaction();

    $bookColumns = table_columns($db, 'books');
    $hasAuthor = in_array('author', $bookColumns, true);
    $hasCategory = in_array('category', $bookColumns, true);
    $hasDescription = in_array('description', $bookColumns, true);

    $fields = ['name', 'price'];
    $placeholders = ['?', '?'];
    $types = 'sd';
    $params = [$title, $price];

    if ($hasAuthor) {
        $fields[] = 'author';
        $placeholders[] = '?';
        $types .= 's';
        $params[] = $author !== '' ? $author : null;
    }

    if ($hasCategory) {
        $fields[] = 'category';
        $placeholders[] = '?';
        $types .= 's';
        $params[] = $category !== '' ? $category : null;
    }

    if ($hasDescription) {
        $fields[] = 'description';
        $placeholders[] = '?';
        $types .= 's';
        $params[] = $description !== '' ? $description : null;
    }

    $sql = 'INSERT INTO books (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
    $statement = $db->prepare($sql);
    bind_params($statement, $types, $params);
    $statement->execute();

    $bookId = (int) $db->insert_id;

    // Upsert inventory quantity
    $inventoryExists = false;
    try {
        $checkStatement = $db->prepare('SELECT quantity FROM inventory WHERE book_id = ? LIMIT 1');
        $checkStatement->bind_param('i', $bookId);
        $checkStatement->execute();
        $inventoryExists = (bool) $checkStatement->get_result()->fetch_assoc();
    } catch (Throwable $exception) {
        $inventoryExists = false;
    }

    if ($inventoryExists) {
        $updateSql = 'UPDATE inventory SET quantity = ?';
        if (table_has_column($db, 'inventory', 'updated_at')) {
            $updateSql .= ', updated_at = NOW()';
        }
        $updateSql .= ' WHERE book_id = ?';

        $updateStatement = $db->prepare($updateSql);
        $updateStatement->bind_param('ii', $quantity, $bookId);
        $updateStatement->execute();
    } else {
        $columns = ['book_id', 'quantity'];
        $values = ['?', '?'];
        $typesInventory = 'ii';
        $inventoryParams = [$bookId, $quantity];

        if (table_has_column($db, 'inventory', 'added_date')) {
            $columns[] = 'added_date';
            $values[] = 'NOW()';
        }
        if (table_has_column($db, 'inventory', 'updated_at')) {
            $columns[] = 'updated_at';
            $values[] = 'NOW()';
        }

        $insertSql = 'INSERT INTO inventory (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')';
        $insertStatement = $db->prepare($insertSql);
        bind_params($insertStatement, $typesInventory, $inventoryParams);
        $insertStatement->execute();
    }

    $db->commit();

    unset($_SESSION['form_data']);
    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => '“' . $title . '” has been added to the catalogue.',
    ];
    header('Location: ../index.php');
    exit;
} catch (Throwable $exception) {
    if (isset($db) && $db instanceof mysqli) {
        try {
            $db->rollback();
        } catch (Throwable $rollbackException) {
            // Ignore rollback errors so the original exception can be reported.
        }
    }

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Could not save the book. Please try again. Error: ' . $exception->getMessage(),
    ];
    header('Location: ../add.php');
    exit;
}
