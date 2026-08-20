<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/monitor.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$site = [
    'name' => '',
    'url' => '',
    'interval_minutes' => 5,
];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM websites WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash('error', lang('web.not_found'));
        redirect('/admin/websites.php');
    }
    $site = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $interval = (int) ($_POST['interval_minutes'] ?? 5);

    if ($name === '' || $url === '') {
        flash('error', lang('web.need_name_url'));
    } elseif (!is_valid_url($url)) {
        flash('error', lang('web.bad_url'));
    } elseif ($interval < 1) {
        flash('error', lang('web.bad_interval'));
    } else {
        if ($id) {
            $pdo->prepare('UPDATE websites SET name = ?, url = ?, interval_minutes = ? WHERE id = ?')
                ->execute([$name, $url, $interval, $id]);
            $siteId = $id;
            flash('success', lang('web.updated'));
        } else {
            $pdo->prepare('INSERT INTO websites (name, url, interval_minutes) VALUES (?, ?, ?)')
                ->execute([$name, $url, $interval]);
            $siteId = (int) $pdo->lastInsertId();
            flash('success', lang('web.added'));
        }
        run_one_website($pdo, $siteId);
        redirect('/admin/websites.php');
    }
    $site['name'] = $name;
    $site['url'] = $url;
    $site['interval_minutes'] = $interval;
}

$pageTitle = $id ? lang('web.edit_title') : lang('web.add_title');
$pageSub = lang('web.form_sub');
require __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <div class="panel-head">
        <h3><i class="bi bi-pencil-square"></i> <?php echo h($pageTitle); ?></h3>
    </div>
    <div class="panel-body">
        <form method="post" class="form">
            <label><?php echo h(lang('web.col_name')); ?></label>
            <input type="text" name="name" required value="<?php echo h($site['name']); ?>">

            <label><?php echo h(lang('web.col_url')); ?></label>
            <input type="url" name="url" required value="<?php echo h($site['url']); ?>" placeholder="https://example.com">

            <label><?php echo h(lang('web.col_interval')); ?></label>
            <input type="number" name="interval_minutes" min="1" value="<?php echo (int) $site['interval_minutes']; ?>">
            <p class="muted"><?php echo h(lang('web.interval_hint')); ?></p>

            <div class="form-actions">
                <button type="submit"><i class="bi bi-save"></i> <?php echo h(lang('set.save')); ?></button>
                <a class="btn-secondary" href="<?php echo BASE_URL; ?>/admin/websites.php"><?php echo h(lang('web.back')); ?></a>
            </div>
        </form>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
