<?php
declare(strict_types=1);
require __DIR__ . '/../bootstrap.php';
unset($_SESSION['client_id'], $_SESSION['client_name']); session_regenerate_id(true); header("Location: " . site_url("espace-client/login.php"));
