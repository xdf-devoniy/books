<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/db.php';

if (!isset($_SESSION['logged_in'])) {
    header('Location: index.php');
    exit;
}

$db = get_db();
$books = fetch_books($db);
$booksById = [];
foreach ($books as $book) {
    $booksById[(int) $book['id']] = $book;
}

$saleForm = $_SESSION['sale_form'] ?? [];
unset($_SESSION['sale_form']);
$selectedId = isset($saleForm['book']) ? (int) $saleForm['book'] : 0;
if ($selectedId <= 0 && isset($_GET['book'])) {
    $selectedId = (int) $_GET['book'];
}
$selectedBook = $booksById[$selectedId] ?? null;

$pageTitle = 'Record a sale';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/flash.php';
?>
<section class="grid grid-cols-1 gap-6 lg:grid-cols-[2fr,1fr]">
    <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-xl shadow-slate-200/60">
        <h1 class="text-3xl font-semibold text-slate-900">Record a sale</h1>
        <p class="mt-2 text-sm text-slate-500">Log sold copies to keep the inventory numbers accurate.</p>
        <form action="actions/record_sale.php" method="POST" class="mt-8 space-y-6">
            <div>
                <label for="book" class="mb-2 block text-sm font-medium text-slate-600">Book <span class="text-rose-500">*</span></label>
                <select id="book" name="book" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-brand focus:ring focus:ring-brand/20">
                    <option value="">Select a title</option>
                    <?php foreach ($books as $book): ?>
                        <option value="<?php echo (int) $book['id']; ?>" <?php echo (int) ($saleForm['book'] ?? 0) === (int) $book['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($book['title']); ?> (<?php echo format_quantity((int) $book['quantity']); ?> in stock)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label for="quantity" class="mb-2 block text-sm font-medium text-slate-600">Quantity <span class="text-rose-500">*</span></label>
                    <input type="number" min="1" id="quantity" name="quantity" value="<?php echo htmlspecialchars($saleForm['quantity'] ?? ''); ?>" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-brand focus:ring focus:ring-brand/20" placeholder="3">
                </div>
                <div>
                    <label for="sale_type" class="mb-2 block text-sm font-medium text-slate-600">Sale type</label>
                    <input type="text" id="sale_type" name="sale_type" value="<?php echo htmlspecialchars($saleForm['sale_type'] ?? 'Retail'); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-brand focus:ring focus:ring-brand/20">
                </div>
            </div>
            <?php if ($selectedBook): ?>
                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 text-sm text-slate-600">
                    <p class="font-medium text-slate-500">Selected book overview</p>
                    <ul class="mt-2 space-y-1">
                        <li><span class="text-slate-500">Title:</span> <strong class="text-slate-900"><?php echo htmlspecialchars($selectedBook['title']); ?></strong></li>
                        <li><span class="text-slate-500">Available:</span> <strong class="text-slate-900"><?php echo format_quantity((int) $selectedBook['quantity']); ?></strong></li>
                        <li><span class="text-slate-500">Price:</span> <strong class="text-slate-900">UZS <?php echo format_currency((float) $selectedBook['price']); ?></strong></li>
                        <li><span class="text-slate-500">Inventory value:</span> <strong class="text-slate-900">UZS <?php echo format_currency((float) $selectedBook['inventory_value']); ?></strong></li>
                    </ul>
                </div>
            <?php endif; ?>
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <a href="index.php" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600 transition hover:border-brand/60 hover:text-brand">Back to dashboard</a>
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-brand px-6 py-3 text-sm font-semibold text-white transition hover:bg-brandDark focus:outline-none focus:ring-2 focus:ring-brand/40">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="mr-2 h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    Record sale
                </button>
            </div>
        </form>
    </div>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-200/60">
            <h2 class="text-xl font-semibold text-slate-900">Inventory snapshot</h2>
            <p class="mt-2 text-sm text-slate-500">Top titles and current availability.</p>
            <div class="mt-4 max-h-80 overflow-y-auto">
                <ul class="space-y-3 text-sm text-slate-600">
                    <?php if ($books === []): ?>
                        <li class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-center">No books in stock yet.</li>
                    <?php endif; ?>
                    <?php foreach ($books as $book): ?>
                        <li class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-medium text-slate-900"><?php echo htmlspecialchars($book['title']); ?></p>
                                    <p class="text-xs text-slate-500">UZS <?php echo format_currency((float) $book['price']); ?></p>
                                </div>
                                <span class="rounded-full bg-brand/10 px-3 py-1 text-xs font-semibold text-brand"><?php echo format_quantity((int) $book['quantity']); ?> left</span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
