<?php
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';
require_admin();

$type = clean_text($_GET['type'] ?? '', 20);
if (!in_array($type, ['contacts', 'statistiques'], true)) {
    http_response_code(422);
    exit('Type d’export invalide.');
}
$pdo = db();
if (!$pdo) {
    http_response_code(503);
    exit('Base de données indisponible.');
}

try {
    if ($type === 'contacts') {
        $rows = $pdo->query('SELECT id_demande, nom, email, telephone, type_projet, budget, message, canal_origine, date_soumission, statut FROM demande_contact ORDER BY date_soumission DESC')->fetchAll();
        $headers = ['ID', 'Nom', 'E-mail', 'Téléphone', 'Type de projet', 'Budget', 'Message', 'Canal', 'Date de soumission', 'Statut'];
        $filename = 'contacts-' . date('Y-m-d') . '.csv';
    } else {
        $rows = $pdo->query('SELECT p.nom AS page, p.type, COUNT(v.id_visite) AS vues, COUNT(DISTINCT CONCAT(v.ip_anonymisee, DATE(v.date_heure))) AS visiteurs_estimes FROM page p LEFT JOIN visite v ON v.id_page = p.id_page AND v.date_heure >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY p.id_page, p.nom, p.type ORDER BY vues DESC')->fetchAll();
        $headers = ['Page', 'Type', 'Vues sur 30 jours', 'Visiteurs estimés sur 30 jours'];
        $filename = 'statistiques-' . date('Y-m-d') . '.csv';
    }
    $pdo->prepare('INSERT INTO export (type, format, id_admin) VALUES (:type, "CSV", :admin)')->execute(['type' => $type, 'admin' => (int) $_SESSION['admin_id']]);
    security_log('Export CSV ' . $type, (int) $_SESSION['admin_id']);
} catch (Throwable $exception) {
    error_log('[portfolio] Export failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Export indisponible.');
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
echo "\xEF\xBB\xBF";
$output = fopen('php://output', 'wb');
fputcsv($output, $headers, ';');
foreach ($rows as $row) {
    fputcsv($output, array_values($row), ';');
}
fclose($output);
exit;
