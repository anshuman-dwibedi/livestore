<?php
require_once dirname(__DIR__) . '/core/bootstrap.php';
Auth::logout();
$base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/api/logout.php')), '/');
header('Location: ' . $base . '/admin/login.php');
exit;
