</main>
<footer class="border-t border-slate-800 bg-slate-900/80 py-6">
    <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-6 text-sm text-slate-400 md:flex-row">
        <p>&copy; <?php echo date('Y'); ?> Harvard School Library. Crafted with care for a smarter catalogue.</p>
        <p>Powered by Tailwind CSS &amp; SQLite.</p>
    </div>
</footer>
<script>
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.getAttribute('data-confirm');
            if (!window.confirm(message || 'Are you sure?')) {
                event.preventDefault();
            }
        });
    });
</script>
</body>
</html>
