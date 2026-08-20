<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$websiteId = isset($_GET['website_id']) ? (int) $_GET['website_id'] : 0;
$status = $_GET['status'] ?? '';
$q = trim($_GET['q'] ?? '');
$range = $_GET['range'] ?? 'all';

$sql = 'SELECT l.*, w.name AS website_name, w.url
        FROM logs l
        JOIN websites w ON w.id = l.website_id
        WHERE 1=1';
$params = [];

if ($websiteId) {
    $sql .= ' AND l.website_id = ?';
    $params[] = $websiteId;
}
if ($status === 'UP' || $status === 'DOWN') {
    $sql .= ' AND l.status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $sql .= ' AND (w.name LIKE ? OR w.url LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($range === 'today') {
    $sql .= ' AND DATE(l.created_at) = CURDATE()';
} elseif ($range === 'week') {
    $sql .= ' AND l.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
}

$sql .= ' ORDER BY l.created_at DESC LIMIT 300';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$changes = [];
$prevBySite = [];
foreach (array_reverse($logs) as $row) {
    $wid = $row['website_id'];
    if (!isset($prevBySite[$wid])) {
        $prevBySite[$wid] = $row['status'];
        continue;
    }
    if ($prevBySite[$wid] !== $row['status']) {
        $changes[] = $row['id'];
        $prevBySite[$wid] = $row['status'];
    }
}

$pageTitle = lang('log.title');
$pageSub = lang('log.sub');
require __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <div class="panel-head">
        <h3><?php echo h(lang('log.title')); ?></h3>
        <form class="toolbar" method="get" style="margin:0;">
            <input type="text" name="q" placeholder="<?php echo h(lang('log.search')); ?>" value="<?php echo h($q); ?>">
            <select name="status">
                <option value=""><?php echo h(lang('log.all_status')); ?></option>
                <option value="UP" <?php echo $status === 'UP' ? 'selected' : ''; ?>><?php echo h(lang('status.up')); ?></option>
                <option value="DOWN" <?php echo $status === 'DOWN' ? 'selected' : ''; ?>><?php echo h(lang('status.down')); ?></option>
            </select>
            <select name="range">
                <option value="all" <?php echo $range === 'all' ? 'selected' : ''; ?>><?php echo h(lang('log.all_time')); ?></option>
                <option value="today" <?php echo $range === 'today' ? 'selected' : ''; ?>><?php echo h(lang('log.today')); ?></option>
                <option value="week" <?php echo $range === 'week' ? 'selected' : ''; ?>><?php echo h(lang('log.week')); ?></option>
            </select>
            <input type="hidden" name="website_id" value="<?php echo $websiteId; ?>">
            <button type="submit"><i class="bi bi-funnel"></i> <?php echo h(lang('log.filter')); ?></button>
        </form>
    </div>
    <p class="muted" style="padding:12px 18px 0;"><?php echo h(lang('log.hint')); ?></p>
    <?php if (!$logs): ?>
        <div class="empty"><i class="bi bi-journal-text"></i><?php echo h(lang('empty.icon_log')); ?><p><?php echo h(lang('log.empty')); ?></p></div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <tr>
                <th><?php echo h(lang('log.checked_at')); ?></th>
                <th><?php echo h(lang('col.website')); ?></th>
                <th><?php echo h(lang('web.col_url')); ?></th>
                <th><?php echo h(lang('col.status')); ?></th>
                <th><?php echo h(lang('col.response')); ?></th>
                <th><?php echo h(lang('log.note')); ?></th>
                <th><?php echo h(lang('log.change')); ?></th>
            </tr>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo h(format_datetime($log['created_at'])); ?></td>
                    <td><?php echo h($log['website_name']); ?></td>
                    <td><span class="url-cell"><?php echo h($log['url']); ?></span></td>
                    <td><?php echo status_badge($log['status']); ?></td>
                    <td><?php echo $log['response_time'] !== null ? (int) $log['response_time'] . ' ms' : '-'; ?></td>
                    <td><?php echo h($log['note']); ?></td>
                    <td><?php echo in_array($log['id'], $changes, true) ? '<span class="badge badge-change">' . h(lang('log.changed')) . '</span>' : ''; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
