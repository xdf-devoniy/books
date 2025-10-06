<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/db.php';

if (!isset($_SESSION['logged_in'])) {
    header('Location: index.php');
    exit;
}

$db = get_db();
$categories = fetch_categories($db);
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

$pageTitle = 'Add a new book';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/flash.php';
?>
<section class="max-w-3xl">
    <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-xl shadow-slate-200/60">
        <h1 class="text-3xl font-semibold text-slate-900">Register a new book</h1>
        <p class="mt-2 text-sm text-slate-500">Capture rich details so the team can find and track titles quickly.</p>
        <form action="actions/create_book.php" method="POST" class="mt-8 space-y-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="title" class="mb-2 block text-sm font-medium text-slate-600">Title <span class="text-rose-500">*</span></label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($formData['title'] ?? ''); ?>" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-brand focus:ring focus:ring-brand/20" placeholder="The Great Harvard Reader">
                </div>
                <div>
                    <label for="author" class="mb-2 block text-sm font-medium text-slate-600">Author</label>
                    <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($formData['author'] ?? ''); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-brand focus:ring focus:ring-brand/20" placeholder="Jane Doe">
                </div>
                <div>
                    <label for="category" class="mb-2 block text-sm font-medium text-slate-600">Category</label>
                    <input list="category-suggestions" id="category" name="category" value="<?php echo htmlspecialchars($formData['category'] ?? ''); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-brand focus:ring focus:ring-brand/20" placeholder="Science, Fiction, ...">
                    <datalist id="category-suggestions">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div>
                    <label for="price" class="mb-2 block text-sm font-medium text-slate-600">Unit price (UZS) <span class="text-rose-500">*</span></label>
                    <input type="number" min="0" step="0.01" id="price" name="price" value="<?php echo htmlspecialchars($formData['price'] ?? ''); ?>" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-brand focus:ring focus:ring-brand/20" placeholder="150000">
                </div>
                <div>
                    <label for="quantity" class="mb-2 block text-sm font-medium text-slate-600">Quantity <span class="text-rose-500">*</span></label>
                    <input type="number" min="0" id="quantity" name="quantity" value="<?php echo htmlspecialchars($formData['quantity'] ?? ''); ?>" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-brand focus:ring focus:ring-brand/20" placeholder="25">
                </div>
            </div>
            <div>
                <label for="description" class="mb-2 block text-sm font-medium text-slate-600">Short description</label>
                <textarea id="description" name="description" rows="4" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-brand focus:ring focus:ring-brand/20" placeholder="What makes this book special?"><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
            </div>
            <div class="flex flex-col-reverse gap-3 md:flex-row md:justify-between">
                <a href="index.php" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600 transition hover:border-brand/60 hover:text-brand">Back to dashboard</a>
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-brand px-6 py-3 text-sm font-semibold text-white transition hover:bg-brandDark focus:outline-none focus:ring-2 focus:ring-brand/40">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="mr-2 h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Save book
                </button>
            </div>
        </form>
    </div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
