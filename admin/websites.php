<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/monitor.php';

remove_legacy_deployment_guard($pdo);

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $pdo->prepare('DELETE FROM websites WHERE id = ?')->execute([$id]);
    flash('success', lang('web.deleted'));
    redirect('/admin/websites.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_id'])) {
    $result = run_one_website($pdo, (int) $_POST['run_id']);
    if (!$result) {
        flash('error', lang('web.not_found'));
    } else {
        $msg = lang('web.checked', [
            'name' => $result['name'],
            'status' => $result['status'],
            'ms' => (string) (int) $result['response_time'],
        ]);
        if ($result['status'] === 'DOWN') {
            $msg .= lang('web.checked_down');
        }
        flash('success', $msg);
    }
    redirect('/admin/websites.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_all'])) {
    $result = run_monitoring($pdo, true);
    flash('success', lang('web.checked_all', ['n' => (string) $result['checked']]));
    redirect('/admin/websites.php');
}

$q = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all';

$sql = 'SELECT * FROM websites WHERE 1=1';
$params = [];

if ($q !== '') {
    $sql .= ' AND (name LIKE ? OR url LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}

if ($filter === 'up') {
    $sql .= " AND status = 'UP'";
} elseif ($filter === 'down') {
    $sql .= " AND status = 'DOWN'";
} elseif ($filter === 'today') {
    $sql .= ' AND DATE(last_checked) = CURDATE()';
} elseif ($filter === 'week') {
    $sql .= ' AND last_checked >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
}

$sql .= ' ORDER BY name ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sites = $stmt->fetchAll();

$pageTitle = lang('web.title');
$pageSub = lang('web.sub');
require __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <div class="panel-head">
        <h3><i class="bi bi-globe2"></i> <?php echo h(lang('web.title')); ?></h3>
        <div class="toolbar">
            <form method="get" class="toolbar" style="margin:0;">
                <input type="text" name="q" placeholder="<?php echo h(lang('web.search')); ?>" value="<?php echo h($q); ?>">
                <select name="filter">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>><?php echo h(lang('web.filter_all')); ?></option>
                    <option value="up" <?php echo $filter === 'up' ? 'selected' : ''; ?>><?php echo h(lang('web.filter_up')); ?></option>
                    <option value="down" <?php echo $filter === 'down' ? 'selected' : ''; ?>><?php echo h(lang('web.filter_down')); ?></option>
                    <option value="today" <?php echo $filter === 'today' ? 'selected' : ''; ?>><?php echo h(lang('web.filter_today')); ?></option>
                    <option value="week" <?php echo $filter === 'week' ? 'selected' : ''; ?>><?php echo h(lang('web.filter_week')); ?></option>
                </select>
                <button type="submit"><i class="bi bi-search"></i> <?php echo h(lang('web.search_btn')); ?></button>
            </form>
            <a class="btn" href="<?php echo BASE_URL; ?>/admin/website-form.php"><i class="bi bi-plus-lg"></i> <?php echo h(lang('web.add')); ?></a>
            <form method="post" style="margin:0;">
                <button type="submit" name="run_all" value="1" class="btn-secondary"><i class="bi bi-arrow-repeat"></i> <?php echo h(lang('web.run_all')); ?></button>
            </form>
        </div>
    </div>
    <?php if (!$sites): ?>
        <div class="empty">
            <i class="bi bi-globe"></i>
            <strong><?php echo h(lang('empty.icon_web')); ?></strong>
            <p><?php echo h(lang('web.empty')); ?></p>
            <a class="btn" href="<?php echo BASE_URL; ?>/admin/website-form.php"><?php echo h(lang('web.add')); ?></a>
        </div>
    <?php else: ?>
    <div class="table-wrap" id="websitesTable" data-status-url="<?php echo h(BASE_URL); ?>/admin/websites-status.php">
        <table>
            <tr>
                <th><?php echo h(lang('web.col_name')); ?></th>
                <th><?php echo h(lang('web.col_url')); ?></th>
                <th><?php echo h(lang('col.status')); ?></th>
                <th><?php echo h(lang('col.response')); ?></th>
                <th><?php echo h(lang('web.col_last')); ?></th>
                <th><?php echo h(lang('web.col_interval')); ?></th>
                <th><?php echo h(lang('web.col_actions')); ?></th>
            </tr>
            <?php foreach ($sites as $site): ?>
                <tr data-site-id="<?php echo (int) $site['id']; ?>">
                    <td><strong><?php echo h($site['name']); ?></strong></td>
                    <td><a class="url-cell" href="<?php echo h($site['url']); ?>" target="_blank" rel="noopener"><?php echo h($site['url']); ?></a></td>
                    <td class="site-status">
                        <?php echo status_badge($site['status']); ?>
                        <?php if ((int) $site['is_slow'] === 1): ?>
                            <span class="badge badge-slow"><?php echo h(lang('status.slow')); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="site-response"><?php echo $site['response_time'] !== null ? (int) $site['response_time'] . ' ms' : '-'; ?></td>
                    <td class="site-last"><?php echo h(format_datetime($site['last_checked'])); ?></td>
                    <td><?php echo h(lang('web.min', ['n' => (string) (int) $site['interval_minutes']])); ?></td>
                    <td class="actions">
                        <form method="post">
                            <input type="hidden" name="run_id" value="<?php echo (int) $site['id']; ?>">
                            <button type="submit" class="btn-run"><i class="bi bi-play-fill"></i> <?php echo h(lang('web.run')); ?></button>
                        </form>
                        <a class="btn-link" href="<?php echo BASE_URL; ?>/admin/website-form.php?id=<?php echo (int) $site['id']; ?>"><?php echo h(lang('web.edit')); ?></a>
                        <a class="btn-link" href="<?php echo BASE_URL; ?>/admin/uptime.php?id=<?php echo (int) $site['id']; ?>"><?php echo h(lang('nav.uptime')); ?></a>
                        <a class="btn-link" href="<?php echo BASE_URL; ?>/admin/logs.php?website_id=<?php echo (int) $site['id']; ?>"><?php echo h(lang('web.logs')); ?></a>
                        <a class="btn-danger" href="<?php echo BASE_URL; ?>/admin/websites.php?delete=<?php echo (int) $site['id']; ?>" onclick="return confirm('<?php echo h(lang('web.delete_confirm')); ?>');"><?php echo h(lang('web.delete')); ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
