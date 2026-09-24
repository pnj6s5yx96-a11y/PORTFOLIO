<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/project-visual.php';

function page_accent_key(string $active): string
{
    $path = strtolower(trim($active, '/'));
    $path = preg_replace('#^en/#', '', $path);
    $path = preg_replace('#\.php$#', '', $path);
    $map = [
        '' => 'home', 'index' => 'home',
        'a-propos' => 'about', 'about' => 'about',
        'services' => 'services',
        'projets' => 'projects', 'projects' => 'projects',
        'contact' => 'contact',
        'blog' => 'blog', 'article' => 'blog',
        'mentions-legales' => 'legal', 'legal' => 'legal',
    ];
    return $map[$path] ?? 'home';
}

function nav_item(string $href, string $label, string $active): string
{
    $current = $active === $href ? ' aria-current="page"' : '';
    return '<a href="' . e(site_url(ltrim($href, '/'))) . '"' . $current . '>' . e($label) . '</a>';
}

function site_meta_defaults(string $title, string $description, string $lang = 'fr'): array
{
    $owner = (string) config('owner');
    $locality = trim((string) config('seo_locality'));
    $country = trim((string) config('seo_country'));
    $suffix = $lang === 'en' ? ' | ' . $owner : ' | ' . $owner;
    $place = $locality !== '' ? $locality : 'Cotonou';
    $countryLabel = $country !== '' ? $country : 'Bénin';
    if ($lang === 'en' && $countryLabel === 'Bénin') { $countryLabel = 'Benin'; }
    $fullTitle = $title === 'Accueil' || $title === 'Home'
        ? ($lang === 'en' ? 'Freelance Web Developer in ' . $place . ', ' . $countryLabel : 'Développeur web freelance à ' . $place . ', ' . $countryLabel) . ' | ' . $owner
        : $title . $suffix;
    return [$fullTitle, $locality, $country];
}

function site_header(string $title, string $description, string $active = '', array $options = []): void
{
    $lang = ($options['lang'] ?? 'fr') === 'en' ? 'en' : 'fr';
    $GLOBALS['site_lang'] = $lang;
    $noindex = (bool) ($options['noindex'] ?? false) || (string) config('environment') === 'local';
    $image = (string) ($options['image'] ?? configured_hero_image());
    $pageType = (string) ($options['page_type'] ?? 'WebPage');
    $canonical = (string) ($options['canonical'] ?? site_url(ltrim(current_path(), '/')));
    $alternates = is_array($options['alternates'] ?? null) ? $options['alternates'] : [];
    [$fullTitle, $locality, $country] = site_meta_defaults($title, $description, $lang);
    $robots = $noindex ? 'noindex,nofollow' : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1';
    $sameAs = array_values(array_filter([config('github_url'), config('linkedin_url')]));
    $imageUrl = site_image_url($image);
    $keywords = seo_keywords($lang);
    $graph = [
        [
            '@type' => 'WebSite',
            '@id' => site_url('#website'),
            'url' => site_url(),
            'name' => config('owner') . ' — Développement web',
            'inLanguage' => $lang,
        ],
        [
            '@type' => 'Person',
            '@id' => site_url('#person'),
            'name' => config('owner'),
            'url' => site_url(),
            'jobTitle' => $lang === 'en' ? 'Freelance Web Developer' : 'Développeur web freelance',
            'email' => config('email'),
            'image' => $imageUrl,
            'sameAs' => $sameAs,
            'knowsAbout' => $keywords,
        ],
        [
            '@type' => 'ProfessionalService',
            '@id' => site_url('#business'),
            'name' => config('owner') . ' — Développement web',
            'url' => site_url(),
            'email' => config('email'),
            'description' => $lang === 'en'
                ? 'Custom website, web application and e-commerce development for businesses in Benin and internationally.'
                : 'Création de sites web, applications web et solutions e-commerce sur mesure au Bénin et à l’international.',
            'areaServed' => array_values(array_filter([
                $locality !== '' ? ['@type' => 'City', 'name' => $locality] : null,
                $country !== '' ? ['@type' => 'Country', 'name' => $country] : null,
                ['@type' => 'Place', 'name' => 'International'],
            ])),
            'image' => $imageUrl,
            'sameAs' => $sameAs,
            'serviceType' => $lang === 'en' ? ['Website development', 'Web application development', 'E-commerce development'] : ['Création de sites web', 'Développement d’applications web', 'Création de sites e-commerce'],
        ],
        [
            '@type' => $pageType,
            '@id' => $canonical . '#webpage',
            'url' => $canonical,
            'name' => $fullTitle,
            'description' => $description,
            'inLanguage' => $lang,
            'isPartOf' => ['@id' => site_url('#website')],
            'about' => ['@id' => site_url('#person')],
            'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => $imageUrl],
        ],
    ];
    foreach (($options['schema_extra'] ?? []) as $extraNode) {
        if (is_array($extraNode) && isset($extraNode['@type'])) {
            $graph[] = $extraNode;
        }
    }
    if ($options['breadcrumb'] ?? true) {
        $items = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => $lang === 'en' ? 'Home' : 'Accueil', 'item' => page_url('home', $lang)],
        ];
        if ($title !== 'Accueil' && $title !== 'Home') {
            $items[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $title, 'item' => $canonical];
        }
        $graph[] = ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }
    ?>
