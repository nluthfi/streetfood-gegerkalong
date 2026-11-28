<?php
require_once __DIR__ . '/../app/config/app.php';
require_admin();

header('Location: ' . app_url('admin/dashboard.php'));
exit;
