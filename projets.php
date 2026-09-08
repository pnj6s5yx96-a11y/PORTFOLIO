<?php
declare(strict_types=1);
require __DIR__ . '/includes/site.php';
$projects = portfolio_projects();
site_header('Projets', 'Études de cas et réalisations web de Michael AKAKPOSSE : site vitrine, application métier et e-commerce.', '/projets.php');
?>
<section class="page-hero page-hero--projects"><div class="shell page-hero-grid"><div data-reveal><p class="eyebrow"><span></span> Portfolio</p><h1>Le design doit raconter ce que le projet <em>résout.</em></h1></div><p data-reveal data-delay="100">Derrière chaque écran, il y a un contexte, des contraintes et une décision. Voici comment je les transforme en expériences simples à utiliser.</p></div></section>
<section class="section projects-section"><div class="shell"><div class="filter-bar" aria-label="Filtrer les projets" data-filter-controls><span>Filtrer par</span><div role="group" aria-label="Technologie ou type"><button type="button" class="is-active" data-filter="all">Tous <b><?= count($projects) ?></b></button><button type="button" data-filter="vitrine">Sites vitrines</button><button type="button" data-filter="application">Applications web</button><button type="button" data-filter="ecommerce">E-commerce</button><button type="button" data-filter="php">PHP</button><button type="button" data-filter="javascript">JavaScript</button></div></div>
  <div class="projects-grid" data-project-grid>
    <?php foreach ($projects as $index => $project): $filterTags = strtolower($project['type'] . ' ' . implode(' ', $project['tags'])); ?>
      <article class="project-card project-card--grid" data-project-card data-tags="<?= e($filterTags) ?>" data-reveal data-delay="<?= ($index % 3) * 80 ?>">
        <?php project_visual($project); ?>
        <div class="project-card-content"><div class="project-card-meta"><span><?= e($project['label']) ?></span><span><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span></div><h2><?= e($project['title']) ?></h2><p><?= e($project['summary']) ?></p><div class="tag-list"><?php foreach ($project['tags'] as $tag): ?><span><?= e($tag) ?></span><?php endforeach; ?></div><div class="project-actions"><a class="text-link" href="/projet.php?<?= isset($project['id']) ? 'id=' . e((string) $project['id']) : 'slug=' . e($project['slug']) ?>">Voir l’étude de cas <span>↗</span></a><button class="text-button" type="button" data-project-open data-project="<?= e(json_encode($project, JSON_UNESCAPED_UNICODE)) ?>">Aperçu <span>+</span></button></div><?php if ($demoUrl = external_url($project['demo_url'] ?? null)): ?><a class="project-resource" href="<?= e($demoUrl) ?>" target="_blank" rel="noopener noreferrer">Voir la démo ↗</a><?php endif; ?><?php if ($repoUrl = external_url($project['repo_url'] ?? null)): ?><a class="project-resource" href="<?= e($repoUrl) ?>" target="_blank" rel="noopener noreferrer">Code source ↗</a><?php endif; ?></div>
      </article>
    <?php endforeach; ?>
  </div>
  <p class="empty-state" hidden data-empty-state>Aucun projet ne correspond à ce filtre pour le moment.</p>
</div></section>
<dialog class="project-dialog" data-project-dialog aria-labelledby="dialog-project-title"><button class="dialog-close" type="button" data-dialog-close aria-label="Fermer l’aperçu">×</button><div class="dialog-layout"><div class="dialog-visual" data-dialog-visual></div><div class="dialog-copy"><p class="eyebrow"><span></span> Étude de cas</p><h2 id="dialog-project-title" data-dialog-title></h2><p data-dialog-summary></p><div class="dialog-block"><b>Le défi</b><p data-dialog-challenge></p></div><div class="tag-list" data-dialog-tags></div><a class="button button--primary" data-dialog-link href="#">Voir l’étude complète <span>↗</span></a></div></div></dialog>
<?php site_footer(); ?>
