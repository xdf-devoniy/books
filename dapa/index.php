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
                background: radial-gradient(circle at 20% 20%, rgba(79, 70, 229, 0.35), transparent 45%),
                    radial-gradient(circle at 80% 0%, rgba(236, 72, 153, 0.3), transparent 55%), #020617;
                min-height: 100vh;
            }
        </style>
    </head>
    <body class="flex items-center justify-center px-6 py-20">
    <div class="w-full max-w-md rounded-3xl border border-white/10 bg-slate-900/80 p-8 shadow-2xl shadow-brand/20 backdrop-blur">
        <div class="mb-6 flex flex-col items-center gap-3 text-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-brand/20 text-brand">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-8 w-8">
                    <path d="M4.75 5A2.75 2.75 0 0 1 7.5 2.25h9a2.75 2.75 0 0 1 2.75 2.75v14.06a.94.94 0 0 1-1.49.78l-4.56-3.04a1 1 0 0 0-1.1 0l-4.56 3.04a.94.94 0 0 1-1.49-.78z" />
                </svg>
            </div>
            <h1 class="text-2xl font-semibold text-white">Library control center</h1>
            <p class="text-sm text-slate-300">Enter the access key to continue.</p>
        </div>
        <?php if (!empty($_SESSION['flash']) && ($_SESSION['flash']['type'] ?? '') === 'error'): ?>
            <div class="mb-6 rounded-2xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                <?php echo htmlspecialchars($_SESSION['flash']['message']); ?>
            </div>
        <?php unset($_SESSION['flash']); endif; ?>
        <form action="" method="POST" class="space-y-5">
            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-slate-200">Password</label>
                <input type="password" id="password" name="password" required class="w-full rounded-2xl border border-slate-700 bg-slate-900/60 px-4 py-3 text-white outline-none transition focus:border-brand focus:ring focus:ring-brand/30" placeholder="Enter secure code">
            </div>
            <button type="submit" class="w-full rounded-2xl bg-brand px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-brandDark focus:outline-none focus:ring-2 focus:ring-brand/50">Unlock dashboard</button>
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
    <div class="card-glow rounded-3xl border border-white/10 bg-slate-900/60 p-6">
        <p class="text-xs uppercase tracking-wide text-slate-400">Unique titles</p>
        <p class="mt-3 text-3xl font-semibold text-white"><?php echo format_quantity($metrics['title_count']); ?></p>
        <p class="mt-1 text-xs text-slate-400">Books currently tracked in the system.</p>
    </div>
    <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
        <p class="text-xs uppercase tracking-wide text-slate-400">Copies on shelves</p>
        <p class="mt-3 text-3xl font-semibold text-white"><?php echo format_quantity($metrics['total_quantity']); ?></p>
        <p class="mt-1 text-xs text-slate-400">Total physical inventory in stock.</p>
    </div>
    <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
        <p class="text-xs uppercase tracking-wide text-slate-400">Inventory value</p>
        <p class="mt-3 text-3xl font-semibold text-white">UZS <?php echo format_currency($metrics['total_value']); ?></p>
        <p class="mt-1 text-xs text-slate-400">Estimated based on unit price × quantity.</p>
    </div>
    <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-6">
        <p class="text-xs uppercase tracking-wide text-slate-400">Average price</p>
        <p class="mt-3 text-3xl font-semibold text-white">UZS <?php echo format_currency($metrics['average_price']); ?></p>
        <p class="mt-1 text-xs text-slate-400">Mean cover price for all titles.</p>
    </div>
</section>

