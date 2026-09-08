<?php
declare(strict_types=1);
require __DIR__ . '/includes/site.php';
$projects = portfolio_projects();
site_header('Accueil', 'Michael AKAKPOSSE conçoit des sites vitrines et plateformes métier sur mesure, rapides, accessibles et pensés pour convertir.', '/');
?>
<section class="hero">
  <div class="hero-noise" aria-hidden="true"></div>
  <div class="shell hero-grid">
    <div class="hero-copy" data-reveal>
      <p class="eyebrow"><span></span> Développeur web freelance</p>
      <h1>Des expériences web <em>claires</em> pour faire avancer vos projets.</h1>
      <p class="hero-text">Je transforme une idée, un besoin métier ou une activité en site web utile, rapide et convaincant — de la première maquette à la mise en ligne.</p>
      <div class="hero-actions"><a class="button button--primary" href="/contact.php">Lancer un projet <span aria-hidden="true">↗</span></a><a class="text-link" href="/projets.php">Voir les réalisations <span aria-hidden="true">↓</span></a></div>
      <div class="hero-proof"><div class="avatar-stack" aria-hidden="true"><span>01</span><span>02</span><span>03</span></div><p>Une approche directe, collaborative<br>et exigeante.</p></div>
    </div>
    <div class="hero-art hero-art--portrait" data-reveal data-delay="120">
      <div class="portrait-glow" aria-hidden="true"></div>
      <figure class="hero-portrait"><img src="https://images.unsplash.com/photo-1565076107311-59de0a9bc0fc?auto=format&fit=crop&fm=jpg&q=82&w=1200" alt="Développeur web travaillant sur un ordinateur portable" width="1200" height="900" fetchpriority="high"><figcaption><span>Michael AKAKPOSSE</span><small>Développeur web freelance</small></figcaption></figure>
      <div class="code-card code-card--back"><span>const</span> idée = <i>impact</i>;</div>
      <div class="code-card code-card--front"><span>design</span> + <i>logic</i> = <b>result</b></div>
      <div class="hero-badge"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m12 2 1.7 5.3L19 9l-5.3 1.7L12 16l-1.7-5.3L5 9l5.3-1.7Z"/></svg><span>Une présence<br>qui vous ressemble</span></div>
    </div>
  </div>
  <div class="shell hero-footer"><p>Créer du web qui sert vraiment votre activité.</p><a href="#projets" aria-label="Découvrir la section projets"><span>Défiler</span><i></i></a></div>
</section>

<section class="photo-story" aria-labelledby="photo-story-title">
  <div class="photo-story-media" data-reveal><img src="https://images.unsplash.com/photo-1637084576418-3f25344ccb7c?auto=format&fit=crop&fm=jpg&q=82&w=2200" alt="Une personne développe un site web sur ordinateur portable" loading="lazy"></div>
  <div class="shell photo-story-content" data-reveal data-delay="100"><p class="eyebrow eyebrow--light"><span></span> Du concret, pas seulement des promesses</p><h2 id="photo-story-title">Chaque projet part d’une ambition. Il se construit avec <em>attention.</em></h2><p>Une interface réussie doit porter votre image, guider vos visiteurs et rester agréable à utiliser longtemps après sa mise en ligne.</p></div>
</section>

<section class="image-rail-section" aria-label="Univers visuel et numérique">
  <div class="image-rail" aria-hidden="true">
    <figure><img src="https://images.unsplash.com/photo-1588091209794-8aa1768e2937?auto=format&fit=crop&fm=jpg&q=80&w=1300" alt="" loading="lazy"></figure>
    <figure><img src="https://images.unsplash.com/photo-1529071242804-840f9a164b8b?auto=format&fit=crop&fm=jpg&q=80&w=1300" alt="" loading="lazy"></figure>
    <figure><img src="https://images.unsplash.com/photo-1565076107311-59de0a9bc0fc?auto=format&fit=crop&fm=jpg&q=80&w=1300" alt="" loading="lazy"></figure>
    <figure><img src="https://images.unsplash.com/photo-1743796055651-41c743ff2465?auto=format&fit=crop&fm=jpg&q=80&w=1300" alt="" loading="lazy"></figure>
    <figure><img src="https://images.unsplash.com/photo-1588091209794-8aa1768e2937?auto=format&fit=crop&fm=jpg&q=80&w=1300" alt="" loading="lazy"></figure>
    <figure><img src="https://images.unsplash.com/photo-1529071242804-840f9a164b8b?auto=format&fit=crop&fm=jpg&q=80&w=1300" alt="" loading="lazy"></figure>
    <figure><img src="https://images.unsplash.com/photo-1565076107311-59de0a9bc0fc?auto=format&fit=crop&fm=jpg&q=80&w=1300" alt="" loading="lazy"></figure>
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
        <div class="project-card-content"><div class="project-card-meta"><span><?= e($project['label']) ?></span><span><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span></div><h3><?= e($project['title']) ?></h3><p><?= e($project['summary']) ?></p><a class="circle-link" href="/projet.php?slug=<?= e($project['slug']) ?>" aria-label="Découvrir <?= e($project['title']) ?>">↗</a></div>
      </article>
    <?php endforeach; ?>
  </div>
  <div class="shell section-end"><a class="text-link text-link--large" href="/projets.php">Tous les projets <span aria-hidden="true">↗</span></a></div>
</section>

<section class="section services-preview">
  <div class="shell services-layout">
    <div data-reveal><p class="eyebrow"><span></span> Mon expertise</p><h2>Un partenaire technique, du <em>cadrage</em> à l’évolution.</h2><a class="button button--secondary" href="/services.php">Découvrir les services <span aria-hidden="true">↗</span></a></div>
    <div class="service-list" data-reveal data-delay="100">
      <a href="/services.php#vitrine"><span>01</span><strong>Sites vitrines</strong><i>Présenter une activité avec précision et personnalité.</i><b>↗</b></a>
      <a href="/services.php#applications"><span>02</span><strong>Applications web</strong><i>Outiller vos équipes avec des parcours simples.</i><b>↗</b></a>
      <a href="/services.php#commerce"><span>03</span><strong>E-commerce</strong><i>Créer un chemin fluide vers la vente.</i><b>↗</b></a>
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

<section class="cta-panel-wrap"><div class="shell"><section class="cta-panel" data-reveal><p class="eyebrow eyebrow--light"><span></span> Votre prochain projet</p><h2>Une idée mérite mieux qu’un site quelconque.</h2><p>Parlons de vos objectifs et voyons comment leur donner une présence numérique qui compte.</p><div><a class="button button--light" href="/contact.php">Parler de votre projet <span aria-hidden="true">↗</span></a><a class="cta-email" href="mailto:<?= e(config('email')) ?>"><?= e(config('email')) ?></a></div><span class="cta-shape shape-a" aria-hidden="true"></span><span class="cta-shape shape-b" aria-hidden="true"></span></section></div></section>
<?php site_footer(); ?>
