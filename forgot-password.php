<?php
require_once __DIR__ . '/includes/auth.php';

if (current_admin()) {
    redirect('/admin/index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['reset_code'] ?? '');
    $password = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($code !== RESET_CODE) {
        $error = lang('forgot.bad_code');
    } elseif (strlen($password) < 6) {
        $error = lang('forgot.short');
    } elseif ($password !== $confirm) {
        $error = lang('forgot.mismatch');
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE admins SET password_hash = ? WHERE username = ?')->execute([$hash, 'admin']);
        flash('success', lang('forgot.done'));
        redirect('/index.php');
    }
}
$pageHeadTitle = lang('forgot.title') . ' | ' . lang('app.name');
require __DIR__ . '/includes/head.php';
?>
<body>
<div class="auth-page">
    <section class="auth-hero">
        <div class="tag"><?php echo h(lang('login.tag')); ?></div>
        <h1><?php echo h(lang('forgot.title')); ?></h1>
        <p><?php echo h(lang('forgot.hint')); ?></p>
    </section>
    <div class="auth-form-wrap">
        <div class="login-box">
            <div class="auth-lang"><?php echo lang_switcher(); ?></div>
            <h2><?php echo h(lang('forgot.title')); ?></h2>
            <p class="muted"><?php echo h(lang('forgot.hint')); ?></p>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo h($error); ?></div>
            <?php endif; ?>
            <form method="post">
                <label><?php echo h(lang('forgot.code')); ?></label>
                <input type="text" name="reset_code" required>
                <label><?php echo h(lang('forgot.new')); ?></label>
                <div class="password-row">
                    <input type="password" name="new_password" id="new_password" required>
                    <button type="button" class="btn-secondary" onclick="togglePassword('new_password')"><?php echo h(lang('show_hide')); ?></button>
                </div>
                <label><?php echo h(lang('forgot.confirm')); ?></label>
                <input type="password" name="confirm_password" required>
                <p><button type="submit" style="width:100%;margin-top:18px;justify-content:center;"><?php echo h(lang('forgot.submit')); ?></button></p>
            </form>
            <p><a href="<?php echo BASE_URL; ?>/index.php"><?php echo h(lang('forgot.back')); ?></a></p>
        </div>
    </div>
</div>
<script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
</body>
</html>
