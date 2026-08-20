<?php
require_once __DIR__ . '/auth.php';
require_admin();

$admin = current_admin();
$pageTitle = $pageTitle ?? lang('nav.dashboard');
$pageSub = $pageSub ?? lang('ui.subtitle');
$pageHeadTitle = $pageTitle . ' | ' . lang('app.name');
require __DIR__ . '/head.php';
$initial = strtoupper(substr((string) ($admin['username'] ?? 'A'), 0, 1));
?>
<body class="app-body">
<div class="blob blob-a"></div>
<div class="blob blob-b"></div>
<button type="button" class="nav-toggle" id="sidebarToggle" aria-label="menu"><i class="bi bi-list"></i></button>
<aside class="side-rail" id="sidebar">
    <a class="logo" href="<?php echo BASE_URL; ?>/admin/index.php">
        <span class="logo-mark">◉</span>
        <span>
            <strong><?php echo h(lang('app.name')); ?></strong>
            <small><?php echo h(lang('app.admin_only')); ?></small>
        </span>
    </a>
    <nav class="main-nav">
        <a class="<?php echo nav_active('index.php'); ?>" href="<?php echo BASE_URL; ?>/admin/index.php"><?php echo h(lang('nav.dashboard')); ?></a>
        <a class="<?php echo nav_active('websites.php'); ?>" href="<?php echo BASE_URL; ?>/admin/websites.php"><?php echo h(lang('nav.websites')); ?></a>
        <a class="<?php echo nav_active('uptime.php'); ?>" href="<?php echo BASE_URL; ?>/admin/uptime.php"><?php echo h(lang('nav.uptime')); ?></a>
        <a class="<?php echo nav_active('logs.php'); ?>" href="<?php echo BASE_URL; ?>/admin/logs.php"><?php echo h(lang('nav.logs')); ?></a>
        <a class="<?php echo nav_active('settings.php'); ?>" href="<?php echo BASE_URL; ?>/admin/settings.php"><?php echo h(lang('nav.settings')); ?></a>
    </nav>
    <div class="rail-end">
        <?php echo lang_switcher(); ?>
        <span class="clock-chip"><span id="liveClock"></span></span>
        <div class="user-chip">
            <span class="avatar"><?php echo h($initial); ?></span>
            <a href="<?php echo BASE_URL; ?>/admin/logout.php"><?php echo h(lang('nav.logout')); ?></a>
        </div>
    </div>
</aside>
<main class="page-shell">
    <div class="page-intro">
        <p class="eyebrow"><?php echo h($pageSub); ?></p>
        <h1><?php echo h($pageTitle); ?></h1>
    </div>
    <?php if ($msg = flash('success')): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?php echo h($msg); ?></div>
    <?php endif; ?>
    <?php if ($msg = flash('error')): ?>
        <div class="alert alert-error"><i class="bi bi-exclamation-triangle"></i> <?php echo h($msg); ?></div>
    <?php endif; ?>
