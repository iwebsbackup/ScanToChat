  <p class="text-center small mt-3 mb-0" style="color:#8fa8b4">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></p>
</div>

<div id="snackbars" aria-live="polite" aria-atomic="false"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.__flash = <?= json_encode(flash_pull(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="assets/admin.js?v=2"></script>
</body>
</html>
