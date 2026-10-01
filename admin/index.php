<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

if (is_admin_logged_in()) {
    redirect('/admin/dashboard.php');
} else {
    redirect('/admin/login.php');
}
