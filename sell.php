<?php
require_once __DIR__ . '/db.php';

$account = current_account();
$books = fetch_books($account);

$pageTitle = "Sotuvni qayd etish";
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/flash.php';
?>
<div class="max-w-3xl mx-auto bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
    <h2 class="text-xl font-semibold text-slate-800 mb-6">Savdo ma'lumotlari</h2>
    <?php if (empty($books)): ?>
        <p class="text-sm text-slate-500">Avval kitob qo'shing, shundan so'ng sotuvni qayd etishingiz mumkin.</p>
    <?php else: ?>
        <form method="post" action="/actions/record_sale.php" class="space-y-6">
            <input type="hidden" name="account" value="<?= htmlspecialchars($account) ?>" />
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-2" for="book_id">Kitob</label>
                <select id="book_id" name="book_id" required class="w-full rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-blue-500 px-4 py-2.5">
                    <option value="">Kitobni tanlang</option>
                    <?php foreach ($books as $book): ?>
                        <option value="<?= (int) $book['id'] ?>">
                            <?= htmlspecialchars($book['title']) ?> — <?= number_format((int) $book['quantity']) ?> dona mavjud
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-2" for="quantity">Sotilgan miqdor</label>
                <input id="quantity" name="quantity" type="number" min="1" required class="w-full rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-blue-500 px-4 py-2.5" placeholder="1" />
            </div>
            <div class="flex items-center justify-end gap-3">
                <a href="/index.php?account=<?= urlencode($account) ?>" class="px-4 py-2 rounded-lg border border-slate-200 text-sm font-medium text-slate-600 hover:text-slate-800">Bekor qilish</a>
                <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold">Sotuvni saqlash</button>
            </div>
        </form>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
