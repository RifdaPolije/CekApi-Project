<?php
/** Variabel: $pageLibs (URL skrip eksternal), $pageJs (nama file js tanpa .js) */
$pageLibs = $pageLibs ?? [];
$pageJs   = $pageJs ?? [];
?>
</div><!-- /.shell -->
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script>
  window.CEKAPI = { base: <?= json_encode(BASE_URL) ?>, csrf: <?= json_encode(csrf_token()) ?> };
</script>
<?php foreach ($pageLibs as $src): ?>
<script src="<?= h($src) ?>"></script>
<?php endforeach; ?>
<script src="<?= h(base_url('assets/js/app.js')) ?>"></script>
<?php foreach ($pageJs as $j): ?>
<script src="<?= h(base_url('assets/js/' . $j . '.js')) ?>"></script>
<?php endforeach; ?>
</body>
</html>
