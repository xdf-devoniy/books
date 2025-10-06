<?php
require_once __DIR__ . '/db.php';

$account = current_account();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$book = $id ? find_book($id, $account) : null;

if (!$book) {
    set_flash('error', 'Kitob topilmadi yoki bu hisobga tegishli emas.');
    header('Location: /books.php?account=' . urlencode($account));
    exit;
}

$pageTitle = "Kitobni tahrirlash";
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/flash.php';
?>
<div class="max-w-3xl mx-auto bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
    <h2 class="text-xl font-semibold text-slate-800 mb-6"><?= htmlspecialchars($book['title']) ?> kitobi</h2>
    <form method="post" action="/actions/update_book.php" class="space-y-6">
        <input type="hidden" name="id" value="<?= (int) $book['id'] ?>" />
        <input type="hidden" name="account" value="<?= htmlspecialchars($account) ?>" />
        <div class="grid gap-6 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-600 mb-2" for="title">Kitob nomi</label>
                <input id="title" name="title" type="text" required value="<?= htmlspecialchars($book['title']) ?>" class="w-full rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-blue-500 px-4 py-2.5" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-2" for="author">Muallif</label>
                <input id="author" name="author" type="text" value="<?= htmlspecialchars($book['author'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-blue-500 px-4 py-2.5" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-2" for="category">Kategoriya</label>
                <input id="category" name="category" type="text" value="<?= htmlspecialchars($book['category'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-blue-500 px-4 py-2.5" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-2" for="quantity">Mavjud miqdor</label>
                <input id="quantity" name="quantity" type="number" min="0" required value="<?= (int) $book['quantity'] ?>" class="w-full rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-blue-500 px-4 py-2.5" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-2" for="buying_price">Sotib olish narxi (1 dona)</label>
                <input id="buying_price" name="buying_price" type="number" min="0" step="0.01" required value="<?= htmlspecialchars((string) $book['buying_price']) ?>" class="w-full rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-blue-500 px-4 py-2.5" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-2" for="selling_price">Sotish narxi (1 dona)</label>
                <input id="selling_price" name="selling_price" type="number" min="0" step="0.01" required value="<?= htmlspecialchars((string) $book['selling_price']) ?>" class="w-full rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-blue-500 px-4 py-2.5" />
            </div>
        </div>
        <div class="flex items-center justify-end gap-3">
            <a href="/books.php?account=<?= urlencode($account) ?>" class="px-4 py-2 rounded-lg border border-slate-200 text-sm font-medium text-slate-600 hover:text-slate-800">Bekor qilish</a>
            <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">Yangilash</button>
        </div>
    </form>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