<!doctype html>
<html lang="<?= e($lang) ?>" data-theme="light" data-app-base="<?= e(app_base()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= e($description) ?>">
  <meta name="robots" content="<?= e($robots) ?>">
  <meta name="author" content="<?= e((string) config('owner')) ?>">
  <meta name="keywords" content="<?= e(implode(', ', $keywords)) ?>">
  <meta name="theme-color" content="#f0f7f5">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <link rel="alternate" hreflang="fr" href="<?= e((string) ($alternates['fr'] ?? ($lang === 'fr' ? $canonical : site_url()))) ?>">
  <link rel="alternate" hreflang="en" href="<?= e((string) ($alternates['en'] ?? ($lang === 'en' ? $canonical : site_url('en/')))) ?>">
  <link rel="alternate" hreflang="x-default" href="<?= e((string) ($alternates['x-default'] ?? ($alternates['fr'] ?? site_url()))) ?>">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="<?= e($lang === 'en' ? 'en_US' : 'fr_FR') ?>">
  <meta property="og:title" content="<?= e($fullTitle) ?>">
  <meta property="og:description" content="<?= e($description) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:site_name" content="<?= e((string) config('owner')) ?> — Développement web">
  <meta property="og:image" content="<?= e($imageUrl) ?>">
  <meta property="og:image:alt" content="<?= e($fullTitle) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($fullTitle) ?>">
  <meta name="twitter:description" content="<?= e($description) ?>">
  <meta name="twitter:image" content="<?= e($imageUrl) ?>">
  <link rel="icon" href="<?= e(site_url('assets/favicon.svg')) ?>" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap">
  <link rel="stylesheet" href="<?= e(site_url('assets/css/site.css')) ?>">
  <title><?= e($fullTitle) ?></title>
  <script nonce="<?= e(csp_nonce()) ?>" type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
