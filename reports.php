<?php
require_once __DIR__ . '/db.php';

$account = current_account();
$metrics = fetch_dashboard_metrics($account);
$daily = fetch_profit_by_day($account, 30);

$pdo = db();
$booksReport = [];
$reportStmt = $pdo->prepare('SELECT book_title, SUM(quantity) AS total_qty, SUM(unit_sell_price * quantity) AS revenue, SUM(unit_buy_price * quantity) AS cost, SUM((unit_sell_price - unit_buy_price) * quantity) AS profit FROM sales WHERE account = ? GROUP BY book_title ORDER BY profit DESC');
$reportStmt->execute([$account]);
$booksReport = $reportStmt->fetchAll();

$pageTitle = "Hisobotlar";
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/flash.php';
?>
<section class="grid gap-6 md:grid-cols-2">
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-800">Umumiy daromad</h2>
        <p class="text-sm text-slate-500 mt-1">Jami tushum va foydani ko'ring.</p>
        <div class="mt-6 grid gap-4">
            <div>
                <p class="text-xs uppercase text-slate-400">Tushum</p>
                <p class="text-2xl font-semibold text-blue-600"><?= number_format($metrics['total_revenue'], 2, '.', ' ') ?> so'm</p>
            </div>
            <div>
                <p class="text-xs uppercase text-slate-400">Foyda</p>
                <p class="text-2xl font-semibold text-emerald-600"><?= number_format($metrics['total_profit'], 2, '.', ' ') ?> so'm</p>
            </div>
        </div>
    </div>
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-800">Ombor qiymati</h2>
        <p class="text-sm text-slate-500 mt-1">Mavjud kitoblar bo'yicha tannarx va sotuv qiymati.</p>
        <dl class="mt-6 grid gap-4">
            <div>
                <dt class="text-xs uppercase text-slate-400">Tannarx</dt>
                <dd class="text-2xl font-semibold text-slate-700"><?= number_format($metrics['stock_cost'], 2, '.', ' ') ?> so'm</dd>
            </div>
            <div>
                <dt class="text-xs uppercase text-slate-400">Potensial tushum</dt>
                <dd class="text-2xl font-semibold text-slate-700"><?= number_format($metrics['stock_value'], 2, '.', ' ') ?> so'm</dd>
            </div>
        </dl>
    </div>
</section>

<section class="mt-10 bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
    <h2 class="text-lg font-semibold text-slate-800 mb-4">Kunlik foyda (oxirgi 30 kun)</h2>
    <?php if (empty($daily)): ?>
        <p class="text-sm text-slate-500">Hozircha sotuvlar mavjud emas.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Sana</th>
                        <th class="px-4 py-3 text-right">Tushum</th>
                        <th class="px-4 py-3 text-right">Foyda</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($daily as $row): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-700"><?= date('d.m.Y', strtotime($row['day'])) ?></td>
                            <td class="px-4 py-3 text-right text-blue-600"><?= number_format((float) $row['revenue'], 2, '.', ' ') ?> so'm</td>
                            <td class="px-4 py-3 text-right text-emerald-600"><?= number_format((float) $row['profit'], 2, '.', ' ') ?> so'm</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="mt-10 bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Kitoblar bo'yicha sotuv natijalari</h2>
            <p class="text-sm text-slate-500">Qaysi kitob qancha foyda keltirganini ko'ring.</p>
        </div>
    </div>
    <?php if (empty($booksReport)): ?>
        <p class="text-sm text-slate-500">Hisobotlar uchun sotuvlar yetarli emas.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Kitob</th>
                        <th class="px-4 py-3 text-right">Sotilgan dona</th>
                        <th class="px-4 py-3 text-right">Tushum</th>
                        <th class="px-4 py-3 text-right">Tannarx</th>
                        <th class="px-4 py-3 text-right">Foyda</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($booksReport as $row): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-700"><?= htmlspecialchars($row['book_title']) ?></td>
                            <td class="px-4 py-3 text-right text-slate-600"><?= number_format((int) $row['total_qty']) ?></td>
                            <td class="px-4 py-3 text-right text-blue-600"><?= number_format((float) $row['revenue'], 2, '.', ' ') ?> so'm</td>
                            <td class="px-4 py-3 text-right text-slate-500"><?= number_format((float) $row['cost'], 2, '.', ' ') ?> so'm</td>
                            <td class="px-4 py-3 text-right text-emerald-600 font-semibold"><?= number_format((float) $row['profit'], 2, '.', ' ') ?> so'm</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
