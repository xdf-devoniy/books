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
    $errors[] = 'The book title is required.';
}

if ($priceInput === '' || !is_numeric($priceInput) || (float) $priceInput < 0) {
    $errors[] = 'Please provide a valid price (0 or greater).';
}

if ($quantityInput === '' || !ctype_digit(str_replace([' ', ','], '', $quantityInput))) {
    $errors[] = 'Quantity must be a whole number.';
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
    $statement = $db->prepare(
        'INSERT INTO books (title, author, category, price, quantity, description)
         VALUES (:title, :author, :category, :price, :quantity, :description)'
    );
    $statement->execute([
        ':title' => $title,
        ':author' => $author !== '' ? $author : null,
        ':category' => $category !== '' ? $category : null,
        ':price' => $price,
        ':quantity' => $quantity,
        ':description' => $description !== '' ? $description : null,
    ]);

    unset($_SESSION['form_data']);

    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => 'Great! "' . $title . '" has been added to the library.',
    ];
    header('Location: ../index.php');
    exit;
} catch (Throwable $exception) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'We could not save the book right now. Please try again. Error: ' . $exception->getMessage(),
    ];
    header('Location: ../add.php');
    exit;
}
