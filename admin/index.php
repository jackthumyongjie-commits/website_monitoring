<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$total = (int) $pdo->query('SELECT COUNT(*) FROM websites')->fetchColumn();
$up = (int) $pdo->query("SELECT COUNT(*) FROM websites WHERE status = 'UP'")->fetchColumn();
$down = (int) $pdo->query("SELECT COUNT(*) FROM websites WHERE status = 'DOWN'")->fetchColumn();
$unknown = max(0, $total - $up - $down);

$recentAlerts = $pdo->query(
    'SELECT a.*, w.name AS website_name
     FROM alerts a
     JOIN websites w ON w.id = a.website_id
     ORDER BY a.created_at DESC
     LIMIT 8'
)->fetchAll();

$latestLogs = $pdo->query(
    'SELECT l.*, w.name AS website_name, w.url
     FROM logs l
     JOIN websites w ON w.id = l.website_id
     ORDER BY l.created_at DESC
     LIMIT 10'
)->fetchAll();

$pageTitle = lang('dash.title');
$pageSub = lang('dash.sub');
$cronRunning = cron_is_running();
$cronLast = cron_last_run();
require __DIR__ . '/../includes/header.php';
?>
<?php if (!$cronRunning): ?>
<section class="panel" style="margin-bottom:1rem;border-color:#f59e0b;background:#fffbeb;">
    <p style="margin:0;color:#92400e;"><i class="bi bi-exclamation-triangle"></i> <?php echo h(lang('dash.cron_off')); ?></p>
</section>
<?php else: ?>
<section class="panel" style="margin-bottom:1rem;border-color:#0f766e;background:#ecfdf5;">
    <p style="margin:0;color:#0f766e;"><i class="bi bi-clock-history"></i> <?php echo h(str_replace('%time%', date('Y-m-d H:i:s', $cronLast), lang('dash.cron_on'))); ?></p>
</section>
<?php endif; ?>
<section class="hero-banner">
    <div>
        <h2><?php echo h(lang('dash.hero_title')); ?></h2>
        <p><?php echo h(lang('dash.hero_p')); ?></p>
    </div>
    <a class="btn-ghost" href="<?php echo BASE_URL; ?>/admin/websites.php"><i class="bi bi-play-circle"></i> <?php echo h(lang('dash.manage')); ?></a>
</section>

<div class="cards">
    <div class="stat-card">
        <div>
            <p class="label"><?php echo h(lang('dash.total')); ?></p>
            <div class="num"><?php echo $total; ?></div>
        </div>
        <div class="stat-ico ico-navy"><i class="bi bi-collection"></i></div>
    </div>
    <div class="stat-card">
        <div>
            <p class="label"><?php echo h(lang('dash.up')); ?></p>
            <div class="num" style="color:#0f766e;"><?php echo $up; ?></div>
        </div>
        <div class="stat-ico ico-green"><i class="bi bi-check-lg"></i></div>
    </div>
    <div class="stat-card">
        <div>
            <p class="label"><?php echo h(lang('dash.down')); ?></p>
            <div class="num" style="color:#c2410c;"><?php echo $down; ?></div>
        </div>
        <div class="stat-ico ico-red"><i class="bi bi-x-lg"></i></div>
    </div>
    <div class="stat-card">
        <div>
            <p class="label"><?php echo h(lang('dash.unknown')); ?></p>
            <div class="num"><?php echo $unknown; ?></div>
        </div>
        <div class="stat-ico ico-amber"><i class="bi bi-question-lg"></i></div>
    </div>
</div>

<div class="grid-2">
    <section class="panel">
        <div class="panel-head"><h3><?php echo h(lang('dash.alerts')); ?></h3></div>
        <?php if (!$recentAlerts): ?>
            <div class="empty"><i class="bi bi-bell"></i><?php echo h(lang('dash.no_alerts')); ?></div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <tr>
                        <th><?php echo h(lang('col.time')); ?></th>
                        <th><?php echo h(lang('col.website')); ?></th>
                        <th><?php echo h(lang('col.type')); ?></th>
                    </tr>
                    <?php foreach ($recentAlerts as $a): ?>
                        <tr>
                            <td><?php echo h(format_datetime($a['created_at'])); ?></td>
                            <td><?php echo h($a['website_name']); ?></td>
                            <td><span class="badge <?php echo $a['alert_type'] === 'RECOVERY' ? 'badge-up' : ($a['alert_type'] === 'SLOW' ? 'badge-slow' : 'badge-down'); ?>"><?php echo h(lang('alert.' . $a['alert_type'])); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </section>
    <section class="panel">
        <div class="panel-head"><h3><?php echo h(lang('dash.activity')); ?></h3></div>
        <?php if (!$latestLogs): ?>
            <div class="empty"><i class="bi bi-activity"></i><?php echo h(lang('dash.no_logs')); ?></div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <tr>
                        <th><?php echo h(lang('col.time')); ?></th>
                        <th><?php echo h(lang('col.website')); ?></th>
                        <th><?php echo h(lang('col.status')); ?></th>
                        <th><?php echo h(lang('col.response')); ?></th>
                    </tr>
                    <?php foreach ($latestLogs as $log): ?>
                        <tr>
                            <td><?php echo h(format_datetime($log['created_at'])); ?></td>
                            <td><?php echo h($log['website_name']); ?></td>
                            <td><?php echo status_badge($log['status']); ?></td>
                            <td><?php echo $log['response_time'] !== null ? (int) $log['response_time'] . ' ms' : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
