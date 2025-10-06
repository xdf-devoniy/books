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

try {
    delete_book($id, $account);
    set_flash('success', 'Kitob o‘chirildi.');
} catch (Throwable $e) {
    set_flash('error', 'O‘chirishda xatolik: ' . $e->getMessage());
}

header('Location: /books.php?account=' . urlencode($account));
exit;
