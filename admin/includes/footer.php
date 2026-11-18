  </main>
  <footer class="app-footer">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?> Admin</footer>
</div>

<div id="snackbars" aria-live="polite" aria-atomic="false"></div>

<!-- Global confirm dialog -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
    <div class="modal-content">
      <div class="modal-body text-center p-4">
        <div class="confirm-ico"><i class="bi bi-exclamation-triangle" id="cIcon"></i></div>
        <h5 class="fw-bold mb-2" id="cTitle">Are you sure?</h5>
        <p class="text-muted mb-4" id="cText"></p>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-soft flex-fill" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger flex-fill" id="cOk">Confirm</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.__flash = <?= json_encode(flash_pull(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="assets/admin.js?v=2"></script>
</body>
</html>
