<?php
if (empty($_SESSION['user-id'])) {
    require __DIR__ . '/public_footer.php';
    return;
}
?>
    </main>
    <footer class="px-4 py-3 small text-muted-2 border-top bg-white no-print">
      &copy; <?= date('Y') ?> UKESMAS Universitas Klabat &middot; <?= e(APP_NAME) ?> v<?= APP_VERSION ?> &middot; Sistem Layanan Kesehatan Kampus Terpadu
    </footer>
  </div>
</div>

<?php require __DIR__ . '/chat_widget.php'; ?>

<script>window.APP_BASE = <?= json_encode(rtrim(APP_PATH, '/')) ?>;</script>
<script src="<?= asset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<?php if (!empty($data['charts'])): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php endif; ?>
<script src="<?= asset('js/app.js') ?>?v=<?= APP_VERSION ?>.3"></script>
<?php if (in_array($_SESSION['user-role'] ?? '', ['student', 'lecturer'], true)): ?>
<?php require __DIR__ . '/queue_call_modal.php'; ?>
<?php endif; ?>
<?php require __DIR__ . '/single_tab_lock.php'; ?>
<?= $data['extra_js'] ?? '' ?>
</body>
</html>
