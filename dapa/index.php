<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/db.php';

$correct_password = 'iyul0624';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if (isset($_POST['password'])) {
    if (hash_equals($correct_password, $_POST['password'])) {
        $_SESSION['logged_in'] = true;
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Welcome back! You can now manage the library collection.',
        ];
        header('Location: index.php');
        exit;
    }

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Incorrect password. Please try again.',
    ];
}

if (!isset($_SESSION['logged_in'])) {
    $pageTitle = 'Secure access | Library';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($pageTitle); ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Inter', system-ui, sans-serif;
                background: radial-gradient(circle at 15% 20%, rgba(99,102,241,0.18), transparent 45%),
                    radial-gradient(circle at 85% 0%, rgba(56,189,248,0.15), transparent 55%),
                    #f8fafc;
                min-height: 100vh;
            }
        </style>
    </head>
    <body class="flex items-center justify-center px-6 py-20 text-slate-800">
    <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-xl shadow-slate-200/60">
        <div class="mb-6 flex flex-col items-center gap-3 text-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-brand/10 text-brand">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-8 w-8">
                    <path d="M4.75 5A2.75 2.75 0 0 1 7.5 2.25h9a2.75 2.75 0 0 1 2.75 2.75v14.06a.94.94 0 0 1-1.49.78l-4.56-3.04a1 1 0 0 0-1.1 0l-4.56 3.04a.94.94 0 0 1-1.49-.78z" />
                </svg>
            </div>
            <h1 class="text-2xl font-semibold text-slate-900">Library control center</h1>
            <p class="text-sm text-slate-500">Enter the access key to continue.</p>
        </div>
        <?php if (!empty($_SESSION['flash']) && ($_SESSION['flash']['type'] ?? '') === 'error'): ?>
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <?php echo htmlspecialchars($_SESSION['flash']['message']); ?>
            </div>
        <?php unset($_SESSION['flash']); endif; ?>
        <form action="" method="POST" class="space-y-5">
            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-slate-600">Password</label>
                <input type="password" id="password" name="password" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-brand focus:ring focus:ring-brand/20" placeholder="Enter secure code">
            </div>
            <button type="submit" class="w-full rounded-2xl bg-brand px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-brandDark focus:outline-none focus:ring-2 focus:ring-brand/40">Unlock dashboard</button>
        </form>
    </div>
    </body>
    </html>
    <?php
    exit;
}

$db = get_db();

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$sort = $_GET['sort'] ?? 'title';

$books = fetch_books($db, $search, $category, $sort);
$categories = fetch_categories($db);
$metrics = fetch_inventory_metrics($db);

$pageTitle = 'Library dashboard';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/flash.php';
?>
<section class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-200/60">
        <p class="text-xs uppercase tracking-wide text-slate-500">Unique titles</p>
        <p class="mt-3 text-3xl font-semibold text-slate-900"><?php echo format_quantity($metrics['title_count']); ?></p>
        <p class="mt-1 text-xs text-slate-500">Books currently tracked in the system.</p>
    </div>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-200/60">
        <p class="text-xs uppercase tracking-wide text-slate-500">Copies on shelves</p>
        <p class="mt-3 text-3xl font-semibold text-slate-900"><?php echo format_quantity($metrics['total_quantity']); ?></p>
        <p class="mt-1 text-xs text-slate-500">Total physical inventory in stock.</p>
    </div>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-200/60">
        <p class="text-xs uppercase tracking-wide text-slate-500">Inventory value</p>
        <p class="mt-3 text-3xl font-semibold text-slate-900">UZS <?php echo format_currency($metrics['total_value']); ?></p>
        <p class="mt-1 text-xs text-slate-500">Estimated based on unit price × quantity.</p>
    </div>
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-200/60">
        <p class="text-xs uppercase tracking-wide text-slate-500">Average price</p>
        <p class="mt-3 text-3xl font-semibold text-slate-900">UZS <?php echo format_currency($metrics['average_price']); ?></p>
        <p class="mt-1 text-xs text-slate-500">Mean cover price for all titles.</p>
    </div>
</section>

