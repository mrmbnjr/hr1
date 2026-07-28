  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- Shared JS -->
  <script src="/hr1/public/assets/js/script.js"></script>

  <!-- Page-specific JS -->
  <?php if (!empty($pageJS)): ?>
      <script src="/hr1/public/assets/js/<?= $pageJS ?>"></script>
  <?php endif; ?>
</body>