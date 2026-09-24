<?php
declare(strict_types=1);
require __DIR__ . '/includes/site.php';
header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

$urls = [
    ['fr' => page_url('home', 'fr'), 'en' => page_url('home', 'en'), 'freq' => 'weekly', 'priority' => '1.0'],
    ['fr' => page_url('about', 'fr'), 'en' => page_url('about', 'en'), 'freq' => 'monthly', 'priority' => '0.7'],
    ['fr' => page_url('projects', 'fr'), 'en' => page_url('projects', 'en'), 'freq' => 'weekly', 'priority' => '0.9'],
    ['fr' => page_url('services', 'fr'), 'en' => page_url('services', 'en'), 'freq' => 'monthly', 'priority' => '0.9'],
    ['fr' => page_url('contact', 'fr'), 'en' => page_url('contact', 'en'), 'freq' => 'monthly', 'priority' => '0.8'],
    ['fr' => page_url('legal', 'fr'), 'en' => page_url('legal', 'en'), 'freq' => 'yearly', 'priority' => '0.3'],
    ['fr' => page_url('blog', 'fr'), 'en' => page_url('blog', 'en'), 'freq' => 'weekly', 'priority' => '0.8'],
];
foreach (portfolio_projects() as $project) {
    $slug = (string) ($project['slug'] ?? '');
    if ($slug === '') { continue; }
    $urls[] = ['fr' => project_page_url($slug, 'fr'), 'en' => project_page_url($slug, 'en'), 'freq' => 'monthly', 'priority' => '0.7'];
}

$blogPosts = require __DIR__ . '/src/Data/blog.php';
foreach ($blogPosts as $slug => $post) {
    $urls[] = ['fr' => site_url('article.php?slug=' . rawurlencode((string) $slug)), 'en' => site_url('en/article.php?slug=' . rawurlencode((string) $slug)), 'freq' => 'monthly', 'priority' => '0.7'];
}
?>
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
<?php foreach ($urls as $url): ?>
  <?php $fr = $url['fr']; $en = $url['en']; ?>
  <url>
    <loc><?= e($fr) ?></loc>
    <changefreq><?= e($url['freq']) ?></changefreq>
    <priority><?= e($url['priority']) ?></priority>
    <xhtml:link rel="alternate" hreflang="fr" href="<?= e($fr) ?>" />
    <xhtml:link rel="alternate" hreflang="en" href="<?= e($en) ?>" />
    <xhtml:link rel="alternate" hreflang="x-default" href="<?= e($fr) ?>" />
  </url>
  <url>
    <loc><?= e($en) ?></loc>
    <changefreq><?= e($url['freq']) ?></changefreq>
    <priority><?= e($url['priority']) ?></priority>
    <xhtml:link rel="alternate" hreflang="fr" href="<?= e($fr) ?>" />
    <xhtml:link rel="alternate" hreflang="en" href="<?= e($en) ?>" />
    <xhtml:link rel="alternate" hreflang="x-default" href="<?= e($fr) ?>" />
  </url>
<?php endforeach; ?>
</urlset>
