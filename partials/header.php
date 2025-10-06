<?php
if (!function_exists('current_account')) {
    require_once __DIR__ . '/../db.php';
}

$account = current_account();
$accountLabel = get_account_label($account);
$pageTitle = $pageTitle ?? 'Kitob boshqaruvi';
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle) ?> • <?= htmlspecialchars($accountLabel) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen">
    <div class="bg-white shadow-sm">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800"><?= htmlspecialchars($pageTitle) ?></h1>
                <p class="text-sm text-slate-500 mt-1">Hisob: <span class="font-medium text-slate-700"><?= htmlspecialchars($accountLabel) ?></span></p>
            </div>
            <div class="flex items-center gap-3">
                <form method="get" action="" class="flex items-center gap-2">
                    <?php foreach (APP_ACCOUNTS as $key => $label): ?>
                        <a href="?account=<?= urlencode($key) ?>" class="px-3 py-2 rounded-lg border text-sm font-medium <?= $account === $key ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-slate-600 border-slate-200 hover:border-blue-400 hover:text-blue-500' ?>">
                            <?= htmlspecialchars($label) ?>
                        </a>
                    <?php endforeach; ?>
                </form>
                <a href="/index.php?account=<?= urlencode($account) ?>" class="px-3 py-2 text-sm font-medium text-blue-600 hover:text-blue-700">Asosiy panel</a>
            </div>
        </div>
        <nav class="border-t border-slate-200">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <ul class="flex flex-wrap gap-3 py-3 text-sm font-medium text-slate-600">
                    <li><a class="hover:text-blue-600" href="/index.php?account=<?= urlencode($account) ?>">Ko'rsatkichlar</a></li>
                    <li><a class="hover:text-blue-600" href="/books.php?account=<?= urlencode($account) ?>">Kitoblar</a></li>
                    <li><a class="hover:text-blue-600" href="/sell.php?account=<?= urlencode($account) ?>">Sotish</a></li>
                    <li><a class="hover:text-blue-600" href="/reports.php?account=<?= urlencode($account) ?>">Hisobotlar</a></li>
                </ul>
            </div>
        </nav>
    </div>
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
