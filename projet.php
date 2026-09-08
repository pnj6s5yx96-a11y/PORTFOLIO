<?php
declare(strict_types=1);
require __DIR__ . '/includes/site.php';
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
$slug = clean_text($_GET['slug'] ?? '', 80);
$project = portfolio_project($id, $slug);
if (!$project) { http_response_code(404); site_header('Projet introuvable', 'Cette étude de cas n’est pas disponible.', '/projets.php'); ?>
<section class="not-found"><div class="shell"><p class="eyebrow"><span></span> Erreur 404</p><h1>Cette étude de cas n’existe pas.</h1><a class="button button--primary" href="/projets.php">Retour aux projets</a></div></section>
<?php site_footer(); exit; }
site_header($project['title'], $project['summary'], '/projets.php');
$GLOBALS['current_project_id'] = $project['id'] ?? null;
?>
<section class="case-hero"><div class="shell case-hero-top"><a class="back-link" href="/projets.php">← Retour aux projets</a><p class="eyebrow"><span></span> <?= e($project['label']) ?></p><div class="case-title-row"><h1><?= e($project['title']) ?></h1><p><?= e($project['summary']) ?></p></div></div><div class="shell case-visual" data-reveal><?php project_visual($project, 'detail'); ?></div></section>
<section class="section case-body"><div class="shell case-layout"><aside class="case-side" data-reveal><p>En bref</p><dl><div><dt>Type</dt><dd><?= e($project['label']) ?></dd></div><div><dt>Stack</dt><dd><?= e(implode(', ', $project['stack'])) ?></dd></div><div><dt>Année</dt><dd><?= date('Y') ?></dd></div></dl><?php if ($demoUrl = external_url($project['demo_url'] ?? null)): ?><a class="case-resource" href="<?= e($demoUrl) ?>" target="_blank" rel="noopener noreferrer">Voir la démo ↗</a><?php endif; ?><?php if ($repoUrl = external_url($project['repo_url'] ?? null)): ?><a class="case-resource" href="<?= e($repoUrl) ?>" target="_blank" rel="noopener noreferrer">Dépôt GitHub ↗</a><?php endif; ?></aside><div class="case-content"><article data-reveal><p class="case-index">01 — Contexte</p><h2>Comprendre avant de <em>construire.</em></h2><p><?= e($project['context']) ?></p></article><article data-reveal><p class="case-index">02 — Solution</p><h2>Une réponse adaptée au <em>terrain.</em></h2><p><?= e($project['solution']) ?></p></article><article data-reveal><p class="case-index">03 — Défi résolu</p><h2>La précision fait la <em>différence.</em></h2><p><?= e($project['challenge']) ?></p></article><article data-reveal><p class="case-index">04 — Résultat</p><h2>Vers une expérience plus <em>fluide.</em></h2><p><?= e($project['result']) ?></p></article><div class="case-stack" data-reveal><p class="case-index">Stack technique complète</p><div><?php foreach ($project['stack'] as $technology): ?><span><?= e($technology) ?></span><?php endforeach; ?></div></div></div></div></section>
<section class="cta-panel-wrap"><div class="shell"><section class="cta-panel cta-panel--compact"><p class="eyebrow eyebrow--light"><span></span> Un besoin similaire ?</p><h2>Construisons une solution qui vous ressemble.</h2><a class="button button--light" href="/contact.php">Discuter de votre projet <span>↗</span></a></section></div></section>
<?php site_footer(); ?>
