<?php
require_once __DIR__ . '/db.php';

$account = current_account();
$books = fetch_books($account);

$pageTitle = "Kitoblar va ombor";
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/flash.php';
?>
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl font-semibold text-slate-800">Kitoblar ro'yxati</h2>
        <p class="text-sm text-slate-500">Har bir kitob uchun sotib olish va sotish narxlarini kuzating.</p>
    </div>
    <a href="/create.php?account=<?= urlencode($account) ?>" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2.5 rounded-lg">
        <span>+ Yangi kitob</span>
    </a>
</div>

<div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Nom</th>
                    <th class="px-4 py-3">Muallif</th>
                    <th class="px-4 py-3">Kategoriya</th>
                    <th class="px-4 py-3 text-right">Miqdor</th>
                    <th class="px-4 py-3 text-right">Sotib olish</th>
                    <th class="px-4 py-3 text-right">Sotish</th>
                    <th class="px-4 py-3 text-right">Bir donadan foyda</th>
                    <th class="px-4 py-3 text-right">Harakatlar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($books)): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">Bu hisob uchun hali kitoblar kiritilmagan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($books as $book): ?>
                        <?php $unitProfit = (float) $book['selling_price'] - (float) $book['buying_price']; ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars($book['title']) ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($book['author'] ?? '–') ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($book['category'] ?? '–') ?></td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-700"><?= number_format((int) $book['quantity']) ?></td>
                            <td class="px-4 py-3 text-right text-slate-600"><?= number_format((float) $book['buying_price'], 2, '.', ' ') ?> so'm</td>
                            <td class="px-4 py-3 text-right text-slate-600"><?= number_format((float) $book['selling_price'], 2, '.', ' ') ?> so'm</td>
                            <td class="px-4 py-3 text-right <?= $unitProfit >= 0 ? 'text-emerald-600' : 'text-red-600' ?>"><?= number_format($unitProfit, 2, '.', ' ') ?> so'm</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="/edit.php?id=<?= (int) $book['id'] ?>&account=<?= urlencode($account) ?>" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-blue-200 text-blue-600 hover:bg-blue-50">Tahrirlash</a>
                                    <form method="post" action="/actions/delete_book.php" onsubmit="return confirm('Ushbu kitobni o\'chirishni tasdiqlaysizmi?');">
                                        <input type="hidden" name="id" value="<?= (int) $book['id'] ?>" />
                                        <input type="hidden" name="account" value="<?= htmlspecialchars($account) ?>" />
                                        <button type="submit" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-red-200 text-red-600 hover:bg-red-50">O'chirish</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
