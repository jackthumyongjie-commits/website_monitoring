<?php
require_once __DIR__ . '/../includes/auth.php';
logout_admin();
flash('success', lang('logout.done'));
redirect('/index.php');
