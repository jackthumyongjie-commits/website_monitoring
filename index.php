<?php
require_once __DIR__ . '/includes/auth.php';

if (current_admin()) {
    redirect(safe_admin_next($_GET['next'] ?? null));
}

$error = '';
$loginNext = safe_admin_next($_GET['next'] ?? null);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $loginNext = safe_admin_next($_POST['next'] ?? $loginNext);
    if ($username === '' || $password === '') {
        $error = lang('login.need_both');
    } elseif (!login_admin($pdo, $username, $password)) {
        $error = lang('login.invalid');
    } else {
        redirect($loginNext);
    }
}
$pageHeadTitle = lang('login.title') . ' | ' . lang('app.name');
require __DIR__ . '/includes/head.php';
?>
<body class="auth-body">
<div class="auth-page">
    <section class="auth-hero">
        <div class="tag"><?php echo h(lang('login.tag')); ?></div>
        <h1><?php echo h(lang('app.name')); ?></h1>
        <p><?php echo h(lang('login.lead')); ?></p>
    </section>
    <div class="auth-form-wrap">
        <div class="login-box">
            <div class="auth-lang"><?php echo lang_switcher(); ?></div>
            <h2><?php echo h(lang('login.title')); ?></h2>
            <p class="muted"><?php echo h(lang('login.only')); ?></p>
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="bi bi-exclamation-triangle"></i> <?php echo h($error); ?></div>
            <?php endif; ?>
            <?php if ($msg = flash('success')): ?>
                <div class="alert alert-success"><?php echo h($msg); ?></div>
            <?php endif; ?>
            <?php if ($msg = flash('error')): ?>
                <div class="alert alert-error"><?php echo h($msg); ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="next" value="<?php echo h($loginNext); ?>">
                <label><?php echo h(lang('login.username')); ?></label>
                <input type="text" name="username" required autofocus>
                <label><?php echo h(lang('login.password')); ?></label>
                <div class="password-row">
                    <input type="password" name="password" id="password" required>
                    <button type="button" class="btn-secondary" onclick="togglePassword('password')"><?php echo h(lang('show_hide')); ?></button>
                </div>
                <p><button type="submit" style="width:100%;margin-top:18px;justify-content:center;"><?php echo h(lang('login.submit')); ?></button></p>
            </form>
            <p class="muted"><a href="<?php echo BASE_URL; ?>/forgot-password.php"><?php echo h(lang('login.forgot')); ?></a></p>
            <p class="muted"><?php echo h(lang('login.default')); ?></p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/watch_script.php'; ?>
<script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
</body>
</html>
