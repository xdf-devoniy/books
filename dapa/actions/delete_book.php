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
        'message' => 'Invalid book identifier.',
    ];
    header('Location: ../index.php');
    exit;
}

try {
    $db = get_db();
    $book = fetch_book($db, $id);

    if (!$book) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'The book was already removed or never existed.',
        ];
        header('Location: ../index.php');
        exit;
    }

    $statement = $db->prepare('DELETE FROM books WHERE id = :id');
    $statement->execute([':id' => $id]);

    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => '"' . $book['title'] . '" has been removed from the catalogue.',
    ];
    header('Location: ../index.php');
    exit;
} catch (Throwable $exception) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Unable to delete the book. Please try again. Error: ' . $exception->getMessage(),
    ];
    header('Location: ../index.php');
    exit;
}
