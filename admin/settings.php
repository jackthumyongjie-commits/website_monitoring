<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/telegram.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'test') {
        $ok = telegram_send(
            $pdo,
            lang('set.test_msg', ['time' => date('Y-m-d H:i:s')]),
            '🩵 Open Telegram',
            'https://telegram.org'
        );
        flash($ok ? 'success' : 'error', $ok ? lang('set.test_ok') : lang('set.test_fail'));
        redirect('/admin/settings.php');
    }

    if ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $stmt = $pdo->prepare('SELECT password_hash FROM admins WHERE id = ?');
        $stmt->execute([current_admin()['id']]);
        $hash = $stmt->fetchColumn();
        if (!password_verify($current, $hash)) {
            flash('error', lang('set.bad_current'));
        } elseif (strlen($new) < 6) {
            flash('error', lang('set.pw_short'));
        } else {
            $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), current_admin()['id']]);
            flash('success', lang('set.pw_ok'));
        }
        redirect('/admin/settings.php');
    }

    set_setting($pdo, 'telegram_bot_token', trim($_POST['telegram_bot_token'] ?? ''));
    set_setting($pdo, 'telegram_chat_id', trim($_POST['telegram_chat_id'] ?? ''));
    $threshold = max(100, (int) ($_POST['slow_threshold_ms'] ?? 3000));
    set_setting($pdo, 'slow_threshold_ms', (string) $threshold);
    flash('success', lang('set.saved'));
    redirect('/admin/settings.php');
}

$token = setting($pdo, 'telegram_bot_token', TELEGRAM_BOT_TOKEN_DEFAULT);
$chatId = setting($pdo, 'telegram_chat_id', TELEGRAM_CHAT_ID_DEFAULT);
$threshold = setting($pdo, 'slow_threshold_ms', (string) SLOW_THRESHOLD_MS_DEFAULT);

$pageTitle = lang('set.title');
$pageSub = lang('set.sub');
$tgReady = telegram_configured($token, $chatId);
require __DIR__ . '/../includes/header.php';
?>
<div class="grid-2">
    <section class="panel">
        <div class="panel-head">
            <h3><i class="bi bi-telegram"></i> <?php echo h(lang('set.tg_title')); ?></h3>
        </div>
        <div class="panel-body">
            <p class="muted"><?php echo h($tgReady ? lang('set.tg_ready') : lang('set.tg_wait')); ?></p>
            <form method="post" class="form">
                <input type="hidden" name="action" value="save">
                <label><?php echo h(lang('set.token')); ?></label>
                <input type="text" name="telegram_bot_token" value="<?php echo h($token); ?>">

                <label><?php echo h(lang('set.chat')); ?></label>
                <input type="text" name="telegram_chat_id" value="<?php echo h($chatId); ?>">

                <label><?php echo h(lang('set.slow')); ?></label>
                <input type="number" name="slow_threshold_ms" min="100" value="<?php echo (int) $threshold; ?>">
                <p class="muted"><?php echo h(lang('set.slow_hint')); ?></p>
                <div class="form-actions">
                    <button type="submit"><i class="bi bi-save"></i> <?php echo h(lang('set.save')); ?></button>
                </div>
            </form>
            <form method="post" style="margin-top:12px;">
                <input type="hidden" name="action" value="test">
                <button type="submit" class="btn-secondary"><i class="bi bi-send"></i> <?php echo h(lang('set.test')); ?></button>
            </form>
        </div>
    </section>
    <section class="panel">
        <div class="panel-head">
            <h3><i class="bi bi-shield-lock"></i> <?php echo h(lang('set.password')); ?></h3>
        </div>
        <div class="panel-body">
            <form method="post" class="form">
                <input type="hidden" name="action" value="password">
                <label><?php echo h(lang('set.current')); ?></label>
                <div class="password-row">
                    <input type="password" name="current_password" required>
                    <button type="button" class="pw-toggle" data-target="current_password"><i class="bi bi-eye"></i></button>
                </div>
                <label><?php echo h(lang('set.new')); ?></label>
                <div class="password-row">
                    <input type="password" name="new_password" required minlength="6">
                    <button type="button" class="pw-toggle" data-target="new_password"><i class="bi bi-eye"></i></button>
                </div>
                <div class="form-actions">
                    <button type="submit"><i class="bi bi-save"></i> <?php echo h(lang('set.save_pw')); ?></button>
                </div>
            </form>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
