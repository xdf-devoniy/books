<?php
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /books.php?account=' . urlencode(current_account()));
    exit;
}

$account = strtolower(trim($_POST['account'] ?? ''));
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if (!$id || !array_key_exists($account, APP_ACCOUNTS)) {
    set_flash('error', 'So‘rov noto‘g‘ri.');
    header('Location: /books.php?account=' . urlencode(current_account()));
    exit;
}

$book = find_book($id, $account);
if (!$book) {
    set_flash('error', 'Kitob topilmadi.');
    header('Location: /books.php?account=' . urlencode($account));
    exit;
}

$title = trim($_POST['title'] ?? '');
$author = trim($_POST['author'] ?? '');
$category = trim($_POST['category'] ?? '');
$quantity = isset($_POST['quantity']) ? max(0, (int) $_POST['quantity']) : 0;
$buying = isset($_POST['buying_price']) ? max(0, (float) $_POST['buying_price']) : 0.0;
$selling = isset($_POST['selling_price']) ? max(0, (float) $_POST['selling_price']) : 0.0;

if ($title === '') {
    set_flash('error', 'Kitob nomi majburiy.');
    header('Location: /edit.php?id=' . $id . '&account=' . urlencode($account));
    exit;
}

try {
    update_book($id, $account, [
        'title' => $title,
        'author' => $author !== '' ? $author : null,
        'category' => $category !== '' ? $category : null,
        'quantity' => $quantity,
        'buying_price' => $buying,
        'selling_price' => $selling,
    ]);
    set_flash('success', 'Kitob yangilandi.');
    header('Location: /books.php?account=' . urlencode($account));
    exit;
} catch (Throwable $e) {
    set_flash('error', 'Yangilashda xatolik: ' . $e->getMessage());
    header('Location: /edit.php?id=' . $id . '&account=' . urlencode($account));
    exit;
}
