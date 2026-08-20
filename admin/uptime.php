<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$uptimePublic = false;
$uptimeBase = BASE_URL . '/admin/uptime.php';

require __DIR__ . '/../includes/uptime_bootstrap.php';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/uptime_body.php';
require __DIR__ . '/../includes/footer.php';
