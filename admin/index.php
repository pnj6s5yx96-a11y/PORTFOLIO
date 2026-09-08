<?php
declare(strict_types=1);

require __DIR__ . '/../includes/admin.php';
require_admin();
$pdo = db();
$stats = ['new' => 0, 'progress' => 0, 'projects' => 0, 'clients' => 0];
$recent = [];
$pages = [];
$visibility = [];
$today = new DateTimeImmutable('today');
for ($offset = 6; $offset >= 0; $offset--) {
    $day = $today->modify("-{$offset} days");
    $visibility[$day->format('Y-m-d')] = ['label' => $day->format('d/m'), 'views' => 0];
}
try {
    if (!$pdo) {
        throw new RuntimeException('Base indisponible');
    }
    $stats['new'] = (int) $pdo->query('SELECT COUNT(*) FROM demande_contact WHERE statut = "Non traité"')->fetchColumn();
    $stats['progress'] = (int) $pdo->query('SELECT COUNT(*) FROM demande_contact WHERE statut = "En cours"')->fetchColumn();
    $stats['projects'] = (int) $pdo->query('SELECT COUNT(*) FROM projet')->fetchColumn();
    $stats['clients'] = (int) $pdo->query('SELECT COUNT(*) FROM client_partenaire')->fetchColumn();
    $recent = $pdo->query('SELECT id_demande, nom, email, type_projet, budget, date_soumission, statut FROM demande_contact ORDER BY date_soumission DESC LIMIT 6')->fetchAll();
    $pages = $pdo->query('SELECT p.nom, COUNT(v.id_visite) AS views FROM page p LEFT JOIN visite v ON v.id_page = p.id_page AND v.date_heure >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY p.id_page, p.nom ORDER BY views DESC LIMIT 5')->fetchAll();
    $dailyRows = $pdo->query('SELECT DATE(date_heure) AS day, COUNT(*) AS views FROM visite WHERE date_heure >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(date_heure)')->fetchAll();
    foreach ($dailyRows as $row) {
        if (isset($visibility[$row['day']])) {
            $visibility[$row['day']]['views'] = (int) $row['views'];
        }
    }
} catch (Throwable $exception) {
    error_log('[portfolio] Dashboard: ' . $exception->getMessage());
}
$visibility = array_values($visibility);
$maxViews = max(1, ...array_column($visibility, 'views'));
$totalViews = array_sum(array_column($visibility, 'views'));
$points = [];
foreach ($visibility as $index => $day) {
    $x = 30 + ($index * (440 / max(1, count($visibility) - 1)));
    $y = 148 - (($day['views'] / $maxViews) * 104);
    $points[] = ['x' => round($x, 1), 'y' => round($y, 1), 'label' => $day['label'], 'views' => $day['views']];
}
$polyline = implode(' ', array_map(static fn(array $point): string => $point['x'] . ',' . $point['y'], $points));
$areaPath = 'M30,148 L' . implode(' L', array_map(static fn(array $point): string => $point['x'] . ',' . $point['y'], $points)) . ' L470,148 Z';
$topPageViews = max(1, ...array_map(static fn(array $page): int => (int) $page['views'], $pages));
admin_header('Vue d’ensemble', 'overview');
?>
<main class="admin-main"><div class="admin-shell">
  <div class="admin-title-row"><div><p class="eyebrow"><span></span> Ton espace de travail</p><h1>Tout est sous contrôle.</h1><p>Retrouve les actions essentielles de ton portfolio, sans passer par phpMyAdmin.</p></div><a class="admin-button admin-button--coral" href="<?= e(site_url('admin/demandes.php')) ?>">Voir les demandes <span style="margin-left:7px">→</span></a></div>
  <section class="admin-stats"><article class="admin-stat"><span>Nouvelles demandes</span><b><?= e((string) $stats['new']) ?></b></article><article class="admin-stat"><span>Conversations en cours</span><b><?= e((string) $stats['progress']) ?></b></article><article class="admin-stat"><span>Projets publiés</span><b><?= e((string) $stats['projects']) ?></b></article><article class="admin-stat"><span>Partenaires</span><b><?= e((string) $stats['clients']) ?></b></article></section>
  <section class="admin-panel"><h2>Actions rapides</h2><div class="quick-actions"><a class="quick-action" href="<?= e(site_url('admin/demandes.php')) ?>"><span>Boîte de réception</span><b>Répondre à un prospect →</b></a><a class="quick-action" href="<?= e(site_url('admin/projects.php')) ?>"><span>Portfolio</span><b>Ajouter un projet →</b></a><a class="quick-action" href="<?= e(site_url('admin/clients.php')) ?>"><span>Relations client</span><b>Créer un espace partenaire →</b></a></div></section>
  <div class="dashboard-grid">
    <section class="admin-panel"><div class="panel-heading"><h2>Dernières demandes</h2><a class="topbar-site-link" href="<?= e(site_url('admin/demandes.php')) ?>">Tout afficher</a></div><table class="admin-table"><thead><tr><th>Prospect</th><th>Projet</th><th>Date</th><th>Statut</th></tr></thead><tbody><?php foreach ($recent as $request): ?><tr><td><a href="<?= e(site_url('admin/demandes.php?id=' . (int) $request['id_demande'])) ?>"><strong><?= e($request['nom']) ?></strong></a><br><small><?= e($request['email']) ?></small></td><td><?= e((string) $request['type_projet']) ?><br><small><?= e((string) $request['budget']) ?></small></td><td><?= e(date('d/m', strtotime($request['date_soumission']))) ?></td><td><span class="status status--<?= e(str_replace(' ', '-', $request['statut'])) ?>"><?= e($request['statut']) ?></span></td></tr><?php endforeach; ?><?php if (!$recent): ?><tr><td colspan="4">Aucune demande reçue pour le moment.</td></tr><?php endif; ?></tbody></table></section>
    <aside class="dashboard-note"><p class="eyebrow eyebrow--light"><span></span> À garder en tête</p><h2>Répondre vite crée la confiance.</h2><p>Les nouvelles demandes, leurs coordonnées et les réponses sont centralisées dans ta boîte de réception.</p><a href="<?= e(site_url('admin/demandes.php')) ?>" class="text-link">Ouvrir la boîte de réception →</a></aside>
  </div>
  <section class="admin-panel visibility-panel"><div class="panel-heading"><div><p class="eyebrow"><span></span> Visibilité des pages</p><h2>Votre audience, jour après jour.</h2></div><a class="topbar-site-link" href="<?= e(site_url('admin/export.php?type=statistiques')) ?>">Exporter CSV</a></div><div class="visibility-layout"><div class="chart-wrap"><div class="chart-summary"><b><?= e((string) $totalViews) ?></b><span>vues sur les 7 derniers jours</span></div><svg class="visibility-chart" viewBox="0 0 500 188" role="img" aria-label="Évolution des vues sur les sept derniers jours"><defs><linearGradient id="view-area" x1="0" x2="0" y1="0" y2="1"><stop stop-color="#77b8b0" stop-opacity=".34"/><stop offset="1" stop-color="#77b8b0" stop-opacity="0"/></linearGradient></defs><path class="chart-grid" d="M30 42H470M30 95H470M30 148H470"/><path class="chart-area" d="<?= e($areaPath) ?>"/><polyline class="chart-line" points="<?= e($polyline) ?>"/><?php foreach ($points as $point): ?><circle class="chart-dot" cx="<?= e((string) $point['x']) ?>" cy="<?= e((string) $point['y']) ?>" r="4"><title><?= e($point['label'] . ' : ' . $point['views'] . ' vues') ?></title></circle><text class="chart-label" x="<?= e((string) $point['x']) ?>" y="174" text-anchor="middle"><?= e($point['label']) ?></text><?php endforeach; ?></svg></div><div class="page-visibility-list"><h3>Pages les plus vues</h3><?php foreach ($pages as $page): ?><div class="page-visibility-row"><div><b><?= e($page['nom']) ?></b><span><?= e((string) $page['views']) ?> vues</span></div><i><em style="width:<?= e((string) round(((int) $page['views'] / $topPageViews) * 100)) ?>%"></em></i></div><?php endforeach; ?><?php if (!$pages): ?><p>Les visites apparaîtront ici dès que le site aura été parcouru.</p><?php endif; ?></div></div></section>
</div></main>
<?php admin_footer(); ?>
