<?php
declare(strict_types=1);
require __DIR__ . '/includes/site.php';
$posts = require __DIR__ . '/src/Data/blog.php';
site_header('Conseils web, SEO et développement au Bénin', 'Conseils pratiques sur la création de sites web, le développement et le SEO local au Bénin.', '/blog.php', ['seo_key' => 'blog', 'alternates' => ['fr' => site_url('blog.php'), 'en' => site_url('en/blog.php')]]);
?>
<section class="section"><div class="shell section-intro split-intro"><div><p class="eyebrow"><span></span> Ressources</p><h1>Conseils web, SEO et <em>développement.</em></h1></div><p>Des guides pratiques pour mieux comprendre la création de sites web, les applications métier et le référencement au Bénin.</p></div><div class="shell project-feature-grid blog-grid">
<?php foreach ($posts as $slug => $post): ?><article class="project-card" data-reveal><div class="project-card-content"><div class="project-card-meta"><span><?= e($post['category']) ?></span><span><?= e($post['read_time']) ?></span></div><h2><?= e($post['title']) ?></h2><p><?= e($post['description']) ?></p><a class="text-link" href="<?= e(site_url('article.php?slug=' . rawurlencode($slug))) ?>">Lire l’article ↗</a></div></article><?php endforeach; ?>
</div></section>
<?php site_footer(); ?>
