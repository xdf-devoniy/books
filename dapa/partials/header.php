<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = $pageTitle ?? 'Book Inventory';
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#6366f1',
                        brandDark: '#4338ca',
                        brandLight: '#a5b4fc',
                    }
                }
            }
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="partials/style.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="min-h-full bg-slate-950 text-slate-100">
<header class="border-b border-slate-800 bg-slate-900/70 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
        <a href="index.php" class="flex items-center gap-3 text-lg font-semibold tracking-tight text-white">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-brand/20 text-brand">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6">
                    <path d="M4.75 5A2.75 2.75 0 0 1 7.5 2.25h9a2.75 2.75 0 0 1 2.75 2.75v14.06a.94.94 0 0 1-1.49.78l-4.56-3.04a1 1 0 0 0-1.1 0l-4.56 3.04a.94.94 0 0 1-1.49-.78z" />
                </svg>
            </span>
            <span>Harvard School Library</span>
        </a>
        <nav class="flex items-center gap-2 text-sm font-medium">
            <a href="index.php" class="rounded-full px-4 py-2 transition hover:bg-brand/10 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'bg-brand/10 text-white' : 'text-slate-300'; ?>">Dashboard</a>
            <a href="add.php" class="rounded-full px-4 py-2 transition hover:bg-brand/10 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) === 'add.php' ? 'bg-brand/10 text-white' : 'text-slate-300'; ?>">Add book</a>
            <a href="stats.php" class="rounded-full px-4 py-2 transition hover:bg-brand/10 hover:text-white <?php echo basename($_SERVER['PHP_SELF']) === 'stats.php' ? 'bg-brand/10 text-white' : 'text-slate-300'; ?>">Insights</a>
            <a href="?logout=true" class="rounded-full px-4 py-2 text-slate-400 transition hover:bg-rose-500/10 hover:text-rose-300">Lock</a>
        </nav>
    </div>
</header>
<main class="mx-auto flex w-full max-w-6xl flex-1 flex-col px-6 pb-16 pt-12">
