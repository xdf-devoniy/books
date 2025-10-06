<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../sell.php');
    exit;
}

$bookId = isset($_POST['book']) ? (int) $_POST['book'] : 0;
$quantityInput = trim($_POST['quantity'] ?? '');
$saleType = trim($_POST['sale_type'] ?? '');

$_SESSION['sale_form'] = [
    'book' => $bookId,
    'quantity' => $quantityInput,
    'sale_type' => $saleType,
];

$errors = [];

if ($bookId <= 0) {
    $errors[] = 'Select a book to sell.';
}

if ($quantityInput === '' || !ctype_digit((string) $quantityInput) || (int) $quantityInput <= 0) {
    $errors[] = 'Quantity must be a positive whole number.';
}

$quantity = (int) $quantityInput;

if ($errors !== []) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => implode(' ', $errors),
    ];
    header('Location: ../sell.php');
    exit;
}

try {
    $db = get_db();
    $db->begin_transaction();

    $inventoryStatement = $db->prepare('SELECT quantity FROM inventory WHERE book_id = ? LIMIT 1');
    $inventoryStatement->bind_param('i', $bookId);
    $inventoryStatement->execute();
    $inventoryRow = $inventoryStatement->get_result()->fetch_assoc();

    $available = $inventoryRow ? (int) $inventoryRow['quantity'] : 0;

    if ($available < $quantity) {
        $db->rollback();
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Not enough copies in stock for this sale.',
        ];
        header('Location: ../sell.php');
        exit;
    }

    $updateSql = 'UPDATE inventory SET quantity = quantity - ?';
    if (table_has_column($db, 'inventory', 'updated_at')) {
        $updateSql .= ', updated_at = NOW()';
    }
    $updateSql .= ' WHERE book_id = ? AND quantity >= ?';

    $updateStatement = $db->prepare($updateSql);
    $updateStatement->bind_param('iii', $quantity, $bookId, $quantity);
    $updateStatement->execute();

    if ($updateStatement->affected_rows === 0) {
        $db->rollback();
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Another update changed the stock before this sale. Try again.',
        ];
        header('Location: ../sell.php');
        exit;
    }

    $columns = ['book_id', 'quantity'];
    $values = ['?', '?'];
    $types = 'ii';
    $params = [$bookId, $quantity];

    if (table_has_column($db, 'sales', 'sale_type')) {
        $columns[] = 'sale_type';
        $values[] = '?';
        $types .= 's';
        $params[] = $saleType !== '' ? $saleType : 'standard';
    }

    if (table_has_column($db, 'sales', 'sale_date')) {
        $columns[] = 'sale_date';
        $values[] = '?';
        $types .= 's';
        $params[] = date('Y-m-d');
    }

    if (table_has_column($db, 'sales', 'created_at')) {
        $columns[] = 'created_at';
        $values[] = 'NOW()';
    }

    $insertSql = 'INSERT INTO sales (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')';
    $insertStatement = $db->prepare($insertSql);
    bind_params($insertStatement, $types, $params);
    $insertStatement->execute();

    $db->commit();

    unset($_SESSION['sale_form']);
    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => 'Sale recorded and inventory updated.',
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
        'message' => 'Could not record the sale. Error: ' . $exception->getMessage(),
    ];
    header('Location: ../sell.php');
    exit;
}
