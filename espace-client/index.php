<?php
declare(strict_types=1);

require __DIR__ . '/../includes/admin.php';
require_client();
$requests = []; $client = null;
try {
    $pdo = db();
    $clientQuery = $pdo->prepare('SELECT nom, email, telephone, date_creation FROM client_partenaire WHERE id_client = :id');
    $clientQuery->execute(['id' => (int) $_SESSION['client_id']]); $client = $clientQuery->fetch();
    $requestQuery = $pdo->prepare('SELECT type_projet, budget, message, date_soumission, statut FROM demande_contact WHERE id_client = :id ORDER BY date_soumission DESC');
    $requestQuery->execute(['id' => (int) $_SESSION['client_id']]); $requests = $requestQuery->fetchAll();
} catch (Throwable $exception) { error_log('[portfolio] Client area: ' . $exception->getMessage()); }
admin_header('Mon espace');
?>
<main class="client-page"><div class="client-box"><p class="eyebrow"><span></span> Espace partenaire</p><h1>Bonjour <?= e((string) ($_SESSION['client_name'] ?? '')) ?>.</h1><p>Voici l’historique des demandes associées à votre compte.</p><section class="admin-panel"><h2>Mes coordonnées</h2><p><strong><?= e((string) ($client['email'] ?? '')) ?></strong><?= !empty($client['telephone']) ? ' · ' . e($client['telephone']) : '' ?></p></section><section class="admin-panel"><h2>Mes demandes</h2><div class="client-requests"><?php foreach ($requests as $request): ?><article class="client-request"><p class="case-index"><?= e(date('d/m/Y', strtotime($request['date_soumission']))) ?> · <span class="status status--<?= e(str_replace(' ', '-', $request['statut'])) ?>"><?= e($request['statut']) ?></span></p><h2><?= e((string) $request['type_projet']) ?></h2><p><?= nl2br(e($request['message'])) ?></p></article><?php endforeach; ?><?php if (!$requests): ?><p>Aucune demande n’est encore associée à votre espace.</p><?php endif; ?></div></section><p><a class="admin-button admin-button--light" href="<?= e(site_url('espace-client/logout.php')) ?>">Se déconnecter</a></p></div></main>
<?php admin_footer(); ?>
