<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/db.php';

if (!isset($_SESSION['logged_in'])) {
    header('Location: index.php');
    exit;
}

$db = get_db();
$metrics = fetch_inventory_metrics($db);
$categories = fetch_category_breakdown($db);
$recent = fetch_recent_books($db, 6);

$pageTitle = 'Inventory insights';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/flash.php';
?>
<section class="grid grid-cols-1 gap-6 md:grid-cols-2">
    <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-black/30">
        <p class="text-xs uppercase tracking-wide text-slate-400">Total inventory value</p>
        <p class="mt-3 text-3xl font-semibold text-white">UZS <?php echo format_currency($metrics['total_value']); ?></p>
        <p class="mt-1 text-xs text-slate-400">Across <?php echo format_quantity($metrics['title_count']); ?> registered titles.</p>
    </div>
    <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-black/30">
        <p class="text-xs uppercase tracking-wide text-slate-400">Average price per book</p>
        <p class="mt-3 text-3xl font-semibold text-white">UZS <?php echo format_currency($metrics['average_price']); ?></p>
        <p class="mt-1 text-xs text-slate-400">A quick indicator of the catalogue pricing.</p>
    </div>
</section>

<section class="sep grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="xl:col-span-2 space-y-6">
        <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-black/30">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-white">Category breakdown</h2>
                <span class="text-xs uppercase tracking-wide text-slate-400">Sorted by inventory value</span>
            </div>
            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-white/5 text-sm">
                    <thead class="bg-white/5 text-left text-xs font-semibold uppercase tracking-wide text-slate-300">
                        <tr>
                            <th class="px-6 py-4">Category</th>
                            <th class="px-6 py-4">Titles</th>
                            <th class="px-6 py-4">Quantity</th>
                            <th class="px-6 py-4">Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-slate-100">
                    <?php if ($categories === []): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-400">Add books to see insights by category.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($categories as $category): ?>
                        <tr class="transition hover:bg-white/5">
                            <td class="px-6 py-4 font-semibold text-white"><?php echo htmlspecialchars($category['category']); ?></td>
                            <td class="px-6 py-4"><?php echo format_quantity((int) $category['title_count']); ?></td>
                            <td class="px-6 py-4"><?php echo format_quantity((int) $category['total_quantity']); ?></td>
                            <td class="px-6 py-4">UZS <?php echo format_currency((float) $category['total_value']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-black/30">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-white">Recent updates</h2>
                <span class="text-xs uppercase tracking-wide text-slate-400">Last refreshed catalogue entries</span>
            </div>
            <?php if ($recent === []): ?>
                <p class="mt-6 text-sm text-slate-400">No recent updates yet. Start by adding books.</p>
            <?php else: ?>
                <ul class="mt-6 space-y-4">
                    <?php foreach ($recent as $book): ?>
                        <li class="flex flex-col gap-1 rounded-2xl bg-white/5 px-4 py-4">
                            <div class="flex items-center justify-between">
                                <div class="font-semibold text-white"><?php echo htmlspecialchars($book['title']); ?></div>
                                <span class="text-xs text-slate-400"><?php echo htmlspecialchars(date('M j, Y H:i', strtotime((string) $book['updated_at']))); ?></span>
                            </div>
                            <div class="flex flex-wrap gap-3 text-xs text-slate-300">
                                <?php if (!empty($book['author'])): ?>
                                    <span>Author: <strong class="text-white/80"><?php echo htmlspecialchars($book['author']); ?></strong></span>
                                <?php endif; ?>
                                <span>Category: <strong class="text-brandLight"><?php echo htmlspecialchars($book['category'] ?: 'Uncategorized'); ?></strong></span>
                                <span>Quantity: <strong class="text-white/80"><?php echo format_quantity((int) $book['quantity']); ?></strong></span>
                                <span>Value: <strong class="text-white/80">UZS <?php echo format_currency((float) $book['inventory_value']); ?></strong></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <div class="space-y-6">
        <div class="rounded-3xl border border-white/10 bg-gradient-to-br from-brand/20 via-slate-900 to-transparent p-6 shadow-2xl shadow-black/30">
            <h2 class="text-xl font-semibold text-white">Quick tips</h2>
            <ul class="mt-4 space-y-3 text-sm text-slate-200">
                <li class="flex items-start gap-3">
                    <span class="mt-0.5 h-2 w-2 rounded-full bg-brand"></span>
                    Keep descriptions up to date so teachers can recommend titles easily.
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-0.5 h-2 w-2 rounded-full bg-rose-400"></span>
                    Review low-stock categories to schedule the next restock.
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-0.5 h-2 w-2 rounded-full bg-sky-400"></span>
                    Use categories consistently to unlock richer analytics.
                </li>
            </ul>
        </div>
        <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-black/30">
            <h2 class="text-xl font-semibold text-white">Snapshot</h2>
            <dl class="mt-4 space-y-3 text-sm text-slate-300">
                <div class="flex items-center justify-between">
                    <dt>Total titles</dt>
                    <dd class="font-semibold text-white"><?php echo format_quantity($metrics['title_count']); ?></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt>Total copies</dt>
                    <dd class="font-semibold text-white"><?php echo format_quantity($metrics['total_quantity']); ?></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt>Inventory value</dt>
                    <dd class="font-semibold text-white">UZS <?php echo format_currency($metrics['total_value']); ?></dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt>Average price</dt>
                    <dd class="font-semibold text-white">UZS <?php echo format_currency($metrics['average_price']); ?></dd>
                </div>
                <?php if (!empty($metrics['last_updated'])): ?>
                    <div class="flex items-center justify-between">
                        <dt>Last updated</dt>
                        <dd class="font-semibold text-white"><?php echo htmlspecialchars(date('M j, Y H:i', strtotime($metrics['last_updated']))); ?></dd>
                    </div>
                <?php endif; ?>
            </dl>
        </div>
    </div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
