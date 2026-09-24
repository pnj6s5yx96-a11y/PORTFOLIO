<?php
declare(strict_types=1);
require __DIR__ . '/includes/site.php';
header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\n";
echo "Allow: /\n\n";
echo "Disallow: /admin/\n";
echo "Disallow: /espace-client/\n";
echo "Disallow: /api/\n";
echo "Disallow: /.git/\n";
echo "Disallow: /.env\n";
echo "Disallow: /storage/\n\n";
echo 'Sitemap: ' . site_url('sitemap.php') . "\n";
if ((string) config('environment') === 'local') { echo "X-Robots-Tag: noindex" . "\n"; }
