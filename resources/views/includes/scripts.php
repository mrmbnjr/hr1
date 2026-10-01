  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Shared JS -->
  <script src="<?= asset('js/script.js') ?>"></script>

  <!-- Page-specific JS -->
  <?php if (!empty($pageJS)): ?>
      <script src="<?= asset('js/' . $pageJS) ?>"></script>
  <?php endif; ?>
</body>