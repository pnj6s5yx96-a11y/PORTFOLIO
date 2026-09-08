<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(204); exit;
}
$path = parse_url((string) ($_POST['path'] ?? ''), PHP_URL_PATH) ?: '';
$projectId = filter_var($_POST['project_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
$pages = ['/' => ['Accueil', 'statique'], '/a-propos.php' => ['À propos', 'statique'], '/projets.php' => ['Projets', 'statique'], '/projet.php' => ['Détail projet', 'detail-projet'], '/services.php' => ['Services', 'statique'], '/contact.php' => ['Contact', 'statique']];
if (!isset($pages[$path]) || !empty($_SESSION['tracked_paths'][$path])) { http_response_code(204); exit; }
$pdo = db();
if (!$pdo) { http_response_code(204); exit; }
try {
    [$name, $type] = $pages[$path];
    if ($type !== 'detail-projet') $projectId = null;
    if ($projectId !== null) {
        $exists = $pdo->prepare('SELECT 1 FROM projet WHERE id_projet = :id');
        $exists->execute(['id' => $projectId]);
        if (!$exists->fetchColumn()) $projectId = null;
    }
    $page = $pdo->prepare('SELECT id_page FROM page WHERE nom = :name AND type = :type AND (id_projet <=> :project) LIMIT 1');
    $page->execute(['name' => $name, 'type' => $type, 'project' => $projectId]);
    $pageId = $page->fetchColumn();
    if (!$pageId) {
        $pdo->prepare('INSERT INTO page (nom, type, id_projet) VALUES (:name, :type, :project)')->execute(['name' => $name, 'type' => $type, 'project' => $projectId]);
        $pageId = $pdo->lastInsertId();
    }
    $pdo->prepare('INSERT INTO visite (ip_anonymisee, id_page) VALUES (:ip, :page)')->execute(['ip' => anonymised_ip(), 'page' => $pageId]);
    $_SESSION['tracked_paths'][$path] = true;
} catch (Throwable $exception) { error_log('[portfolio] Tracking failed: ' . $exception->getMessage()); }
http_response_code(204);
