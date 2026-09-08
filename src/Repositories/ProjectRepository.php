<?php
declare(strict_types=1);

/**
 * Supplies portfolio entries from MySQL. A local fallback keeps the public
 * site readable until the database has been imported in MAMP.
 */
function portfolio_projects(): array
{
    static $projects = null;
    if (is_array($projects)) {
        return $projects;
    }

    $fallback = require __DIR__ . '/../Data/projects.php';
    $pdo = db();
    if (!$pdo) {
        return $projects = $fallback;
    }

    try {
        $rows = $pdo->query('SELECT id_projet, titre, description, stack_technique, lien_demo, lien_repo, image_url, ordre_affichage FROM projet ORDER BY ordre_affichage ASC, id_projet ASC')->fetchAll();
        if (!$rows) {
            return $projects = $fallback;
        }

        $fallbackByTitle = [];
        foreach ($fallback as $item) {
            $fallbackByTitle[mb_strtolower($item['title'])] = $item;
        }

        return $projects = array_map(static function (array $row) use ($fallbackByTitle): array {
            $template = $fallbackByTitle[mb_strtolower($row['titre'])] ?? [];
            $tags = array_values(array_filter(array_map('trim', explode(',', $row['stack_technique']))));
            $type = $template['type'] ?? (str_contains(mb_strtolower($row['titre'] . ' ' . $row['description']), 'commerce') ? 'ecommerce' : 'application');
            return [
                'id' => (int) $row['id_projet'],
                'slug' => $template['slug'] ?? 'projet-' . $row['id_projet'],
                'title' => $row['titre'],
                'label' => $template['label'] ?? 'Réalisation web',
                'type' => $type,
                'tags' => $tags,
                'summary' => $template['summary'] ?? mb_strimwidth(trim(strip_tags($row['description'])), 0, 150, '…'),
                'context' => $template['context'] ?? $row['description'],
                'solution' => $template['solution'] ?? 'Une solution web conçue pour répondre au besoin exprimé, avec une interface claire et une base technique durable.',
                'challenge' => $template['challenge'] ?? 'Concevoir une expérience simple, performante et adaptée aux usages réels.',
                'result' => $template['result'] ?? 'Un projet prêt à soutenir les objectifs de l’activité et à évoluer avec elle.',
                'stack' => $tags,
                'demo_url' => $row['lien_demo'],
                'repo_url' => $row['lien_repo'],
                'image_url' => $row['image_url'],
                'accent' => $template['accent'] ?? 'violet',
                'featured' => (int) $row['ordre_affichage'] === 1,
            ];
        }, $rows);
    } catch (Throwable $exception) {
        error_log('[portfolio] Project loading failed: ' . $exception->getMessage());
        return $projects = $fallback;
    }
}

function portfolio_project(?int $id, string $slug = ''): ?array
{
    foreach (portfolio_projects() as $project) {
        if (($id !== null && ($project['id'] ?? null) === $id) || ($slug !== '' && $project['slug'] === $slug)) {
            return $project;
        }
    }
    return null;
}
