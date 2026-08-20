<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$uptimePublic = true;
$uptimeBase = BASE_URL . '/uptime/';

require __DIR__ . '/../includes/uptime_bootstrap.php';
require __DIR__ . '/../includes/public_header.php';
require __DIR__ . '/../includes/uptime_body.php';
require __DIR__ . '/../includes/public_footer.php';
