<?php
$pageTitle = $pageTitle ?? lang('up.title');
$pageSub = $pageSub ?? lang('up.public_sub');
$pageHeadTitle = $pageTitle . ' | ' . lang('app.name');
require __DIR__ . '/head.php';
?>
<body class="app-body public-body">
<div class="blob blob-a"></div>
<div class="blob blob-b"></div>
<div class="public-wrap">
    <header class="public-top">
        <a class="public-logo" href="<?php echo BASE_URL; ?>/uptime/">
            <span class="logo-mark">◉</span>
            <span>
                <strong><?php echo h(lang('app.name')); ?></strong>
                <small><?php echo h(lang('up.title')); ?></small>
            </span>
        </a>
        <div class="public-actions">
            <?php echo lang_switcher(); ?>
            <a class="btn-secondary public-admin-link" href="<?php echo BASE_URL; ?>/index.php?next=<?php echo urlencode('/admin/index.php'); ?>"><?php echo h(lang('up.admin_login')); ?></a>
        </div>
    </header>
    <main class="public-main">
        <div class="page-intro">
            <p class="eyebrow"><?php echo h($pageSub); ?></p>
            <h1><?php echo h($pageTitle); ?></h1>
        </div>
