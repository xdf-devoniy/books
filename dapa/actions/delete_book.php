<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Missing book identifier.',
    ];
    header('Location: ../index.php');
    exit;
}

try {
    $db = get_db();
    $db->begin_transaction();

    if (table_has_column($db, 'inventory', 'book_id')) {
        $statement = $db->prepare('DELETE FROM inventory WHERE book_id = ?');
        $statement->bind_param('i', $id);
        $statement->execute();
    }

    if (table_has_column($db, 'sales', 'book_id')) {
        $statement = $db->prepare('DELETE FROM sales WHERE book_id = ?');
        $statement->bind_param('i', $id);
        $statement->execute();
    }

    $statement = $db->prepare('DELETE FROM books WHERE id = ? LIMIT 1');
    $statement->bind_param('i', $id);
    $statement->execute();

    $db->commit();

    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => 'The book has been removed.',
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
        'message' => 'Could not delete the book. Error: ' . $exception->getMessage(),
    ];
    header('Location: ../index.php');
    exit;
}
