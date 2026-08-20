<?php
$htmlLang = current_lang() === 'en' ? 'en' : 'zh-CN';
$pageHeadTitle = $pageHeadTitle ?? lang('app.name');
?>
<!DOCTYPE html>
<html lang="<?php echo h($htmlLang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageHeadTitle); ?></title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