</head>
<body data-page="<?= e(page_accent_key($active)) ?>">
  <a class="skip-link" href="#main"><?= $lang === 'en' ? 'Skip to main content' : 'Aller au contenu principal' ?></a>
  <header class="site-header" data-header>
    <div class="shell nav-wrap">
      <a class="brand" href="<?= e(site_url($lang === 'en' ? 'en/index.php' : 'index.php')) ?>" aria-label="<?= e($lang === 'en' ? 'Home ' . config('owner') : 'Accueil ' . config('owner')) ?>">
        <img class="header-profile-avatar" src="<?= e(site_image_url(configured_profile_image())) ?>" alt="" width="38" height="38">
        <span class="brand-mark brand-mark--akm" aria-hidden="true">AKM</span>
        <span>AKAKPOSSE <span class="brand-firstname">Michael</span></span>
      </a>
      <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav" data-menu-toggle>
        <span class="sr-only"><?= $lang === 'en' ? 'Open menu' : 'Ouvrir le menu' ?></span><span></span><span></span>
      </button>
      <nav id="primary-nav" class="primary-nav" aria-label="<?= e($lang === 'en' ? 'Main navigation' : 'Navigation principale') ?>" data-menu>
        <?php if ($lang === 'en'): ?>
          <?= nav_item('/en/index.php', 'Home', $active) ?>
          <?= nav_item('/en/about.php', 'About', $active) ?>
          <?= nav_item('/en/projects.php', 'Projects', $active) ?>
          <?= nav_item('/en/services.php', 'Services', $active) ?>
          <?= nav_item('/en/contact.php', 'Contact', $active) ?>
          <?= nav_item('/en/blog.php', 'Guides', $active) ?>
        <?php else: ?>
          <?= nav_item('/index.php', 'Accueil', $active) ?>
          <?= nav_item('/a-propos.php', 'À propos', $active) ?>
          <?= nav_item('/projets.php', 'Projets', $active) ?>
          <?= nav_item('/services.php', 'Services', $active) ?>
          <?= nav_item('/contact.php', 'Contact', $active) ?>
          <?= nav_item('/blog.php', 'Conseils', $active) ?>
        <?php endif; ?>
      </nav>
      <div class="nav-actions">
        <a class="text-link" href="<?= e($lang === 'en' ? ($alternates['fr'] ?? site_url()) : ($alternates['en'] ?? site_url('en/'))) ?>" lang="<?= $lang === 'en' ? 'fr' : 'en' ?>" aria-label="<?= e($lang === 'en' ? 'Version française' : 'English version') ?>"><?= $lang === 'en' ? 'FR' : 'EN' ?></a>
        <button class="theme-toggle" type="button" data-theme-toggle aria-label="<?= e($lang === 'en' ? 'Toggle light theme' : 'Activer le thème clair') ?>" title="Changer de thème">
          <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3v2m0 14v2M3 12h2m14 0h2m-2.64-6.36-1.42 1.42M7.05 16.95l-1.42 1.42m0-12.72 1.42 1.42m9.9 9.9 1.42 1.42M16 12a4 4 0 1 1-8 0Z"/></svg>
        </button>
        <a class="button button--small button--nav" href="<?= e(site_url($lang === 'en' ? 'en/contact.php' : 'contact.php')) ?>"><?= $lang === 'en' ? 'Start a project' : 'Parlons projet' ?> <span aria-hidden="true">↗</span></a>
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
    $lang = (($GLOBALS['site_lang'] ?? 'fr') === 'en') ? 'en' : 'fr';
    $prefix = $lang === 'en' ? 'en/' : '';
    $t = static function (string $fr, string $en) use ($lang): string { return $lang === 'en' ? $en : $fr; };
    ?>
  </main>
  <a class="whatsapp-float" href="<?= e(whatsapp_url($t('Bonjour Michael, je souhaite discuter d’un projet web.', 'Hello Michael, I would like to discuss a web project.'))) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e($t('Contacter Michael sur WhatsApp', 'Contact Michael on WhatsApp')) ?>">
    <svg aria-hidden="true" viewBox="0 0 32 32"><path d="M16 3.2A12.6 12.6 0 0 0 5.1 22.1L3.5 28.5l6.6-1.7A12.7 12.7 0 1 0 16 3.2Zm0 22.9a10.2 10.2 0 0 1-5.2-1.4l-.4-.2-3.9 1 1-3.8-.3-.4a10.3 10.3 0 1 1 8.8 4.8Zm5.6-7.7c-.3-.1-1.8-.9-2.1-1s-.5-.1-.8.2-.9 1-1.1 1.2-.4.3-.7.1a8.3 8.3 0 0 1-2.5-1.6 9.1 9.1 0 0 1-1.7-2.2c-.2-.3 0-.5.1-.6l.5-.6.3-.5c.1-.2 0-.4 0-.5l-.9-2.1c-.2-.5-.5-.4-.7-.4h-.6c-.2 0-.5.1-.8.4s-1 1-1 2.4 1 2.8 1.2 3 .9 1.5 2.2 2.5c.3.3 2.2 1.8 4.2 2.4.5.2.9.3 1.2.4.5.1 1 .1 1.4.1.4-.1 1.8-.7 2-1.4s.3-1.3.2-1.4-.2-.2-.5-.3Z"/></svg>
    <span>WhatsApp</span>
  </a>
  <footer class="site-footer">
    <div class="shell footer-grid">
      <div>
        <a class="brand" href="<?= e(site_url($prefix . 'index.php')) ?>" aria-label="<?= e($t('Retour à l’accueil', 'Back to home')) ?>"><span class="brand-mark brand-mark--akm" aria-hidden="true">AKM</span><span>AKAKPOSSE <span class="brand-firstname">Michael</span></span></a>
        <p class="footer-intro"><?= e($t('Sites vitrines et plateformes métier qui font avancer les projets avec clarté.', 'Websites and business applications that move projects forward with clarity.')) ?></p>
      </div>
      <div>
        <p class="footer-title"><?= e($t('Explorer', 'Explore')) ?></p>
        <a href="<?= e(site_url($prefix . ($lang === 'en' ? 'projects.php' : 'projets.php'))) ?>"><?= e($t('Projets', 'Projects')) ?></a>
        <a href="<?= e(site_url($prefix . 'services.php')) ?>">Services</a>
        <a href="<?= e(site_url($prefix . ($lang === 'en' ? 'about.php' : 'a-propos.php'))) ?>"><?= e($t('À propos', 'About')) ?></a>
        <a href="<?= e(site_url($prefix . 'blog.php')) ?>"><?= e($t('Conseils', 'Guides')) ?></a>
      </div>
      <div>
        <p class="footer-title"><?= e($t('Contact', 'Contact')) ?></p>
        <a href="mailto:<?= e(config('email')) ?>"><?= e(config('email')) ?></a>
        <a href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener noreferrer">WhatsApp ↗</a>
        <a href="<?= e(site_url($prefix . 'contact.php')) ?>"><?= e($t('Demander un devis', 'Request a quote')) ?></a>
      </div>
      <div>
        <p class="footer-title"><?= e($t('Présence', 'Presence')) ?></p>
        <?php if (config('github_url')): ?><a href="<?= e((string) config('github_url')) ?>" target="_blank" rel="noopener noreferrer">GitHub ↗</a><?php endif; ?>
        <?php if (config('linkedin_url')): ?><a href="<?= e((string) config('linkedin_url')) ?>" target="_blank" rel="noopener noreferrer">LinkedIn ↗</a><?php endif; ?>
        <a href="<?= e(site_url($prefix . ($lang === 'en' ? 'legal.php' : 'mentions-legales.php'))) ?>"><?= e($t('Mentions & confidentialité', 'Legal & privacy')) ?></a>
        <a class="footer-admin-link" href="<?= e(site_url('admin/')) ?>">Espace administration ↗</a>
      </div>
    </div>
    <div class="shell footer-bottom"><span>© <?= e($year) ?> <?= e(config('owner')) ?>.</span><span><?= e($t('Conçu avec exigence, du premier pixel à la mise en ligne.', 'Built with care, from the first pixel to launch.')) ?></span></div>
  </footer>
  <script nonce="<?= e(csp_nonce()) ?>">window.PORTFOLIO = {csrf: '<?= e(csrf_token()) ?>', trackUrl: '<?= e(site_url('api/track.php')) ?>', projectUrl: '<?= e(site_url($lang === 'en' ? 'en/project.php?slug=' : 'projet.php?slug=')) ?>', projectId: <?= $trackedProjectId ? (int) $trackedProjectId : 'null' ?>};</script>
  <script src="<?= e(site_url('assets/js/app.js')) ?>" defer></script>
</body>
</html>
<?php
}
