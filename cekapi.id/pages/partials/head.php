<?php
/** Variabel: $pageTitle, $pageCss (array nama file css tanpa .css) */
$pageCss = $pageCss ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title><?= h($pageTitle ?? 'CEKAPI.id') ?> | CEKAPI.id</title>
  <link rel="icon" type="image/svg+xml" href="<?= h(base_url('assets/img/logo.svg')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&amp;family=Poppins:wght@300;400;500;600;700;800&amp;display=swap">
  <link rel="stylesheet" href="<?= h(base_url('assets/css/global.css')) ?>">
  <link rel="stylesheet" href="<?= h(base_url('assets/css/layout.css')) ?>">
<?php foreach ($pageCss as $c): ?>
  <link rel="stylesheet" href="<?= h(base_url('assets/css/' . $c . '.css')) ?>">
<?php endforeach; ?>
</head>
<body class="app">
