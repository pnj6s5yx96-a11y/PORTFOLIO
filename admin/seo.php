<?php
declare(strict_types=1);

require __DIR__ . '/../includes/admin.php';
require_admin();

$error = '';
$success = '';

$seoSettings = [];
foreach (['home', 'about', 'services', 'projects', 'contact', 'blog'] as $seoKey) {
    $seoSettings[$seoKey]['fr'] = seo_meta_get($seoKey, 'fr');
    $seoSettings[$seoKey]['en'] = seo_meta_get($seoKey, 'en');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Votre session a expiré. Actualisez la page puis réessayez.';
    } else {
        try {
            foreach (['home', 'about', 'services', 'projects', 'contact', 'blog'] as $seoKey) {
                foreach (['fr', 'en'] as $seoLang) {
                    $prefix = 'SEO_' . strtoupper($seoKey) . '_' . strtoupper($seoLang);
                    seo_meta_save($seoKey, $seoLang, [
                        'title' => $_POST[$prefix . '_TITLE'] ?? '',
                        'description' => $_POST[$prefix . '_DESCRIPTION'] ?? '',
                        'focus_keyword' => $_POST[$prefix . '_KEYWORD'] ?? '',
                        'robots' => $_POST[$prefix . '_ROBOTS'] ?? 'index,follow',
                    ]);
                    $seoSettings[$seoKey][$seoLang] = seo_meta_get($seoKey, $seoLang);
                }
            }
            security_log('Métadonnées SEO modifiées', (int) $_SESSION['admin_id']);
            $success = 'Les paramètres SEO ont été enregistrés.';
        } catch (Throwable $e) {
            error_log('[portfolio] SEO save: ' . $e->getMessage());
            $error = 'Impossible d’enregistrer le SEO : ' . $e->getMessage();
        }
    }
}

admin_header('Référencement SEO', 'seo');
?>
<main class="admin-main"><div class="admin-shell">
  <div class="admin-title-row"><div><p class="eyebrow"><span></span> Visibilité sur Google</p><h1>Référencement SEO</h1><p>Ajuste le titre, la meta description et le mot-clé principal de chaque page. Ces valeurs sont utilisées automatiquement dans le code du site, en français et en anglais.</p></div><a class="admin-button admin-button--light" href="<?= e(site_url('admin/settings.php')) ?>">← Retour aux paramètres</a></div>
  <?php if ($error): ?><p class="admin-alert" role="alert"><?= e($error) ?></p><?php endif; ?>
  <?php if ($success): ?><p class="admin-success" role="status"><?= e($success) ?></p><?php endif; ?>

  <section class="admin-panel settings-seo-card">
    <form method="post" class="contact-form settings-form">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <div class="seo-dashboard-grid">
      <?php foreach ($seoSettings as $seoKey => $langs): $labels = ['home' => 'Accueil / Home', 'about' => 'À propos / About', 'services' => 'Services', 'projects' => 'Projets / Projects', 'contact' => 'Contact', 'blog' => 'Conseils / Guides']; ?>
        <div class="seo-page-card"><h3><?= e($labels[$seoKey]) ?></h3>
          <?php foreach (['fr' => 'Français', 'en' => 'English'] as $seoLang => $label): $v = $langs[$seoLang]; $prefix = 'SEO_' . strtoupper($seoKey) . '_' . strtoupper($seoLang); ?>
          <fieldset class="seo-fieldset"><legend><?= e($label) ?></legend>
            <div class="field"><label for="<?= e($prefix) ?>_TITLE">Title SEO</label><input id="<?= e($prefix) ?>_TITLE" name="<?= e($prefix) ?>_TITLE" maxlength="180" value="<?= e($v['title']) ?>"></div>
            <div class="field"><label for="<?= e($prefix) ?>_DESCRIPTION">Meta description</label><textarea id="<?= e($prefix) ?>_DESCRIPTION" name="<?= e($prefix) ?>_DESCRIPTION" maxlength="320"><?= e($v['description']) ?></textarea></div>
            <div class="field"><label for="<?= e($prefix) ?>_KEYWORD">Mot-clé principal</label><input id="<?= e($prefix) ?>_KEYWORD" name="<?= e($prefix) ?>_KEYWORD" maxlength="150" value="<?= e($v['focus_keyword']) ?>"></div>
            <div class="field"><label for="<?= e($prefix) ?>_ROBOTS">Indexation</label><select id="<?= e($prefix) ?>_ROBOTS" name="<?= e($prefix) ?>_ROBOTS"><option value="index,follow" <?= $v['robots'] === 'index,follow' ? 'selected' : '' ?>>Index, suivre les liens</option><option value="noindex,follow" <?= $v['robots'] === 'noindex,follow' ? 'selected' : '' ?>>Noindex, suivre les liens</option><option value="noindex,nofollow" <?= $v['robots'] === 'noindex,nofollow' ? 'selected' : '' ?>>Noindex, ne pas suivre</option></select></div>
          </fieldset><?php endforeach; ?>
        </div>
      <?php endforeach; ?>
      </div>
      <p class="settings-seo-note"><strong>Conseil :</strong> vise environ 50–60 caractères pour le title et 140–160 caractères pour la description. Évite de répéter artificiellement le même mot-clé.</p>
      <button class="admin-button admin-button--coral" type="submit">Enregistrer tout le SEO →</button>
    </form>
  </section>
</div></main>
<?php admin_footer(); ?>
