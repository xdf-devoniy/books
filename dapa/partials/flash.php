<?php
if (!empty($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $type = $flash['type'] ?? 'info';
    $message = $flash['message'] ?? '';

    $styles = [
        'success' => 'bg-emerald-500/10 text-emerald-200 border-emerald-500/40',
        'error' => 'bg-rose-500/10 text-rose-200 border-rose-500/40',
        'info' => 'bg-sky-500/10 text-sky-200 border-sky-500/40',
    ];

    $icon = [
        'success' => 'M4.5 12.75l6 6 9-13.5',
        'error' => 'M6 18L18 6M6 6l12 12',
        'info' => 'M12 8.25h.008v.008H12z M12 12v6',
    ];

    $styleClass = $styles[$type] ?? $styles['info'];
    $path = $icon[$type] ?? $icon['info'];
    ?>
    <div class="mb-8 flex items-start gap-3 rounded-2xl border px-5 py-4 shadow-lg shadow-black/10 <?php echo $styleClass; ?>">
        <span class="mt-0.5 inline-flex rounded-full bg-black/10 p-2">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="<?php echo $path; ?>" />
            </svg>
        </span>
        <p class="text-sm font-medium leading-relaxed"><?php echo nl2br(htmlspecialchars($message)); ?></p>
    </div>
    <?php
}
?>
