<?php
declare(strict_types=1);

/** A lightweight, purpose-made visual treatment for each case-study card. */
function project_visual(array $project, string $size = 'card'): void
{
    $accent = $project['accent'] ?? 'violet';
    $imageUrl = trim((string) ($project['image_url'] ?? ''));
    if ($imageUrl !== ''): ?>
      <div class="project-visual project-visual--image project-visual--<?= e($size) ?>" role="img" aria-label="Capture du projet <?= e($project['title']) ?>">
        <img src="<?= e($imageUrl) ?>" alt="Capture du projet <?= e($project['title']) ?>" loading="lazy">
        <div class="visual-caption"><span><?= e($project['label']) ?></span><strong><?= e($project['title']) ?></strong></div>
      </div>
      <?php return;
    endif;
    ?>
    <div class="project-visual project-visual--<?= e($accent) ?> project-visual--<?= e($size) ?>" role="img" aria-label="Aperçu visuel de <?= e($project['title']) ?>">
      <div class="visual-window">
        <div class="visual-topbar"><span></span><span></span><span></span><i></i></div>
        <div class="visual-sidebar"><b></b><b></b><b></b><b></b><b></b></div>
        <div class="visual-content">
          <div class="visual-heading"></div>
          <div class="visual-stats"><i></i><i></i><i></i></div>
          <div class="visual-chart"><span></span><span></span><span></span><span></span><span></span><span></span></div>
          <div class="visual-lines"><b></b><b></b><b></b></div>
        </div>
      </div>
      <div class="visual-caption"><span><?= e($project['label']) ?></span><strong><?= e($project['title']) ?></strong></div>
    </div>
    <?php
}
