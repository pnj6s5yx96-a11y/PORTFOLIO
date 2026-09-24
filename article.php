<?php
declare(strict_types=1);
require __DIR__ . '/includes/site.php';
$posts = require __DIR__ . '/src/Data/blog.php';
$slug = clean_text($_GET['slug'] ?? '', 100);
$post = $posts[$slug] ?? null;
if (!$post) { http_response_code(404); site_header('Article introuvable', 'Cet article n’est pas disponible.', '/blog.php', ['noindex' => true, 'breadcrumb' => false]); ?><section class="section"><div class="shell"><h1>Article introuvable.</h1><a class="button button--primary" href="<?= e(site_url('blog.php')) ?>">Retour aux conseils</a></div></section><?php site_footer(); exit; }
$canonical = site_url('article.php?slug=' . rawurlencode($slug));
$schema = ['@type'=>'Article','@id'=>$canonical.'#article','headline'=>$post['title'],'description'=>$post['description'],'datePublished'=>$post['date'],'dateModified'=>$post['date'],'author'=>['@id'=>site_url('#person')],'publisher'=>['@id'=>site_url('#person')],'mainEntityOfPage'=>['@id'=>$canonical.'#webpage']];
site_header($post['title'], $post['description'], '/blog.php', ['canonical'=>$canonical,'alternates'=>['fr'=>$canonical,'en'=>site_url('en/article.php?slug='.rawurlencode($slug))],'focus_keyword'=>$post['focus_keyword'] ?? '','schema_extra'=>[$schema]]);
?><article class="section article-page"><div class="shell article-shell"><header class="article-header"><p class="eyebrow"><span></span> <?= e($post['category']) ?> · <?= e($post['read_time']) ?></p><h1><?= e($post['title']) ?></h1><p class="article-intro"><?= e($post['intro']) ?></p></header><div class="article-content"><?php foreach ($post['sections'] as $section): ?><section><h2><?= e($section['title']) ?></h2><?php foreach ($section['paragraphs'] as $paragraph): ?><p><?= e($paragraph) ?></p><?php endforeach; ?></section><?php endforeach; ?></div><div class="article-cta"><h2>Un projet web au Bénin ?</h2><p>Si vous préparez un site vitrine, une application web ou une boutique en ligne, échangeons sur votre besoin.</p><a class="button button--primary" href="<?= e(site_url('contact.php')) ?>">Parler de votre projet ↗</a></div></div></article><?php site_footer(); ?>
