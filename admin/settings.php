<?php
declare(strict_types=1);

require __DIR__ . '/../includes/admin.php';
require_admin();

function dashboard_write_site_settings(array $values): bool
{
    $envFile = dirname(__DIR__) . '/.env';
    if (!is_readable($envFile) || !is_writable($envFile)) {
        return false;
    }

    $contents = (string) file_get_contents($envFile);
    foreach ($values as $key => $value) {
        $value = str_replace(["\r", "\n", '"'], '', trim((string) $value));
        $line = $key . '="' . $value . '"';
        $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
        $contents = preg_match($pattern, $contents) === 1
            ? (string) preg_replace($pattern, $line, $contents)
            : rtrim($contents) . PHP_EOL . $line . PHP_EOL;
    }

    return file_put_contents($envFile, $contents, LOCK_EX) !== false;
}


function dashboard_upload_site_image(array $file, string $prefix): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
        throw new RuntimeException('Le téléversement de l’image a échoué.');
    }
    if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Chaque image doit faire 5 Mo maximum.');
    }
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file((string) $file['tmp_name']) ?: '';
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Utilisez une image JPG, PNG ou WebP.');
    }
    $directory = dirname(__DIR__) . '/assets/uploads/site';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Le dossier des images du site ne peut pas être créé.');
    }
    $filename = $prefix . '-' . bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
    if (!move_uploaded_file((string) $file['tmp_name'], $directory . '/' . $filename)) {
        throw new RuntimeException('Impossible d’enregistrer l’image.');
    }
    return 'assets/uploads/site/' . $filename;
}

function dashboard_delete_site_image(?string $image): void
{
    $image = trim((string) $image);
    if ($image === '' || preg_match('~^https?://~i', $image)) return;
    $relative = ltrim(str_replace('\\', '/', $image), '/');
    if (!preg_match('~^assets/uploads/site/[A-Za-z0-9._-]+$~', $relative)) return;
    $file = dirname(__DIR__) . '/' . $relative;
    if (is_file($file)) @unlink($file);
}

$error = '';
$success = '';
$settings = [
    'SITE_OWNER' => (string) config('owner'),
    'SITE_EMAIL' => (string) config('email'),
    'WHATSAPP_NUMBER' => (string) config('whatsapp_number'),
    'SEO_LOCALITY' => (string) config('seo_locality'),
    'SITE_HERO_IMAGE' => (string) config('hero_image'),
    'SITE_PROFILE_IMAGE' => (string) config('profile_image'),
];


