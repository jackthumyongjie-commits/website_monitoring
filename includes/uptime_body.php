<?php if (!empty($uptimeDetail) && empty($site)): ?>
    <div class="panel"><div class="empty"><?php echo h(lang('web.not_found')); ?></div></div>
<?php elseif (!empty($uptimeDetail) && !empty($site)): ?>
    <?php $st = strtolower((string) $site['status']); ?>
    <p class="muted" style="margin-top:-12px;margin-bottom:18px;">
        <a href="<?php echo h($uptimeBase); ?>"><?php echo h(lang('up.back')); ?></a>
    </p>

    <div class="ur-hero">
        <div>
            <span class="ur-dot <?php echo $st === 'up' ? 'up' : ($st === 'down' ? 'down' : 'unknown'); ?>"><?php echo h(lang('status.' . ($st === 'down' ? 'down' : ($st === 'up' ? 'up' : 'unknown')))); ?></span>
            <h2 style="margin:10px 0 4px;font-family:'Instrument Serif',Georgia,serif;font-size:2rem;font-weight:400;"><?php echo h($site['name']); ?></h2>
            <a href="<?php echo h($site['url']); ?>" target="_blank" rel="noopener"><?php echo h($site['url']); ?></a>
            <p class="now"><?php echo h(lang('up.http')); ?> · <?php echo h(lang('up.for')); ?> <?php echo h($sinceLabel); ?></p>
        </div>
        <div class="ur-right">
            <div class="pct"><?php echo h(format_uptime_pct($s90['pct'])); ?></div>
            <div class="lbl"><?php echo h(lang('up.90d')); ?> <?php echo h(lang('up.uptime')); ?></div>
        </div>
    </div>

    <div class="cards cards-3">
        <div class="stat-card"><div><p class="label"><?php echo h(lang('up.90d')); ?> <?php echo h(lang('up.uptime')); ?></p><div class="num"><?php echo h(format_uptime_pct($s90['pct'])); ?></div></div></div>
        <div class="stat-card"><div><p class="label"><?php echo h(lang('up.avg_rt_90d')); ?></p><div class="num"><?php echo $s90['avg_rt'] !== null ? (int) $s90['avg_rt'] . 'ms' : '—'; ?></div></div></div>
        <div class="stat-card"><div><p class="label"><?php echo h(lang('up.checks_90d')); ?></p><div class="num"><?php echo (int) $s90['total']; ?></div></div></div>
    </div>

    <section class="panel" style="margin-bottom:16px;">
        <div class="panel-head"><h3><?php echo h(lang('up.timeline_90d')); ?></h3></div>
        <div class="panel-body">
            <?php echo render_uptime_bar($ticks); ?>
            <p class="bar-legend"><span><?php echo h(lang('up.90d_ago')); ?></span><span><?php echo h(lang('up.today')); ?></span></p>
            <div class="rt-chart">
                <?php foreach ($ticks as $t): ?>
                    <?php
                    $stTick = strtoupper((string) ($t['status'] ?? 'UNKNOWN'));
                    $down = $stTick === 'DOWN';
                    $unknown = $stTick === 'UNKNOWN';
                    $hgt = $unknown ? 4 : ($down ? 8 : max(6, (int) round(((int) ($t['response_time'] ?? 0) / $maxRt) * 100)));
                    ?>
                    <span class="rt-col <?php echo $unknown ? 'none' : ($down ? 'down' : 'up'); ?>" style="height:<?php echo $hgt; ?>%" title="<?php echo h(($t['day_label'] ?? $t['created_at'] ?? '') . ' · ' . (int) ($t['response_time'] ?? 0) . ' ms'); ?>"></span>
                <?php endforeach; ?>
            </div>
            <p class="muted"><?php echo h(lang('up.chart_hint_90d')); ?></p>
        </div>
    </section>

    <div class="grid-2">
        <section class="panel">
            <div class="panel-head"><h3><?php echo h(lang('up.incidents_90d')); ?></h3></div>
            <?php if (!$incidents): ?>
                <div class="empty"><?php echo h(lang('up.no_incidents')); ?></div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <tr>
                            <th><?php echo h(lang('up.started')); ?></th>
                            <th><?php echo h(lang('up.ended')); ?></th>
                            <th><?php echo h(lang('up.duration')); ?></th>
                        </tr>
                        <?php foreach ($incidents as $inc): ?>
                            <tr>
                                <td><?php echo h(format_datetime($inc['started'])); ?></td>
                                <td><?php echo $inc['ongoing'] ? h(lang('up.ongoing')) : h(format_datetime($inc['ended'])); ?></td>
                                <td><?php echo h(format_duration((int) $inc['seconds'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>
        </section>
        <section class="panel">
            <div class="panel-head"><h3><?php echo h(lang('up.recent_checks_90d')); ?></h3></div>
            <?php if (!$checks): ?>
                <div class="empty"><?php echo h(lang('log.empty')); ?></div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <tr>
                            <th><?php echo h(lang('log.checked_at')); ?></th>
                            <th><?php echo h(lang('col.status')); ?></th>
                            <th><?php echo h(lang('col.response')); ?></th>
                        </tr>
                        <?php foreach ($checks as $log): ?>
                            <tr>
                                <td><?php echo h(format_datetime($log['created_at'])); ?></td>
                                <td><?php echo status_badge($log['status']); ?></td>
                                <td><?php echo $log['response_time'] !== null ? (int) $log['response_time'] . ' ms' : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
<?php else: ?>
    <?php if (empty($uptimePublic)): ?>
        <p class="muted public-share-hint" style="margin-top:-8px;margin-bottom:8px;">
            <?php echo h(lang('up.public_link')); ?>:
            <a href="<?php echo h(BASE_URL . '/uptime/'); ?>" target="_blank" rel="noopener"><?php echo h(BASE_URL . '/uptime/'); ?></a>
        </p>
        <p class="muted public-share-hint" style="margin-top:0;margin-bottom:16px;">
            <?php echo h(lang('up.admin_link')); ?>:
            <a href="<?php echo h(BASE_URL . '/admin/uptime.php'); ?>"><?php echo h(BASE_URL . '/admin/uptime.php'); ?></a>
        </p>
    <?php endif; ?>
    <p class="muted" style="margin-top:-8px;margin-bottom:16px;"><?php echo h(!empty($uptimePublic) ? lang('up.list_hint_public') : lang('up.list_hint_90d')); ?></p>
    <?php if (!$sites): ?>
        <div class="panel"><div class="empty"><?php echo h(lang('web.empty')); ?></div></div>
    <?php else: ?>
        <div class="ur-list"<?php if (!empty($uptimePublic)): ?> id="publicUptimeList" data-list-url="<?php echo h(BASE_URL . '/uptime/list-data.php'); ?>"<?php endif; ?>>
            <?php foreach ($sites as $site): ?>
                <?php
                $sid = (int) $site['id'];
                $st = strtolower((string) $site['status']);
                $dot = $st === 'up' ? 'up' : ($st === 'down' ? 'down' : 'unknown');
                ?>
                <a class="ur-card ur-card-90" href="<?php echo h(uptime_detail_url($uptimeBase, $sid)); ?>">
                    <div class="ur-left">
                        <span class="ur-dot <?php echo $dot; ?>"><?php echo h(lang('status.' . ($dot === 'unknown' ? 'unknown' : $dot))); ?></span>
                        <span class="ur-type"><?php echo h(lang('up.http')); ?></span>
                    </div>
                    <div class="ur-mid">
                        <div class="ur-meta">
                            <h3><?php echo h($site['name']); ?></h3>
                            <span class="url"><?php echo h($site['url']); ?></span>
                        </div>
                        <?php echo render_uptime_bar($bars[$sid] ?? []); ?>
                        <p class="hint"><?php echo h(lang('up.timeline_90d_short')); ?></p>
                    </div>
                    <div class="ur-right">
                        <div class="pct"><?php echo h(format_uptime_pct($pct90[$sid] ?? null)); ?></div>
                        <div class="lbl"><?php echo h(lang('up.90d')); ?> <?php echo h(lang('up.uptime')); ?></div>
                        <div class="ms"><?php echo $site['response_time'] !== null ? (int) $site['response_time'] . ' ms' : '—'; ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