<section class="sep">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Collection overview</h2>
            <?php if (!empty($metrics['last_updated'])): ?>
                <p class="text-sm text-slate-500">Last activity: <?php echo htmlspecialchars(date('M j, Y', strtotime((string) $metrics['last_updated']))); ?></p>
            <?php endif; ?>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="sell.php" class="inline-flex items-center justify-center rounded-2xl border border-brand/40 px-5 py-3 text-sm font-semibold text-brand transition hover:bg-brand/10">Record sale</a>
            <a href="add.php" class="inline-flex items-center justify-center rounded-2xl bg-brand px-5 py-3 text-sm font-semibold text-white transition hover:bg-brandDark focus:outline-none focus:ring-2 focus:ring-brand/40">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="mr-2 h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add new book
            </a>
        </div>
    </div>
    <form method="GET" class="mt-6 grid grid-cols-1 gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-200/60 md:grid-cols-4">
        <div class="md:col-span-2">
            <label for="search" class="block text-xs font-medium uppercase tracking-wide text-slate-500">Search</label>
            <div class="mt-2 flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5 text-slate-400">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0z" />
                </svg>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by title<?php echo $categories !== [] ? ', category' : ''; ?>" class="w-full bg-transparent py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none">
            </div>
        </div>
        <div>
            <label for="category" class="block text-xs font-medium uppercase tracking-wide text-slate-500">Category</label>
            <select id="category" name="category" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-brand focus:outline-none">
                <option value="">All categories</option>
                <?php foreach ($categories as $option): ?>
                    <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $option === $category ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="sort" class="block text-xs font-medium uppercase tracking-wide text-slate-500">Sort by</label>
            <select id="sort" name="sort" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-brand focus:outline-none">
                <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Title (A-Z)</option>
                <option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Last activity</option>
                <option value="quantity" <?php echo $sort === 'quantity' ? 'selected' : ''; ?>>Quantity (high to low)</option>
                <option value="value" <?php echo $sort === 'value' ? 'selected' : ''; ?>>Inventory value</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-brand/60 hover:text-brand">Apply filters</button>
        </div>
    </form>
</section>

<section class="sep">
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-200/80">
        <div class="table-gradient overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-4">Title</th>
                        <th class="px-6 py-4">Price</th>
                        <th class="px-6 py-4 text-right">Quantity</th>
                        <th class="px-6 py-4 text-right">Inventory value</th>
                        <th class="px-6 py-4">Last activity</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                <?php if ($books === []): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">No books found. Try adjusting your filters or <a href="add.php" class="text-brand underline">add a new title</a>.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($books as $book): ?>
                    <tr class="transition hover:bg-slate-50/80">
                        <td class="px-6 py-4 align-top">
                            <div class="font-semibold text-slate-900"><?php echo htmlspecialchars($book['title']); ?></div>
                            <div class="mt-1 flex flex-wrap gap-2 text-xs text-slate-500">
                                <?php if (!empty($book['author'])): ?>
                                    <span>Author: <strong class="text-slate-700"><?php echo htmlspecialchars($book['author']); ?></strong></span>
                                <?php endif; ?>
                                <?php if (!empty($book['category'])): ?>
                                    <span>Category: <strong class="text-slate-700"><?php echo htmlspecialchars($book['category']); ?></strong></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($book['description'])): ?>
                                <p class="mt-2 text-xs text-slate-500 line-clamp-2"><?php echo htmlspecialchars($book['description']); ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 align-top text-slate-700">UZS <?php echo format_currency((float) $book['price']); ?></td>
                        <td class="px-6 py-4 align-top text-right font-semibold text-slate-900"><?php echo format_quantity((int) $book['quantity']); ?></td>
                        <td class="px-6 py-4 align-top text-right font-semibold text-slate-900">UZS <?php echo format_currency((float) $book['inventory_value']); ?></td>
                        <td class="px-6 py-4 align-top text-slate-600"><?php echo !empty($book['last_activity']) ? htmlspecialchars(date('M j, Y', strtotime((string) $book['last_activity']))) : 'No activity yet'; ?></td>
                        <td class="px-6 py-4 align-top text-right">
                            <div class="flex flex-wrap justify-end gap-2">
                                <a href="sell.php?book=<?php echo (int) $book['id']; ?>" class="inline-flex items-center justify-center rounded-full border border-brand/40 px-3 py-1 text-xs font-semibold text-brand transition hover:bg-brand/10">Sell</a>
                                <a href="edit.php?id=<?php echo (int) $book['id']; ?>" class="inline-flex items-center justify-center rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600 transition hover:border-brand/60 hover:text-brand">Edit</a>
                                <form action="actions/delete_book.php" method="POST" data-confirm="Delete this book?" class="inline-flex">
                                    <input type="hidden" name="id" value="<?php echo (int) $book['id']; ?>">
                                    <button type="submit" class="inline-flex items-center justify-center rounded-full border border-rose-200 px-3 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>