if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Votre session a expiré. Actualisez la page puis réessayez.';
    } else {
        $settings = [
            'SITE_OWNER' => clean_text($_POST['SITE_OWNER'] ?? '', 100),
            'SITE_EMAIL' => trim((string) ($_POST['SITE_EMAIL'] ?? '')),
            'WHATSAPP_NUMBER' => preg_replace('/^00/', '', preg_replace('/\D+/', '', (string) ($_POST['WHATSAPP_NUMBER'] ?? ''))),
            'SEO_LOCALITY' => clean_text($_POST['SEO_LOCALITY'] ?? '', 100),
            'SITE_HERO_IMAGE' => (string) config('hero_image'),
            'SITE_PROFILE_IMAGE' => (string) config('profile_image'),
        ];

        $oldHero = $settings['SITE_HERO_IMAGE'];
        $oldProfile = $settings['SITE_PROFILE_IMAGE'];
        $newHero = dashboard_upload_site_image($_FILES['site_hero_image'] ?? [], 'hero');
        $newProfile = dashboard_upload_site_image($_FILES['site_profile_image'] ?? [], 'profile');
        if ($newHero !== null) $settings['SITE_HERO_IMAGE'] = $newHero;
        if ($newProfile !== null) $settings['SITE_PROFILE_IMAGE'] = $newProfile;

        if (mb_strlen($settings['SITE_OWNER']) < 2) {
            $error = 'Indiquez le nom à afficher sur le site.';
        } elseif (!filter_var($settings['SITE_EMAIL'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Indiquez une adresse e-mail valide.';
        } elseif ($settings['WHATSAPP_NUMBER'] !== '' && strlen($settings['WHATSAPP_NUMBER']) < 8) {
            $error = 'Le numéro WhatsApp semble trop court.';
        } elseif (!dashboard_write_site_settings($settings)) {
            if ($newHero !== null) dashboard_delete_site_image($newHero);
            if ($newProfile !== null) dashboard_delete_site_image($newProfile);
            $error = 'Les réglages n’ont pas pu être enregistrés. Vérifiez les permissions du fichier .env.';
        } else {
            if ($newHero !== null && $oldHero !== $newHero) dashboard_delete_site_image($oldHero);
            if ($newProfile !== null && $oldProfile !== $newProfile) dashboard_delete_site_image($oldProfile);
            security_log('Paramètres et images du site modifiés', (int) $_SESSION['admin_id']);
            $success = 'Paramètres et images enregistrés. Les changements sont visibles immédiatement.';
        }
    }
}

$smtpReady = (string) config('mail.host') !== '' && (string) config('mail.username') !== '' && (string) config('mail.password') !== '';
$uploadsDirectory = dirname(__DIR__) . '/assets/uploads/site';
$uploadsReady = is_dir($uploadsDirectory) ? is_writable($uploadsDirectory) : is_writable(dirname($uploadsDirectory));

admin_header('Paramètres', 'settings');
?>
<main class="admin-main"><div class="admin-shell">
  <div class="admin-title-row"><div><p class="eyebrow"><span></span> Configuration du site</p><h1>Paramètres</h1><p>Gérez les coordonnées visibles sur le site et contrôlez les éléments essentiels de votre espace de travail.</p></div><a class="admin-button admin-button--light" href="<?= e(site_url()) ?>" target="_blank" rel="noopener">Voir le site ↗</a></div>
  <?php if ($error): ?><p class="admin-alert" role="alert"><?= e($error) ?></p><?php endif; ?>
  <?php if ($success): ?><p class="admin-success" role="status"><?= e($success) ?></p><?php endif; ?>

  <div class="settings-grid">
    <section class="admin-panel settings-card">
      <h2>Identité & contact</h2>
      <p>Ces informations sont utilisées dans le portfolio, le formulaire de contact et le bouton WhatsApp.</p>
      <form method="post" enctype="multipart/form-data" class="contact-form settings-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save_settings">
        <div class="field"><label for="SITE_OWNER">Nom affiché</label><input id="SITE_OWNER" name="SITE_OWNER" required maxlength="100" value="<?= e($settings['SITE_OWNER']) ?>" placeholder="AKM AKAKPOSSE Michael"></div>
        <div class="field"><label for="SITE_EMAIL">E-mail de réception des demandes</label><input id="SITE_EMAIL" name="SITE_EMAIL" type="email" required maxlength="150" value="<?= e($settings['SITE_EMAIL']) ?>" placeholder="akmdejesus@icloud.com"><small>Les demandes du formulaire seront destinées à cette adresse. La livraison par e-mail doit aussi être configurée via SMTP.</small></div>
        <div class="field"><label for="WHATSAPP_NUMBER">Numéro WhatsApp</label><input id="WHATSAPP_NUMBER" name="WHATSAPP_NUMBER" inputmode="tel" maxlength="20" value="<?= e($settings['WHATSAPP_NUMBER']) ?>" placeholder="2290197927434"><small>Sans « + » ni « 00 » : le bouton WhatsApp ouvrira directement une discussion.</small></div>
        <div class="field"><label for="SEO_LOCALITY">Ville / zone d’activité</label><input id="SEO_LOCALITY" name="SEO_LOCALITY" maxlength="100" value="<?= e($settings['SEO_LOCALITY']) ?>" placeholder="Cotonou, Bénin"><small>Utilisée dans la présentation et le référencement local.</small></div>
        <div class="settings-media-grid">
          <div class="media-setting">
            <div class="media-setting-head"><div><b>Photo principale du site</b><small>Utilisée dans le hero de l’accueil et sur la page À propos.</small></div></div>
            <div class="settings-image-preview"><img src="<?= e(site_image_url(configured_hero_image())) ?>" alt="Photo principale actuelle"></div>
            <label class="upload-dropzone upload-dropzone--compact" for="site_hero_image"><input id="site_hero_image" name="site_hero_image" type="file" accept="image/jpeg,image/png,image/webp"><strong>Changer la photo principale</strong><span>JPG, PNG ou WebP · 5 Mo max.</span></label>
          </div>
          <div class="media-setting">
            <div class="media-setting-head"><div><b>Photo de profil du header</b><small>Synchronisée automatiquement sur le site et le dashboard.</small></div></div>
            <div class="settings-image-preview settings-image-preview--avatar"><img src="<?= e(site_image_url(configured_profile_image())) ?>" alt="Photo de profil actuelle"></div>
            <label class="upload-dropzone upload-dropzone--compact" for="site_profile_image"><input id="site_profile_image" name="site_profile_image" type="file" accept="image/jpeg,image/png,image/webp"><strong>Changer la photo de profil</strong><span>JPG, PNG ou WebP · 5 Mo max.</span></label>
          </div>
        </div>
        <button class="admin-button admin-button--coral" type="submit">Enregistrer les paramètres →</button>
      </form>
    </section>

    <aside class="settings-aside">
      <section class="admin-panel settings-card">
        <h2>État du site</h2>
        <div class="setting-status"><i><?= $uploadsReady ? '✓' : '!' ?></i><div><b>Import d’images</b><small><?= $uploadsReady ? 'Le dossier des captures est accessible. Vous pouvez importer vos images dans vos projets.' : 'Le dossier des captures doit être accessible en écriture par MAMP.' ?></small></div></div>
        <div class="setting-status"><i class="<?= $smtpReady ? '' : 'is-warning' ?>"><?= $smtpReady ? '✓' : '!' ?></i><div><b>Réception des e-mails</b><small><?= $smtpReady ? 'Le SMTP semble configuré.' : 'Le SMTP n’est pas complet : les demandes restent enregistrées dans la boîte de réception du dashboard.' ?></small></div></div>
        <div class="setting-status"><i>↗</i><div><b>Adresse locale</b><small><?= e(site_url()) ?></small></div></div>
      </section>
      <section class="settings-tip"><h3>Référencement SEO</h3><p>Le titre, la meta description et le mot-clé de chaque page se gèrent maintenant sur leur propre page, séparée d’ici.</p><p><a href="<?= e(site_url('admin/seo.php')) ?>">Gérer le SEO →</a></p></section>
      <section class="settings-tip"><h3>Un projet, sans risque.</h3><p>Ajoutez-le d’abord comme <b>brouillon</b>, vérifiez son image et ses liens, puis passez-le en <b>publié</b> lorsque tout est prêt.</p><p><a href="<?= e(site_url('admin/projects.php')) ?>">Gérer mes projets →</a></p></section>
    </aside>
  </div>
</div></main>
<?php admin_footer(); ?>
