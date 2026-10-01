<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/admin-auth.php';

logout_admin();
set_flash('info', 'Admin session terminated.');
redirect('/admin/login.php');
