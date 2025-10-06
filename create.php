<?php
require_once __DIR__ . '/db.php';

$account = current_account();
$pageTitle = "Yangi kitob qo'shish";
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/flash.php';
?>
<div class="max-w-3xl mx-auto bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
    <h2 class="text-xl font-semibold text-slate-800 mb-6">Kitob ma'lumotlari</h2>
    <form method="post" action="/actions/create_book.php" class="space-y-6">
        <input type="hidden" name="account" value="<?= htmlspecialchars($account) ?>" />
        <div class="grid gap-6 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-600 mb-2" for="title">Kitob nomi</label>
                <input id="title" name="title" type="text" required class="w-full rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-blue-500 px-4 py-2.5" placeholder="Masalan, Alkimyogar" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-2" for="author">Muallif</label>
                <input id="author" name="author" type="text" class="w-full rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-blue-500 px-4 py-2.5" placeholder="Paulo Koelo" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-2" for="category">Kategoriya</label>
                <input id="category" name="category" type="text" class="w-full rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-blue-500 px-4 py-2.5" placeholder="Badiiy adabiyot" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-2" for="quantity">Boshlang'ich miqdor</label>
                <input id="quantity" name="quantity" type="number" min="0" required class="w-full rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-blue-500 px-4 py-2.5" value="0" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-2" for="buying_price">Sotib olish narxi (1 dona)</label>
                <input id="buying_price" name="buying_price" type="number" min="0" step="0.01" required class="w-full rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-blue-500 px-4 py-2.5" placeholder="40000" />
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-2" for="selling_price">Sotish narxi (1 dona)</label>
                <input id="selling_price" name="selling_price" type="number" min="0" step="0.01" required class="w-full rounded-lg border border-slate-300 focus:border-blue-500 focus:ring-blue-500 px-4 py-2.5" placeholder="60000" />
            </div>
        </div>
        <div class="flex items-center justify-end gap-3">
            <a href="/books.php?account=<?= urlencode($account) ?>" class="px-4 py-2 rounded-lg border border-slate-200 text-sm font-medium text-slate-600 hover:text-slate-800">Bekor qilish</a>
            <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold">Saqlash</button>
        </div>
    </form>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