<section class="sep">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-white">Collection overview</h2>
            <?php if (!empty($metrics['last_updated'])): ?>
                <p class="text-sm text-slate-400">Last update: <?php echo htmlspecialchars(date('M j, Y H:i', strtotime($metrics['last_updated']))); ?></p>
            <?php endif; ?>
        </div>
        <a href="add.php" class="inline-flex items-center justify-center rounded-2xl bg-brand px-5 py-3 text-sm font-semibold text-white transition hover:bg-brandDark focus:outline-none focus:ring-2 focus:ring-brand/40">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="mr-2 h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add new book
        </a>
    </div>
    <form method="GET" class="mt-6 grid grid-cols-1 gap-4 rounded-3xl border border-white/10 bg-slate-900/60 p-6 shadow-lg shadow-black/20 md:grid-cols-4">
        <div class="md:col-span-2">
            <label for="search" class="block text-xs font-medium uppercase tracking-wide text-slate-400">Search</label>
            <div class="mt-2 flex items-center gap-3 rounded-2xl border border-slate-700 bg-slate-950/60 px-4">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5 text-slate-500">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0z" />
                </svg>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by title, author, or category" class="w-full bg-transparent py-3 text-sm text-white placeholder:text-slate-500 focus:outline-none">
            </div>
        </div>
        <div>
            <label for="category" class="block text-xs font-medium uppercase tracking-wide text-slate-400">Category</label>
            <select id="category" name="category" class="mt-2 w-full rounded-2xl border border-slate-700 bg-slate-950/60 px-4 py-3 text-sm text-white focus:border-brand focus:outline-none">
                <option value="">All categories</option>
                <?php foreach ($categories as $option): ?>
                    <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $option === $category ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="sort" class="block text-xs font-medium uppercase tracking-wide text-slate-400">Sort by</label>
            <select id="sort" name="sort" class="mt-2 w-full rounded-2xl border border-slate-700 bg-slate-950/60 px-4 py-3 text-sm text-white focus:border-brand focus:outline-none">
                <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Title (A-Z)</option>
                <option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Last updated</option>
                <option value="quantity" <?php echo $sort === 'quantity' ? 'selected' : ''; ?>>Quantity (high to low)</option>
                <option value="value" <?php echo $sort === 'value' ? 'selected' : ''; ?>>Inventory value</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full rounded-2xl bg-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-brand/30">Apply filters</button>
        </div>
    </form>
</section>

<section class="sep">
    <div class="overflow-hidden rounded-3xl border border-white/10 bg-slate-900/70 shadow-2xl shadow-black/20">
        <div class="table-gradient overflow-x-auto">
            <table class="min-w-full divide-y divide-white/5 text-sm">
                <thead class="bg-white/5 text-left text-xs font-semibold uppercase tracking-wide text-slate-300">
                    <tr>
                        <th class="px-6 py-4">Title</th>
                        <th class="px-6 py-4">Author</th>
                        <th class="px-6 py-4">Category</th>
                        <th class="px-6 py-4 text-right">Price</th>
                        <th class="px-6 py-4 text-right">Quantity</th>
                        <th class="px-6 py-4 text-right">Inventory value</th>
                        <th class="px-6 py-4 text-right">Updated</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-100">
                <?php if ($books === []): ?>
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-sm text-slate-400">No books found. Try adjusting your filters or <a href="add.php" class="text-brandLight underline">add a new title</a>.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($books as $book): ?>
                    <tr class="transition hover:bg-white/5">
                        <td class="px-6 py-4">
                            <div class="font-semibold text-white"><?php echo htmlspecialchars($book['title']); ?></div>
                            <?php if (!empty($book['description'])): ?>
                                <p class="mt-1 text-xs text-slate-400 line-clamp-2"><?php echo htmlspecialchars($book['description']); ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-slate-300"><?php echo $book['author'] ? htmlspecialchars($book['author']) : '<span class="badge bg-white/10 text-white/80">Unknown</span>'; ?></td>
                        <td class="px-6 py-4">
                            <span class="badge bg-brand/10 text-brandLight"><?php echo htmlspecialchars($book['category'] ?: 'Uncategorized'); ?></span>
                        </td>
                        <td class="px-6 py-4 text-right">UZS <?php echo format_currency((float) $book['price']); ?></td>
                        <td class="px-6 py-4 text-right"><?php echo format_quantity((int) $book['quantity']); ?></td>
                        <td class="px-6 py-4 text-right">UZS <?php echo format_currency((float) $book['inventory_value']); ?></td>
                        <td class="px-6 py-4 text-right text-xs text-slate-400"><?php echo htmlspecialchars(date('M j, Y', strtotime((string) $book['updated_at']))); ?></td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="edit.php?id=<?php echo (int) $book['id']; ?>" class="inline-flex items-center rounded-full bg-white/10 px-3 py-2 text-xs font-semibold text-white transition hover:bg-brand/30">Edit</a>
                                <form action="actions/delete_book.php" method="POST" class="inline" data-confirm="Delete this book? This action cannot be undone.">
                                    <input type="hidden" name="id" value="<?php echo (int) $book['id']; ?>">
                                    <button type="submit" class="inline-flex items-center rounded-full bg-rose-500/20 px-3 py-2 text-xs font-semibold text-rose-200 transition hover:bg-rose-500/30">Delete</button>
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
