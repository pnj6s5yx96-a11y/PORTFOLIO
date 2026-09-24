<?php
declare(strict_types=1);
require __DIR__ . '/includes/site.php';
$projects = portfolio_projects();
$heroImage = configured_hero_image();
site_header('Accueil', 'Développeur web freelance à Cotonou, Bénin. Création de sites vitrines, applications web et solutions e-commerce rapides, modernes et optimisées pour les moteurs de recherche.', '/', ['lang' => 'fr', 'seo_key' => 'home', 'alternates' => ['fr' => site_url(), 'en' => site_url('en/')]]);
?>
<section class="hero">
  <div class="hero-noise" aria-hidden="true"></div>
  <div class="shell hero-grid">
    <div class="hero-copy" data-reveal>
      <p class="eyebrow"><span></span> Développeur web freelance</p>
      <h1>Développeur web freelance à Cotonou pour un web <em>clair, crédible et utile.</em></h1>
      <p class="hero-text">Je conçois des sites vitrines, applications métier et expériences e-commerce qui donnent confiance, simplifient les parcours et aident votre activité à avancer.</p>
      <div class="hero-actions"><a class="button button--primary" href="<?= e(site_url('contact.php')) ?>">Lancer un projet <span aria-hidden="true">↗</span></a><a class="text-link" href="<?= e(site_url('projets.php')) ?>">Voir les réalisations <span aria-hidden="true">↓</span></a></div>
      <div class="hero-proof hero-proof--facts"><div class="proof-mark" aria-hidden="true">AKM</div><p><strong>Du site vitrine à l’application métier.</strong><br>Des solutions pensées pour les indépendants, PME et porteurs de projet.</p></div>
    </div>
    <div class="hero-art hero-art--portrait" data-reveal data-delay="120">
      <div class="portrait-glow" aria-hidden="true"></div>
      <figure class="hero-portrait"><img src="<?= e(site_image_url($heroImage)) ?>" alt="Portrait de Michael AKAKPOSSE, développeur web freelance" width="900" height="1000" fetchpriority="high"><figcaption><span>Michael AKAKPOSSE</span><small>Développeur web freelance</small></figcaption></figure>
      <div class="code-card code-card--back"><span>const</span> idée = <i>impact</i>;</div>
      <div class="code-card code-card--front"><span>design</span> + <i>logic</i> = <b>result</b></div>
      <div class="hero-badge"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m12 2 1.7 5.3L19 9l-5.3 1.7L12 16l-1.7-5.3L5 9l5.3-1.7Z"/></svg><span>Une présence<br>qui vous ressemble</span></div>
    </div>
  </div>
  <div class="shell hero-footer"><p>Créer du web qui sert vraiment votre activité.</p><a href="#projets" aria-label="Découvrir la section projets"><span>Défiler</span><i></i></a></div>
</section>

<section class="proof-strip" aria-label="Positionnement">
  <div class="shell proof-strip-grid">
    <div><span>01</span><strong>Clarté</strong><p>Un message et des parcours faciles à comprendre.</p></div>
    <div><span>02</span><strong>Crédibilité</strong><p>Une identité digitale cohérente qui rassure vos visiteurs.</p></div>
    <div><span>03</span><strong>Utilité</strong><p>Des fonctionnalités pensées autour de vos vrais besoins.</p></div>
    <div><span>04</span><strong>Évolution</strong><p>Une base technique conçue pour grandir avec votre activité.</p></div>
  </div>
</section>

<section class="section intro-section" id="projets">
  <div class="shell section-intro split-intro" data-reveal>
    <div><p class="eyebrow"><span></span> Réalisations sélectionnées</p><h2>Des projets qui partent d’un <em>vrai besoin.</em></h2></div>
    <p>Chaque interface est pensée pour être belle, mais surtout pour aider une activité à mieux se présenter, s’organiser ou convertir.</p>
  </div>
  <div class="shell project-feature-grid">
    <?php foreach ($projects as $index => $project): ?>
      <article class="project-card <?= $index === 0 ? 'project-card--featured' : '' ?>" data-reveal data-delay="<?= $index * 80 ?>">
        <?php project_visual($project); ?>
        <div class="project-card-content"><div class="project-card-meta"><span><?= e($project['label']) ?></span><span><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span></div><h3><?= e($project['title']) ?></h3><p><?= e($project['summary']) ?></p><a class="circle-link" href="<?= e(site_url('projet.php?slug=' . rawurlencode($project['slug']))) ?>" aria-label="Découvrir <?= e($project['title']) ?>">↗</a></div>
      </article>
    <?php endforeach; ?>
  </div>
  <div class="shell section-end"><a class="text-link text-link--large" href="<?= e(site_url('projets.php')) ?>">Tous les projets <span aria-hidden="true">↗</span></a></div>
