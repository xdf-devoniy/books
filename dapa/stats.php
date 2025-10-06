<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/db.php';

if (!isset($_SESSION['logged_in'])) {
    header('Location: index.php');
    exit;
}

$db = get_db();
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$metrics = fetch_inventory_metrics($db);
$categories = fetch_category_breakdown($db);
$recent = fetch_recent_books($db, 6);
$sales = fetch_sales_for_date($db, $selectedDate);
$salesSummary = summarize_sales($db, $selectedDate);

$pageTitle = 'Inventory reports';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/flash.php';
?>
<section class="grid grid-cols-1 gap-6 md:grid-cols-2">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60">
        <p class="text-xs uppercase tracking-wide text-slate-500">Total inventory value</p>
        <p class="mt-3 text-3xl font-semibold text-slate-900">UZS <?php echo format_currency($metrics['total_value']); ?></p>
        <p class="mt-1 text-xs text-slate-500">Across <?php echo format_quantity($metrics['title_count']); ?> registered titles.</p>
    </div>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60">
        <p class="text-xs uppercase tracking-wide text-slate-500">Average price per book</p>
        <p class="mt-3 text-3xl font-semibold text-slate-900">UZS <?php echo format_currency($metrics['average_price']); ?></p>
        <p class="mt-1 text-xs text-slate-500">A quick indicator of the catalogue pricing.</p>
    </div>
</section>

<section class="sep grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Sales on <?php echo htmlspecialchars(date('M j, Y', strtotime($selectedDate))); ?></h2>
                    <p class="text-sm text-slate-500">Track the quantity and value of books sold for the selected date.</p>
                </div>
                <form method="GET" class="flex items-center gap-3">
                    <label for="date" class="text-sm font-medium text-slate-500">Choose date</label>
                    <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($selectedDate); ?>" class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-brand focus:ring focus:ring-brand/20">
                    <button type="submit" class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-brand/60 hover:text-brand">Filter</button>
                </form>
            </div>
            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3 text-right">Quantity</th>
                            <th class="px-4 py-3">Sale type</th>
                            <th class="px-4 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if ($sales === []): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">No sales recorded for this date.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sales as $sale): ?>
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-900"><?php echo htmlspecialchars($sale['title']); ?></td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-900"><?php echo format_quantity((int) $sale['quantity']); ?></td>
                                <td class="px-4 py-3 text-slate-600"><?php echo $sale['sale_type'] !== null ? htmlspecialchars($sale['sale_type']) : '—'; ?></td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-900">UZS <?php echo format_currency((float) $sale['total_value']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-6 grid grid-cols-1 gap-4 rounded-2xl border border-slate-100 bg-slate-50 p-4 text-sm text-slate-600 md:grid-cols-2">
                <div>
                    <p class="text-slate-500">Total sold copies</p>
                    <p class="mt-1 text-xl font-semibold text-slate-900"><?php echo format_quantity((int) $salesSummary['quantity']); ?></p>
                </div>
                <div>
                    <p class="text-slate-500">Revenue for the day</p>
                    <p class="mt-1 text-xl font-semibold text-slate-900">UZS <?php echo format_currency((float) $salesSummary['value']); ?></p>
                </div>
            </div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold text-slate-900">Category breakdown</h2>
                <span class="text-xs uppercase tracking-wide text-slate-500">Sorted by inventory value</span>
            </div>
            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Titles</th>
                            <th class="px-4 py-3">Quantity</th>
                            <th class="px-4 py-3">Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if ($categories === []): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">Add books to see insights by category.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-900"><?php echo htmlspecialchars($category['category']); ?></td>
                                <td class="px-4 py-3"><?php echo format_quantity((int) $category['title_count']); ?></td>
                                <td class="px-4 py-3"><?php echo format_quantity((int) $category['total_quantity']); ?></td>
                                <td class="px-4 py-3">UZS <?php echo format_currency((float) $category['total_value']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60">
            <h2 class="text-xl font-semibold text-slate-900">Snapshot</h2>
            <dl class="mt-4 space-y-3 text-sm text-slate-600">
                <div class="flex items-center justify-between">
                    <dt>Total titles</dt>
                    <dd class="font-semibold text-slate-900"><?php echo format_quantity($metrics['title_count']); ?></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt>Total copies</dt>
                    <dd class="font-semibold text-slate-900"><?php echo format_quantity($metrics['total_quantity']); ?></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt>Inventory value</dt>
                    <dd class="font-semibold text-slate-900">UZS <?php echo format_currency($metrics['total_value']); ?></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt>Average price</dt>
                    <dd class="font-semibold text-slate-900">UZS <?php echo format_currency($metrics['average_price']); ?></dd>
                </div>
                <?php if (!empty($metrics['last_updated'])): ?>
                    <div class="flex items-center justify-between">
                        <dt>Last activity</dt>
                        <dd class="font-semibold text-slate-900"><?php echo htmlspecialchars(date('M j, Y', strtotime((string) $metrics['last_updated']))); ?></dd>
                    </div>
                <?php endif; ?>
            </dl>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-slate-900">Recent updates</h2>
                <span class="text-xs uppercase tracking-wide text-slate-500">Last refreshed catalogue entries</span>
            </div>
            <?php if ($recent === []): ?>
                <p class="mt-6 text-sm text-slate-500">No recent updates yet. Start by adding books.</p>
            <?php else: ?>
                <ul class="mt-6 space-y-4">
                    <?php foreach ($recent as $book): ?>
                        <li class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4">
                            <div class="flex items-center justify-between">
                                <div class="font-semibold text-slate-900"><?php echo htmlspecialchars($book['title']); ?></div>
                                <span class="text-xs text-slate-500"><?php echo $book['last_activity'] ? htmlspecialchars(date('M j, Y', strtotime((string) $book['last_activity']))) : '—'; ?></span>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-3 text-xs text-slate-600">
                                <?php if (!empty($book['author'])): ?>
                                    <span>Author: <strong class="text-slate-800"><?php echo htmlspecialchars($book['author']); ?></strong></span>
                                <?php endif; ?>
                                <span>Quantity: <strong class="text-slate-800"><?php echo format_quantity((int) $book['quantity']); ?></strong></span>
                                <span>Value: <strong class="text-slate-800">UZS <?php echo format_currency((float) $book['inventory_value']); ?></strong></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
