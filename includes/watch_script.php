<?php
$watchMs = max(5000, (int) CRON_INTERVAL_SECONDS * 1000);
?>
<script>
(function () {
    var cronUrl = '<?php echo BASE_URL; ?>/cron/web-cron.php';
    var ms = <?php echo $watchMs; ?>;
    function ping() { fetch(cronUrl, { cache: 'no-store' }).catch(function () {}); }
    ping();
    setInterval(ping, ms);
})();
</script>
