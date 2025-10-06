<?php
require_once __DIR__ . '/db.php';

$account = current_account();
$metrics = fetch_dashboard_metrics($account);
$books = fetch_books($account);
$recentSales = fetch_recent_sales($account, 5);

$pageTitle = "Asosiy boshqaruv";
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/flash.php';
?>
<section class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <p class="text-sm text-slate-500">Jami kitob turlari</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900"><?= number_format($metrics['total_books']) ?></p>
    </div>
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <p class="text-sm text-slate-500">Ombordagi jami dona</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900"><?= number_format($metrics['total_quantity']) ?></p>
    </div>
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <p class="text-sm text-slate-500">Ombor qiymati (tannarx)</p>
        <p class="mt-2 text-3xl font-semibold text-emerald-600"><?= number_format($metrics['stock_cost'], 2, '.', ' ') ?> so'm</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <p class="text-sm text-slate-500">Potensial savdo qiymati</p>
        <p class="mt-2 text-3xl font-semibold text-blue-600"><?= number_format($metrics['stock_value'], 2, '.', ' ') ?> so'm</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <p class="text-sm text-slate-500">Umumiy daromad</p>
        <p class="mt-2 text-3xl font-semibold text-indigo-600"><?= number_format($metrics['total_revenue'], 2, '.', ' ') ?> so'm</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <p class="text-sm text-slate-500">Yig'ilgan foyda</p>
        <p class="mt-2 text-3xl font-semibold text-emerald-600"><?= number_format($metrics['total_profit'], 2, '.', ' ') ?> so'm</p>
    </div>
</section>

<section class="mt-10 grid gap-8 lg:grid-cols-2">
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-800">So'nggi savdolar</h2>
            <a href="/reports.php?account=<?= urlencode($account) ?>" class="text-sm text-blue-600 hover:text-blue-700">Batafsil</a>
        </div>
        <?php if (empty($recentSales)): ?>
            <p class="text-sm text-slate-500">Hozircha savdo qayd etilmagan.</p>
        <?php else: ?>
            <ul class="space-y-3">
                <?php foreach ($recentSales as $sale): ?>
                    <li class="flex items-center justify-between border border-slate-200 rounded-xl px-4 py-3">
                        <div>
                            <p class="font-medium text-slate-700"><?= htmlspecialchars($sale['book_title']) ?></p>
                            <p class="text-xs text-slate-500"><?= date('d.m.Y H:i', strtotime($sale['sold_at'])) ?> • <?= number_format($sale['quantity']) ?> dona</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-slate-600">Tannarx: <?= number_format($sale['unit_buy_price'], 2, '.', ' ') ?> so'm</p>
                            <p class="text-sm text-emerald-600 font-semibold">Sotuv: <?= number_format($sale['unit_sell_price'], 2, '.', ' ') ?> so'm</p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-slate-800">Ombordagi eng kam kitoblar</h2>
            <a href="/books.php?account=<?= urlencode($account) ?>" class="text-sm text-blue-600 hover:text-blue-700">Kitoblar ro'yxati</a>
        </div>
        <?php
        $sortedBooks = $books;
        usort($sortedBooks, static function ($a, $b) {
            return $a['quantity'] <=> $b['quantity'];
        });
        $topLow = array_slice($sortedBooks, 0, 5);
        ?>
        <?php if (empty($topLow)): ?>
            <p class="text-sm text-slate-500">Kitoblar hali qo'shilmagan.</p>
        <?php else: ?>
            <ul class="space-y-3">
                <?php foreach ($topLow as $book): ?>
                    <li class="border border-slate-200 rounded-xl px-4 py-3 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-slate-700"><?= htmlspecialchars($book['title']) ?></p>
                            <p class="text-xs text-slate-500">Kategoriya: <?= htmlspecialchars($book['category'] ?? 'Noma’lum') ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-amber-600"><?= number_format($book['quantity']) ?> dona</p>
                            <p class="text-xs text-slate-400">Sotuv narxi: <?= number_format($book['selling_price'], 2, '.', ' ') ?> so'm</p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
