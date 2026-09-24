<?php
declare(strict_types=1);

/** A lightweight, purpose-made visual treatment for each case-study card. */
function project_visual(array $project, string $size = 'card'): void
{
    $accent = $project['accent'] ?? 'violet';
    $lang = (($GLOBALS['site_lang'] ?? 'fr') === 'en') ? 'en' : 'fr';
    $capture = $lang === 'en' ? 'Project screenshot' : 'Capture du projet';
    $addCapture = $lang === 'en' ? 'Add a real screenshot from the administration area.' : 'Ajoutez une vraie capture depuis l’espace administrateur.';
    $imageUrl = trim((string) ($project['image_url'] ?? ''));
    $imageSrc = project_image_url($imageUrl);
    if ($imageSrc !== null): ?>
      <div class="project-visual project-visual--image project-visual--<?= e($size) ?>" role="img" aria-label="<?= e($capture) ?> <?= e($project['title']) ?>">
        <img src="<?= e($imageSrc) ?>" alt="<?= e($capture) ?> <?= e($project['title']) ?>" loading="lazy" decoding="async">
        <div class="visual-caption"><span><?= e($project['label']) ?></span><strong><?= e($project['title']) ?></strong></div>
      </div>
      <?php return;
    endif;
    ?>
    <div class="project-visual project-visual--placeholder project-visual--<?= e($size) ?>" role="img" aria-label="<?= e($capture) ?> <?= e($project['title']) ?>">
      <div class="capture-placeholder">
        <span class="capture-placeholder-icon" aria-hidden="true">↗</span>
        <span class="capture-placeholder-label"><?= e($capture) ?></span>
        <strong><?= e($project['title']) ?></strong>
        <small><?= e($addCapture) ?></small>
      </div>
      <div class="visual-caption"><span><?= e($project['label']) ?></span><strong><?= e($project['title']) ?></strong></div>
    </div>
    <?php
}