</section>

<section class="section services-preview">
  <div class="shell services-layout">
    <div data-reveal><p class="eyebrow"><span></span> Mon expertise</p><h2>Un partenaire technique, du <em>cadrage</em> à l’évolution.</h2><a class="button button--secondary" href="<?= e(site_url('services.php')) ?>">Découvrir les services <span aria-hidden="true">↗</span></a></div>
    <div class="service-list" data-reveal data-delay="100">
      <a href="<?= e(site_url('services.php#vitrine')) ?>"><span>01</span><strong>Sites vitrines</strong><i>Présenter une activité avec précision et personnalité.</i><b>↗</b></a>
      <a href="<?= e(site_url('services.php#applications')) ?>"><span>02</span><strong>Applications web</strong><i>Outiller vos équipes avec des parcours simples.</i><b>↗</b></a>
      <a href="<?= e(site_url('services.php#commerce')) ?>"><span>03</span><strong>E-commerce</strong><i>Créer un chemin fluide vers la vente.</i><b>↗</b></a>
    </div>
  </div>
</section>

<section class="section process-section">
  <div class="shell"><div class="section-intro" data-reveal><p class="eyebrow"><span></span> Une méthode lisible</p><h2>Bien travailler commence par bien <em>comprendre.</em></h2></div>
    <ol class="process-grid">
      <li data-reveal><span>01</span><h3>Écouter</h3><p>Nous clarifions le contexte, vos objectifs et les personnes que le projet doit convaincre.</p></li>
      <li data-reveal data-delay="80"><span>02</span><h3>Structurer</h3><p>Je conçois les parcours, les contenus et la solution technique la plus juste.</p></li>
      <li data-reveal data-delay="160"><span>03</span><h3>Construire</h3><p>J’intègre, teste et peaufine chaque détail pour livrer un produit solide.</p></li>
      <li data-reveal data-delay="240"><span>04</span><h3>Faire vivre</h3><p>La mise en ligne est le début : je reste disponible pour faire évoluer votre outil.</p></li>
    </ol>
  </div>
</section>

<section class="section local-seo-section"><div class="shell section-intro split-intro" data-reveal><div><p class="eyebrow"><span></span> Développement web au Bénin</p><h2>Un développeur web freelance basé à <em>Cotonou.</em></h2></div><p>J’accompagne les entrepreneurs, indépendants et entreprises avec des sites vitrines, applications web et solutions e-commerce conçus pour le marché béninois et les projets internationaux.</p></div></section>

<section class="section resources-preview"><div class="shell section-intro split-intro" data-reveal><div><p class="eyebrow"><span></span> Conseils & ressources</p><h2>Des réponses concrètes pour <em>mieux préparer votre projet web.</em></h2></div><p>Découvrez mes guides sur la création de sites web, le développement et le référencement local au Bénin.</p></div><div class="shell project-feature-grid blog-grid"><?php foreach (array_slice(require __DIR__ . '/src/Data/blog.php', 0, 3, true) as $slug => $post): ?><article class="project-card" data-reveal><div class="project-card-content"><div class="project-card-meta"><span><?= e($post['category']) ?></span><span><?= e($post['read_time']) ?></span></div><h3><?= e($post['title']) ?></h3><p><?= e($post['description']) ?></p><a class="text-link" href="<?= e(site_url('article.php?slug=' . rawurlencode($slug))) ?>">Lire le guide ↗</a></div></article><?php endforeach; ?></div><div class="shell section-end"><a class="text-link text-link--large" href="<?= e(site_url('blog.php')) ?>">Tous les conseils <span aria-hidden="true">↗</span></a></div></section>

<section class="cta-panel-wrap"><div class="shell"><section class="cta-panel" data-reveal><p class="eyebrow eyebrow--light"><span></span> Votre prochain projet</p><h2>Une idée mérite mieux qu’un site quelconque.</h2><p>Parlons de vos objectifs et voyons comment leur donner une présence numérique qui compte.</p><div><a class="button button--light" href="<?= e(site_url('contact.php')) ?>">Parler de votre projet <span aria-hidden="true">↗</span></a><a class="cta-email" href="mailto:<?= e(config('email')) ?>"><?= e(config('email')) ?></a></div><span class="cta-shape shape-a" aria-hidden="true"></span><span class="cta-shape shape-b" aria-hidden="true"></span></section></div></section>
<?php site_footer(); ?>
