function togglePassword(id) {
    var input = document.getElementById(id);
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
}

function tickClock() {
    var el = document.getElementById('liveClock');
    if (!el) return;
    var now = new Date();
    var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
    el.textContent = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate())
        + ' ' + pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
}

document.addEventListener('DOMContentLoaded', function () {
    tickClock();
    setInterval(tickClock, 1000);
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });
    }
    initWebsiteStatusPolling();
    initPublicUptimeList();
});

function initPublicUptimeList() {
    var list = document.getElementById('publicUptimeList');
    if (!list) return;
    var url = list.getAttribute('data-list-url');
    if (!url) return;

    function refreshList() {
        fetch(url, { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data) return;
                var cards = list.querySelectorAll('.ur-card').length;
                if (data.count !== cards) {
                    location.reload();
                }
            })
            .catch(function () {});
    }

    setInterval(refreshList, 15000);
    setInterval(function () { location.reload(); }, 45000);
}

function initWebsiteStatusPolling() {
    var table = document.getElementById('websitesTable');
    if (!table) return;
    var url = table.getAttribute('data-status-url');
    if (!url) return;

    function refreshStatuses() {
        fetch(url, { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || !data.sites) return;
                data.sites.forEach(function (site) {
                    var row = table.querySelector('[data-site-id="' + site.id + '"]');
                    if (!row) return;
                    var statusCell = row.querySelector('.site-status');
                    var responseCell = row.querySelector('.site-response');
                    var lastCell = row.querySelector('.site-last');
                    if (statusCell) statusCell.innerHTML = site.status_html;
                    if (responseCell) responseCell.textContent = site.response_time;
                    if (lastCell) lastCell.textContent = site.last_checked;
                });
            })
            .catch(function () {});
    }

    refreshStatuses();
    setInterval(refreshStatuses, 5000);
}
