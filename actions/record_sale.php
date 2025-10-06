<?php
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /sell.php?account=' . urlencode(current_account()));
    exit;
}

$account = strtolower(trim($_POST['account'] ?? ''));
$bookId = isset($_POST['book_id']) ? (int) $_POST['book_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 0;

if (!$bookId || $quantity < 1 || !array_key_exists($account, APP_ACCOUNTS)) {
    set_flash('error', 'Ma‘lumotlar to‘liq emas.');
    header('Location: /sell.php?account=' . urlencode($account ?: current_account()));
    exit;
}

try {
    $sale = record_sale($account, $bookId, $quantity);
    $foyda = ($sale['unit_sell_price'] - $sale['unit_buy_price']) * $sale['quantity'];
    set_flash('success', sprintf('%s kitobidan %d dona sotildi. Foyda: %s so‘m.', $sale['book_title'], $sale['quantity'], number_format($foyda, 2, '.', ' ')));
    header('Location: /sell.php?account=' . urlencode($account));
    exit;
} catch (Throwable $e) {
    set_flash('error', 'Sotuvni saqlashda xatolik: ' . $e->getMessage());
    header('Location: /sell.php?account=' . urlencode($account));
    exit;
}
