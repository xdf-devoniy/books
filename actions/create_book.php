<?php
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /books.php?account=' . urlencode(current_account()));
    exit;
}

$account = strtolower(trim($_POST['account'] ?? ''));
if (!array_key_exists($account, APP_ACCOUNTS)) {
    set_flash('error', 'Noto‘g‘ri hisob tanlandi.');
    header('Location: /books.php?account=' . urlencode(current_account()));
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
    header('Location: /create.php?account=' . urlencode($account));
    exit;
}

try {
    create_book($account, [
        'title' => $title,
        'author' => $author !== '' ? $author : null,
        'category' => $category !== '' ? $category : null,
        'quantity' => $quantity,
        'buying_price' => $buying,
        'selling_price' => $selling,
    ]);
    set_flash('success', 'Kitob muvaffaqiyatli qo‘shildi.');
    header('Location: /books.php?account=' . urlencode($account));
    exit;
} catch (Throwable $e) {
    set_flash('error', 'Kitobni saqlashda xatolik: ' . $e->getMessage());
    header('Location: /create.php?account=' . urlencode($account));
    exit;
}
