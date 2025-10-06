<?php
$flash = get_flash();
if ($flash):
    $styles = [
        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'error' => 'bg-red-50 text-red-700 border-red-200',
        'info' => 'bg-blue-50 text-blue-700 border-blue-200',
    ];
    $style = $styles[$flash['type']] ?? $styles['info'];
?>
<div class="mb-6">
    <div class="border rounded-lg px-4 py-3 <?= $style ?>">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
</div>
<?php endif; ?>
