<?php
declare(strict_types=1);
require __DIR__ . '/../bootstrap.php';
if (is_admin()) security_log('Déconnexion administrateur', (int) $_SESSION['admin_id']);
$_SESSION = []; session_destroy(); header("Location: " . site_url("admin/login.php"));
