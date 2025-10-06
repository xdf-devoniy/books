<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/db.php';

if (!isset($_SESSION['logged_in'])) {
    header('Location: index.php');
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Missing book identifier.',
    ];
    header('Location: index.php');
    exit;
}

$db = get_db();
$book = fetch_book($db, $id);

if (!$book) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'The requested book could not be found.',
    ];
    header('Location: index.php');
    exit;
}

$categories = fetch_categories($db);
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

$defaults = array_merge($book, $formData);

$pageTitle = 'Update book';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/flash.php';
?>
<section class="max-w-3xl">
    <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-xl shadow-slate-200/60">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">Update book</h1>
                <p class="mt-1 text-sm text-slate-500">Keep the catalogue accurate and the shelves stocked.</p>
            </div>
            <span class="badge bg-brand/10 text-brand">ID #<?php echo (int) $book['id']; ?></span>
        </div>
        <dl class="mt-6 grid grid-cols-1 gap-4 rounded-2xl border border-slate-100 bg-slate-50 p-4 text-sm text-slate-600 md:grid-cols-3">
            <div>
                <dt class="font-medium text-slate-500">Current quantity</dt>
                <dd class="mt-1 text-lg font-semibold text-slate-900"><?php echo format_quantity((int) $book['quantity']); ?></dd>
            </div>
            <div>
                <dt class="font-medium text-slate-500">Inventory value</dt>
                <dd class="mt-1 text-lg font-semibold text-slate-900">UZS <?php echo format_currency((float) $book['inventory_value']); ?></dd>
            </div>
            <div>
                <dt class="font-medium text-slate-500">Last activity</dt>
                <dd class="mt-1 text-lg font-semibold text-slate-900"><?php echo !empty($book['last_activity']) ? htmlspecialchars(date('M j, Y', strtotime((string) $book['last_activity']))) : 'No activity yet'; ?></dd>
            </div>
        </dl>
        <form action="actions/update_book.php" method="POST" class="mt-8 space-y-6">
            <input type="hidden" name="id" value="<?php echo (int) $book['id']; ?>">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="title" class="mb-2 block text-sm font-medium text-slate-600">Title <span class="text-rose-500">*</span></label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($defaults['title'] ?? ''); ?>" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-brand focus:ring focus:ring-brand/20">
                </div>
                <div>
                    <label for="author" class="mb-2 block text-sm font-medium text-slate-600">Author</label>
                    <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($defaults['author'] ?? ''); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-brand focus:ring focus:ring-brand/20">
                </div>
                <div>
                    <label for="category" class="mb-2 block text-sm font-medium text-slate-600">Category</label>
                    <input list="category-suggestions" id="category" name="category" value="<?php echo htmlspecialchars($defaults['category'] ?? ''); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-brand focus:ring focus:ring-brand/20">
                    <datalist id="category-suggestions">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div>
                    <label for="price" class="mb-2 block text-sm font-medium text-slate-600">Unit price (UZS) <span class="text-rose-500">*</span></label>
                    <input type="number" min="0" step="0.01" id="price" name="price" value="<?php echo htmlspecialchars((string) ($defaults['price'] ?? '')); ?>" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-brand focus:ring focus:ring-brand/20">
                </div>
                <div>
                    <label for="quantity" class="mb-2 block text-sm font-medium text-slate-600">Quantity <span class="text-rose-500">*</span></label>
                    <input type="number" min="0" id="quantity" name="quantity" value="<?php echo htmlspecialchars((string) ($defaults['quantity'] ?? '')); ?>" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-brand focus:ring focus:ring-brand/20">
                </div>
            </div>
            <div>
                <label for="description" class="mb-2 block text-sm font-medium text-slate-600">Short description</label>
                <textarea id="description" name="description" rows="4" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-brand focus:ring focus:ring-brand/20"><?php echo htmlspecialchars($defaults['description'] ?? ''); ?></textarea>
            </div>
            <div class="flex flex-col-reverse gap-3 md:flex-row md:justify-between">
                <a href="index.php" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600 transition hover:border-brand/60 hover:text-brand">Cancel</a>
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-brand px-6 py-3 text-sm font-semibold text-white transition hover:bg-brandDark focus:outline-none focus:ring-2 focus:ring-brand/40">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="mr-2 h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    Save changes
                </button>
            </div>
        </form>
    </div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
