<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

function admin_header(string $title, string $active = 'overview'): void
{
    $admin = is_admin();
    ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <link rel="stylesheet" href="<?= e(site_url('assets/css/style.css')) ?>">
  <link rel="stylesheet" href="<?= e(site_url('assets/css/dashboard.css')) ?>">
  <title><?= e($title) ?> · Administration</title>
</head>
<body class="admin-body">
<?php if ($admin): ?>
  <div class="dashboard-layout">
    <aside class="dashboard-sidebar" aria-label="Navigation du tableau de bord">
      <a class="dashboard-brand" href="<?= e(site_url('admin/')) ?>"><img class="dashboard-profile-avatar" src="<?= e(site_image_url(configured_profile_image())) ?>" alt="" width="42" height="42"><span>AKM</span><b>Dashboard</b></a>
      <p class="dashboard-caption">Espace de pilotage</p>
      <nav class="dashboard-nav">
        <a class="<?= $active === 'overview' ? 'is-active' : '' ?>" href="<?= e(site_url('admin/')) ?>"><i>⌂</i> Vue d’ensemble</a>
        <a class="<?= $active === 'requests' ? 'is-active' : '' ?>" href="<?= e(site_url('admin/demandes.php')) ?>"><i>✉</i> Boîte de réception</a>
        <a class="<?= $active === 'projects' ? 'is-active' : '' ?>" href="<?= e(site_url('admin/projects.php')) ?>"><i>◈</i> Mes projets</a>
        <a class="<?= $active === 'clients' ? 'is-active' : '' ?>" href="<?= e(site_url('admin/clients.php')) ?>"><i>◌</i> Partenaires</a>
        <a class="<?= $active === 'settings' ? 'is-active' : '' ?>" href="<?= e(site_url('admin/settings.php')) ?>"><i>⚙</i> Paramètres</a>
        <a class="<?= $active === 'seo' ? 'is-active' : '' ?>" href="<?= e(site_url('admin/seo.php')) ?>"><i>◎</i> SEO</a>
      </nav>
      <div class="dashboard-sidebar-footer">
        <a href="<?= e(site_url()) ?>" target="_blank" rel="noopener">Voir le site ↗</a>
        <a href="<?= e(site_url('admin/logout.php')) ?>">Se déconnecter</a>
      </div>
    </aside>
    <section class="dashboard-stage">
      <header class="dashboard-topbar"><div class="dashboard-topbar-identity"><img class="dashboard-profile-avatar dashboard-profile-avatar--top" src="<?= e(site_image_url(configured_profile_image())) ?>" alt="" width="38" height="38"><div><span class="dashboard-kicker">Bonjour <?= e((string) ($_SESSION['admin_name'] ?? '')) ?></span><strong><?= e($title) ?></strong></div></div><a class="topbar-site-link" href="<?= e(site_url()) ?>" target="_blank" rel="noopener">Ouvrir le site ↗</a></header>
<?php else: ?>
  <header class="auth-topbar"><a class="dashboard-brand" href="<?= e(site_url()) ?>"><span>AKM</span><b>Dashboard</b></a><a href="<?= e(site_url()) ?>">← Retour au site</a></header>
<?php endif; ?>
<?php
}

function admin_footer(): void
{
    if (is_admin()) {
        echo '</section></div>';
    }
    echo '</body></html>';
}

function request_statuses(): array
{
    return ['Non traité', 'En cours', 'Converti', 'Sans suite'];
}
