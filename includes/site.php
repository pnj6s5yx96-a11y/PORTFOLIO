<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/project-visual.php';

function nav_item(string $href, string $label, string $active): string
{
    $current = $active === $href ? ' aria-current="page"' : '';
    return '<a href="' . e(site_url(ltrim($href, '/'))) . '"' . $current . '>' . e($label) . '</a>';
}

function site_header(string $title, string $description, string $active = ''): void
{
    $fullTitle = $title === 'Accueil' ? config('owner') . ' — Développeur web freelance' : $title . ' | ' . config('owner');
    $canonical = site_url(ltrim(current_path(), '/'));
    $locality = trim((string) config('seo_locality'));
    if ($locality !== '' && $title === 'Accueil') {
        $fullTitle .= ' à ' . $locality;
    }
    $sameAs = [];
    ?>
<!doctype html>
<html lang="fr" data-theme="light" data-app-base="<?= e(app_base()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= e($description) ?>">
  <meta name="robots" content="index,follow">
  <meta name="theme-color" content="#f0f7f5">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <link rel="icon" href="<?= e(site_url('assets/favicon.svg')) ?>" type="image/svg+xml">
  <link rel="preconnect" href="https://images.unsplash.com" crossorigin>
  <link rel="stylesheet" href="<?= e(site_url('assets/css/style.css')) ?>">
  <link rel="stylesheet" href="<?= e(site_url('assets/css/project-capture.css')) ?>">
  <link rel="stylesheet" href="<?= e(site_url('assets/css/project-links.css')) ?>">
  <link rel="stylesheet" href="<?= e(site_url('assets/css/immersive-home.css')) ?>">
  <link rel="stylesheet" href="<?= e(site_url('assets/css/site-redesign.css')) ?>">
  <title><?= e($fullTitle) ?></title>
  <script nonce="<?= e(csp_nonce()) ?>" type="application/ld+json"><?= json_encode(array_filter([
      '@context' => 'https://schema.org',
      '@graph' => [
          array_filter([
              '@type' => 'Person',
              'name' => config('owner'),
              'url' => site_url(),
              'jobTitle' => 'Développeur web freelance',
              'email' => config('email'),
              'sameAs' => $sameAs,
          ]),
          array_filter([
              '@type' => 'ProfessionalService',
              'name' => config('owner') . ' — Développement web',
              'url' => site_url(),
              'email' => config('email'),
              'areaServed' => $locality !== '' ? $locality : null,
              'description' => 'Création de sites vitrines, applications web et plateformes métier sur mesure.',
          ]),
      ],
  ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body>
  <a class="skip-link" href="#main">Aller au contenu principal</a>
  <header class="site-header" data-header>
    <div class="shell nav-wrap">
      <a class="brand" href="<?= e(site_url()) ?>" aria-label="Accueil <?= e(config('owner')) ?>">
        <span class="brand-mark brand-mark--akm" aria-hidden="true">AKM</span>
        <span>AKAKPOSSE <span class="brand-firstname">Michael</span></span>
      </a>
      <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav" data-menu-toggle>
        <span class="sr-only">Ouvrir le menu</span><span></span><span></span>
      </button>
      <nav id="primary-nav" class="primary-nav" aria-label="Navigation principale" data-menu>
        <?= nav_item('/', 'Accueil', $active) ?>
        <?= nav_item('/a-propos.php', 'À propos', $active) ?>
        <?= nav_item('/projets.php', 'Projets', $active) ?>
        <?= nav_item('/services.php', 'Services', $active) ?>
        <?= nav_item('/contact.php', 'Contact', $active) ?>
      </nav>
      <div class="nav-actions">
        <button class="theme-toggle" type="button" data-theme-toggle aria-label="Activer le thème clair" title="Changer de thème">
          <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3v2m0 14v2M3 12h2m14 0h2m-2.64-6.36-1.42 1.42M7.05 16.95l-1.42 1.42m0-12.72 1.42 1.42m9.9 9.9 1.42 1.42M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/></svg>
        </button>
        <a class="button button--small button--nav" href="<?= e(site_url('contact.php')) ?>">Parlons projet <span aria-hidden="true">↗</span></a>
      </div>
    </div>
  </header>
  <main id="main">
    <?php
}

function site_footer(): void
{
    $year = date('Y');
    $trackedProjectId = $GLOBALS['current_project_id'] ?? null;
    ?>
  </main>
  <a class="whatsapp-float" href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener noreferrer" aria-label="Contacter Michael sur WhatsApp">
    <svg aria-hidden="true" viewBox="0 0 32 32"><path d="M16 3.2A12.6 12.6 0 0 0 5.1 22.1L3.5 28.5l6.6-1.7A12.7 12.7 0 1 0 16 3.2Zm0 22.9a10.2 10.2 0 0 1-5.2-1.4l-.4-.2-3.9 1 1-3.8-.3-.4a10.3 10.3 0 1 1 8.8 4.8Zm5.6-7.7c-.3-.1-1.8-.9-2.1-1s-.5-.1-.8.2-.9 1-1.1 1.2-.4.3-.7.1a8.3 8.3 0 0 1-2.5-1.6 9.1 9.1 0 0 1-1.7-2.2c-.2-.3 0-.5.1-.6l.5-.6.3-.5c.1-.2 0-.4 0-.5l-.9-2.1c-.2-.5-.5-.4-.7-.4h-.6c-.2 0-.5.1-.8.4s-1 1-1 2.4 1 2.8 1.2 3 .9 1.5 2.2 2.5c.3.3 2.2 1.8 4.2 2.4.5.2.9.3 1.2.4.5.1 1 .1 1.4.1.4-.1 1.8-.7 2-1.4s.3-1.3.2-1.4-.2-.2-.5-.3Z"/></svg>
    <span>WhatsApp</span>
  </a>
  <footer class="site-footer">
    <div class="shell footer-grid">
      <div>
        <a class="brand" href="<?= e(site_url()) ?>" aria-label="Retour à l’accueil"><span class="brand-mark brand-mark--akm" aria-hidden="true">AKM</span><span>AKAKPOSSE <span class="brand-firstname">Michael</span></span></a>
        <p class="footer-intro">Sites vitrines et plateformes métier qui font avancer les projets avec clarté.</p>
      </div>
      <div>
        <p class="footer-title">Explorer</p>
        <a href="<?= e(site_url('projets.php')) ?>">Projets</a><a href="<?= e(site_url('services.php')) ?>">Services</a><a href="<?= e(site_url('a-propos.php')) ?>">À propos</a>
      </div>
      <div>
        <p class="footer-title">Contact</p>
        <a href="mailto:<?= e(config('email')) ?>"><?= e(config('email')) ?></a>
        <a href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener noreferrer">WhatsApp ↗</a>
        <a href="<?= e(site_url('contact.php')) ?>">Demander un devis</a>
      </div>
      <div>
        <p class="footer-title">Réseaux</p>
        <a href="#" aria-label="Ajouter votre profil GitHub dans includes/site.php">GitHub ↗</a>
        <a href="#" aria-label="Ajouter votre profil LinkedIn dans includes/site.php">LinkedIn ↗</a>
        <a href="<?= e(site_url('mentions-legales.php')) ?>">Mentions & confidentialité</a>
      </div>
    </div>
    <div class="shell footer-bottom"><span>© <?= e($year) ?> <?= e(config('owner')) ?>.</span><span>Conçu avec exigence, du premier pixel à la mise en ligne.</span></div>
  </footer>
  <script nonce="<?= e(csp_nonce()) ?>">window.PORTFOLIO = {csrf: '<?= e(csrf_token()) ?>', trackUrl: '<?= e(site_url('api/track.php')) ?>', projectUrl: '<?= e(site_url('projet.php')) ?>', projectId: <?= $trackedProjectId ? (int) $trackedProjectId : 'null' ?>};</script>
  <script src="<?= e(site_url('assets/js/app.js')) ?>" defer></script>
</body>
</html>
<?php
}
