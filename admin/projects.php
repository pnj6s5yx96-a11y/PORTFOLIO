<?php
declare(strict_types=1);

require __DIR__ . '/../includes/admin.php';
require_admin();
$pdo = db();
$error = '';
$success = '';
$editing = null;

function project_form_data(array $source): array
{
    return [
        'id_projet' => filter_var($source['id_projet'] ?? null, FILTER_VALIDATE_INT) ?: null,
        'titre' => clean_text($source['titre'] ?? '', 150),
        'description' => trim(strip_tags((string) ($source['description'] ?? ''))),
        'stack_technique' => clean_text($source['stack_technique'] ?? '', 255),
        'lien_demo' => normalize_project_url($source['lien_demo'] ?? ''),
        'lien_repo' => normalize_project_url($source['lien_repo'] ?? ''),
        'image_url' => trim((string) ($source['image_url'] ?? '')),
        'ordre_affichage' => max(0, (int) ($source['ordre_affichage'] ?? 0)),
        'seo_title_fr' => clean_text($source['seo_title_fr'] ?? '', 180),
        'seo_description_fr' => clean_text($source['seo_description_fr'] ?? '', 320),
        'seo_keyword_fr' => clean_text($source['seo_keyword_fr'] ?? '', 150),
        'seo_title_en' => clean_text($source['seo_title_en'] ?? '', 180),
        'seo_description_en' => clean_text($source['seo_description_en'] ?? '', 320),
        'seo_keyword_en' => clean_text($source['seo_keyword_en'] ?? '', 150),
        'statut_publication' => in_array((string) ($source['statut_publication'] ?? 'brouillon'), ['brouillon', 'publie', 'archive'], true) ? (string) ($source['statut_publication'] ?? 'brouillon') : 'brouillon',
    ];
}

function normalize_project_url($url): string
{
    $url = trim((string) $url);
    if ($url === '') return '';
    // Facilite la saisie : github.com/... devient automatiquement https://github.com/...
    if (preg_match('~^(github\.com/|www\.)~i', $url) || (!str_contains($url, '://') && preg_match('~^[a-z0-9.-]+\.[a-z]{2,}(/.*)?$~i', $url))) {
        $url = 'https://' . $url;
    }
    return $url;
}

function delete_project_image(?string $image): void
{
    $image = trim((string) $image);
    if ($image === '' || external_url($image)) {
        return;
    }

    $relative = ltrim(str_replace('\\\\', '/', $image), '/');
    if (!preg_match('~^assets/uploads/projects/[A-Za-z0-9._-]+$~', $relative)) {
        return;
    }

    $file = dirname(__DIR__) . '/' . $relative;
    if (is_file($file)) {
        @unlink($file);
    }
}

function upload_project_image(array $file): ?string
{
    if (($file["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file["tmp_name"] ?? ""))) {
        throw new RuntimeException("Le téléversement de l’image a échoué.");
    }
    if ((int) ($file["size"] ?? 0) > 4 * 1024 * 1024) {
        throw new RuntimeException("L’image ne doit pas dépasser 4 Mo.");
    }
    $extensions = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file((string) $file["tmp_name"]) ?: "";
    if (!isset($extensions[$mime])) {
        throw new RuntimeException("Utilisez une image JPG, PNG ou WebP.");
    }
    $directory = dirname(__DIR__) . "/assets/uploads/projects";
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException("Le dossier des images ne peut pas être créé.");
    }
    $filename = bin2hex(random_bytes(16)) . "." . $extensions[$mime];
    if (!move_uploaded_file((string) $file["tmp_name"], $directory . "/" . $filename)) {
        throw new RuntimeException("Impossible d’enregistrer l’image.");
    }
    // Stocker un chemin local rend le projet portable en environnement MAMP.
    return "assets/uploads/projects/" . $filename;
}

if (!$pdo) {
    $error = 'La base de données est indisponible.';
} elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Votre session a expiré. Actualisez la page puis réessayez.';
    } else {
        $action = clean_text($_POST['action'] ?? '', 20);
        try {
            if (in_array($action, ['archive', 'restore'], true)) {
                $id = filter_var($_POST['id_projet'] ?? null, FILTER_VALIDATE_INT) ?: 0;
                if ($id <= 0) {
                    throw new RuntimeException('Projet introuvable.');
                }
                $status = $action === 'archive' ? 'archive' : 'brouillon';
                $pdo->prepare('UPDATE projet SET statut_publication = :status WHERE id_projet = :id')->execute(['status' => $status, 'id' => $id]);
                security_log($action === 'archive' ? 'Projet archivé' : 'Projet restauré en brouillon', (int) $_SESSION['admin_id']);
                $success = $action === 'archive' ? "Projet archivé : il reste disponible dans le dashboard et n'apparaît plus sur le site." : 'Projet restauré en brouillon.';
            }
            if ($action === 'delete') {
                $id = filter_var($_POST['id_projet'] ?? null, FILTER_VALIDATE_INT) ?: 0;
                if ($id <= 0) {
                    throw new RuntimeException('Projet introuvable.');
                }
                if (($_POST['confirm_delete'] ?? '') !== '1') {
                    throw new RuntimeException('Confirmez la suppression définitive du projet.');
                }

                $stmt = $pdo->prepare('SELECT titre, image_url FROM projet WHERE id_projet = :id LIMIT 1');
                $stmt->execute(['id' => $id]);
                $projectToDelete = $stmt->fetch();
                if (!$projectToDelete) {
                    throw new RuntimeException('Ce projet n’existe plus.');
                }

                $pdo->beginTransaction();
                try {
                    // Les pages liées utilisent ON DELETE SET NULL dans le schéma fourni.
                    $pdo->prepare('DELETE FROM projet WHERE id_projet = :id')->execute(['id' => $id]);
                    $pdo->commit();
                } catch (Throwable $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $exception;
                }

                delete_project_image($projectToDelete['image_url'] ?? null);
                security_log('Projet supprimé définitivement', (int) $_SESSION['admin_id']);
                $success = 'Projet supprimé définitivement.';
                $editing = project_form_data([]);
            }
            if ($action === 'remove_image') {
                $id = filter_var($_POST['id_projet'] ?? null, FILTER_VALIDATE_INT) ?: 0;
                if ($id <= 0) {
                    throw new RuntimeException('Projet introuvable.');
                }
                $stmt = $pdo->prepare('SELECT image_url FROM projet WHERE id_projet = :id LIMIT 1');
                $stmt->execute(['id' => $id]);
                $current = $stmt->fetchColumn();
                $pdo->prepare('UPDATE projet SET image_url = NULL WHERE id_projet = :id')->execute(['id' => $id]);
                delete_project_image($current ?: null);
                security_log('Photo de projet retirée', (int) $_SESSION['admin_id']);
                $success = 'Photo retirée du projet.';
            }
            if ($action === 'save') {
                $data = project_form_data($_POST);
                $uploadedImage = upload_project_image($_FILES["project_image"] ?? []);
                if ($uploadedImage !== null) {
                    $data["image_url"] = $uploadedImage;
                }
                if (mb_strlen($data['titre']) < 2 || mb_strlen($data['description']) < 20 || $data['stack_technique'] === '') {
                    throw new RuntimeException('Indiquez un titre, une description de 20 caractères minimum et les technologies utilisées.');
                }
                // Les liens externes doivent être absolus. Une image importée est, elle, un chemin local MAMP.
                foreach (['lien_demo', 'lien_repo'] as $field) {
                    if ($data[$field] !== '' && !external_url($data[$field])) {
                        throw new RuntimeException('Le lien « ' . $field . ' » doit être une adresse web valide (ex. https://github.com/...).');
                    }
                }
                if ($data['image_url'] !== '' && !external_url($data['image_url']) && !preg_match('~^(?:assets/|/|\./)~i', $data['image_url'])) {
                    throw new RuntimeException('L’image doit être importée depuis votre ordinateur ou utiliser une URL http:// ou https://.');
                }
                $parameters = [
                    'title' => $data['titre'], 'description' => $data['description'], 'stack' => $data['stack_technique'],
                    'demo' => $data['lien_demo'] ?: null, 'repo' => $data['lien_repo'] ?: null, 'image' => $data['image_url'] ?: null, 'order' => $data['ordre_affichage'], 'status' => $data['statut_publication'],
                ];
                if ($data['id_projet']) {
                    $parameters['id'] = $data['id_projet'];
                    $pdo->prepare('UPDATE projet SET titre = :title, description = :description, stack_technique = :stack, lien_demo = :demo, lien_repo = :repo, image_url = :image, ordre_affichage = :order, statut_publication = :status WHERE id_projet = :id')->execute($parameters);
                    $projectId = (int)$data['id_projet'];
                    security_log('Projet modifié', (int) $_SESSION['admin_id']);
                    $success = 'Projet mis à jour.';
                } else {
                    $pdo->prepare('INSERT INTO projet (titre, description, stack_technique, lien_demo, lien_repo, image_url, ordre_affichage, statut_publication) VALUES (:title, :description, :stack, :demo, :repo, :image, :order, :status)')->execute($parameters);
                    $projectId = (int)$pdo->lastInsertId();
                    security_log('Projet créé', (int) $_SESSION['admin_id']);
                    $success = $data['statut_publication'] === 'publie' ? 'Nouveau projet publié dans le portfolio.' : 'Projet enregistré en brouillon : il reste privé.';
                }
                seo_meta_save('project:' . $projectId, 'fr', ['title'=>$data['seo_title_fr'], 'description'=>$data['seo_description_fr'], 'focus_keyword'=>$data['seo_keyword_fr']]);
                seo_meta_save('project:' . $projectId, 'en', ['title'=>$data['seo_title_en'], 'description'=>$data['seo_description_en'], 'focus_keyword'=>$data['seo_keyword_en']]);
                $editing = project_form_data([]);
            }
        } catch (Throwable $exception) {
            error_log('[portfolio] Project manager: ' . $exception->getMessage());
            $error = $exception->getMessage();
            if (($action ?? '') === 'save') {
                $editing = project_form_data($_POST);
            }
        }
    }
}

$projects = [];
try {
    if ($pdo) {
        $projects = $pdo->query('SELECT * FROM projet ORDER BY CASE WHEN ordre_affichage = 0 THEN 1 ELSE 0 END ASC, ordre_affichage ASC, id_projet ASC')->fetchAll();
        $editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT) ?: null;
        if ($editId && !$editing) {
            foreach ($projects as $project) {
                if ((int) $project['id_projet'] === $editId) {
                    $editing = project_form_data($project);
                    $seoFr = seo_project_get((int)$project['id_projet'], 'fr');
                    $seoEn = seo_project_get((int)$project['id_projet'], 'en');
                    $editing['seo_title_fr'] = $seoFr['title']; $editing['seo_description_fr'] = $seoFr['description']; $editing['seo_keyword_fr'] = $seoFr['focus_keyword'];
                    $editing['seo_title_en'] = $seoEn['title']; $editing['seo_description_en'] = $seoEn['description']; $editing['seo_keyword_en'] = $seoEn['focus_keyword'];
                    break;
                }
            }
        }
    }
} catch (Throwable $exception) {
    error_log('[portfolio] Project list: ' . $exception->getMessage());
    $error = 'Impossible de charger les projets.';
}
$editing ??= project_form_data([]);

admin_header('Mes projets', 'projects');
?>
<main class="admin-main"><div class="admin-shell">
  <div class="admin-title-row"><div><p class="eyebrow"><span></span> Portfolio public</p><h1>Mes projets</h1><p>Ajoute, modifie, classe et illustre tes réalisations. Chaque changement est visible automatiquement sur le site public.</p></div><a class="admin-button admin-button--light" href="<?= e(site_url('projets.php')) ?>" target="_blank" rel="noopener">Voir le portfolio ↗</a></div>
  <?php if ($error): ?><p class="admin-alert" role="alert"><?= e($error) ?></p><?php endif; ?>
  <?php if ($success): ?><p class="admin-success" role="status"><?= e($success) ?></p><?php endif; ?>
  <div class="project-manager">
    <section class="admin-panel project-form-card">
      <div class="project-form-heading">
        <div><p class="eyebrow"><span></span> Enregistrement maîtrisé</p><h2><?= $editing['id_projet'] ? 'Modifier le projet' : 'Ajouter un projet' ?></h2></div>
        <span class="form-step"><?= $editing['id_projet'] ? 'Édition' : 'Nouveau' ?></span>
      </div>
      <p class="form-help">Ajoute les informations essentielles, choisis une capture et colle les liens. Choisissez si le projet reste privé en brouillon ou s’il est publié sur votre portfolio.</p>
      <form method="post" enctype="multipart/form-data" class="contact-form project-form" id="project-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id_projet" value="<?= e((string) $editing['id_projet']) ?>">

        <div class="project-form-section">
          <div class="project-section-title"><span>01</span><div><b>Informations du projet</b><small>Ce que le visiteur verra sur le portfolio.</small></div></div>
          <div class="field"><label for="titre">Nom du projet <i>*</i></label><input id="titre" name="titre" required maxlength="150" value="<?= e($editing['titre']) ?>" placeholder="Ex. Boutique Kora"></div>
          <div class="field"><label for="description">Description <i>*</i></label><textarea id="description" name="description" required minlength="20" maxlength="4000" placeholder="Explique brièvement le besoin, ce que tu as réalisé et la valeur apportée…"><?= e($editing['description']) ?></textarea><small>Une description courte et concrète aide le visiteur à comprendre le projet.</small></div>
          <div class="field"><label for="stack_technique">Technologies <i>*</i></label><input id="stack_technique" name="stack_technique" required maxlength="255" value="<?= e($editing['stack_technique']) ?>" placeholder="PHP, MySQL, JavaScript"></div>
        </div>

        <div class="project-form-section">
          <div class="project-section-title"><span>02</span><div><b>Capture du projet</b><small>Une image réelle donne immédiatement confiance.</small></div></div>
          <label class="upload-dropzone" for="project_image" id="project-dropzone">
            <input id="project_image" name="project_image" type="file" accept="image/jpeg,image/png,image/webp">
            <span class="upload-icon">↑</span><strong>Choisir une capture</strong><span>ou glisser-déposer ici</span><small>JPG, PNG ou WebP · 4 Mo maximum</small>
          </label>
          <div id="image-preview" class="image-preview" <?= $editing['image_url'] ? '' : 'hidden' ?>>
            <?php if ($editing['image_url']): ?><img src="<?= e(project_image_url($editing['image_url']) ?? '') ?>" alt="Aperçu actuel"><span>Aperçu actuel</span><?php endif; ?>
          </div>
        </div>

        <div class="project-form-section">
          <div class="project-section-title"><span>03</span><div><b>Liens du projet</b><small>Facultatifs, mais recommandés pour montrer le travail réalisé.</small></div></div>
          <div class="link-field-card">
            <div class="link-field-icon">↗</div><div class="field"><label for="lien_demo">URL du site / Démo</label><input id="lien_demo" name="lien_demo" maxlength="255" value="<?= e($editing['lien_demo']) ?>" placeholder="https://mon-site.com" inputmode="url" autocomplete="url"><small>Colle simplement l’adresse du site. <b>https://</b> sera ajouté automatiquement si nécessaire.</small><div class="url-preview" id="demo-preview"></div></div>
          </div>
          <div class="link-field-card">
            <div class="link-field-icon link-field-icon--github">GH</div><div class="field"><label for="lien_repo">Dépôt GitHub</label><input id="lien_repo" name="lien_repo" maxlength="255" value="<?= e($editing['lien_repo']) ?>" placeholder="https://github.com/compte/projet" inputmode="url" autocomplete="url"><small>Tu peux coller <b>github.com/compte/projet</b> sans écrire https://.</small><div class="url-preview" id="repo-preview"></div></div>
          </div>
        </div>

        <div class="project-form-section project-form-section--compact">
          <div class="field"><label for="ordre_affichage">Ordre d’affichage</label><input id="ordre_affichage" name="ordre_affichage" type="number" min="0" max="9999" value="<?= e((string) $editing['ordre_affichage']) ?>"><small>1 = premier projet · 0 = classement automatique en fin de liste.</small></div>
        </div>

        <div class="project-form-section project-form-section--seo">
          <div class="project-section-title"><span>04</span><div><b>Référencement SEO du projet</b><small>Ces champs contrôlent le titre et la description affichés dans les résultats de recherche.</small></div></div>
          <div class="seo-language-card"><h3>🇫🇷 Français</h3><div class="field"><label for="seo_title_fr">Title SEO</label><input id="seo_title_fr" name="seo_title_fr" maxlength="180" value="<?= e($editing['seo_title_fr'] ?? '') ?>" placeholder="Pressing Manager — Application web PHP & MySQL | Portfolio"></div><div class="field"><label for="seo_description_fr">Meta description</label><textarea id="seo_description_fr" name="seo_description_fr" maxlength="320" placeholder="Décrivez brièvement le projet pour Google et les visiteurs."><?= e($editing['seo_description_fr'] ?? '') ?></textarea></div><div class="field"><label for="seo_keyword_fr">Mot-clé principal</label><input id="seo_keyword_fr" name="seo_keyword_fr" maxlength="150" value="<?= e($editing['seo_keyword_fr'] ?? '') ?>" placeholder="application web PHP MySQL"></div></div>
          <div class="seo-language-card"><h3>🇬🇧 English</h3><div class="field"><label for="seo_title_en">SEO title</label><input id="seo_title_en" name="seo_title_en" maxlength="180" value="<?= e($editing['seo_title_en'] ?? '') ?>" placeholder="Pressing Manager — PHP & MySQL Web Application | Portfolio"></div><div class="field"><label for="seo_description_en">Meta description</label><textarea id="seo_description_en" name="seo_description_en" maxlength="320" placeholder="Briefly describe the project for search engines and visitors."><?= e($editing['seo_description_en'] ?? '') ?></textarea></div><div class="field"><label for="seo_keyword_en">Primary keyword</label><input id="seo_keyword_en" name="seo_keyword_en" maxlength="150" value="<?= e($editing['seo_keyword_en'] ?? '') ?>" placeholder="PHP MySQL web application"></div></div>
        </div>

        <div class="project-form-section project-form-section--status">
          <div class="project-section-title"><span>05</span><div><b>Visibilité du projet</b><small>Choisis exactement ce qui sera visible sur ton site.</small></div></div>
          <div class="field"><label for="statut_publication">Statut de publication</label>
            <select id="statut_publication" name="statut_publication">
              <option value="brouillon" <?= $editing['statut_publication'] === 'brouillon' ? 'selected' : '' ?>>Brouillon — privé, visible uniquement ici</option>
              <option value="publie" <?= $editing['statut_publication'] === 'publie' ? 'selected' : '' ?>>Publié — visible sur le portfolio</option>
              <option value="archive" <?= $editing['statut_publication'] === 'archive' ? 'selected' : '' ?>>Archivé — conservé, mais masqué du site</option>
            </select>
          </div>
        </div>

        <div class="publish-summary"><span>✓</span><div><b>Publication maîtrisée</b><small>Le statut choisi ci-dessus détermine si ce projet est public, privé ou archivé.</small></div></div>
        <button class="admin-button admin-button--coral project-publish-button" type="submit"><span><?= $editing['id_projet'] ? 'Enregistrer les modifications' : 'Enregistrer le projet' ?></span> <b>→</b></button>
        <?php if ($editing['id_projet']): ?><a class="admin-button admin-button--light" href="<?= e(site_url('admin/projects.php')) ?>">Annuler</a><?php endif; ?>
      </form>
    </section>
    <section class="admin-panel"><h2>Bibliothèque des projets <small style="color:#71838a;font:12px var(--sans)"><?= count($projects) ?></small></h2><div class="admin-project-list"><?php foreach ($projects as $project): ?><article class="admin-project">
  <div class="admin-project-main">
    <span class="project-order"><?= e((string) $project['ordre_affichage']) ?></span>
    <div class="admin-project-thumb">
      <?php if (!empty($project['image_url'])): ?>
        <img src="<?= e(project_image_url($project['image_url']) ?? '') ?>" alt="Photo du projet <?= e($project['titre']) ?>" loading="lazy" width="60" height="45">
      <?php else: ?>
        <span class="admin-project-thumb--empty" aria-hidden="true">◇</span>
      <?php endif; ?>
    </div>
    <div class="admin-project-copy">
      <b><?= e($project['titre']) ?></b>
      <p><?= e(mb_strimwidth($project['description'], 0, 95, '…')) ?></p>
      <span class="admin-project-status admin-project-status--<?= e((string) $project['statut_publication']) ?>">
        <?= e((string) $project['statut_publication'] === 'publie' ? 'Publié' : ((string) $project['statut_publication'] === 'archive' ? 'Archivé' : 'Brouillon')) ?>
      </span>
    </div>
  </div>
  <div class="admin-project-actions">
    <a class="admin-button admin-button--light" href="<?= e(site_url('admin/projects.php?edit=' . (int) $project['id_projet'])) ?>">Modifier</a>
    <?php if (!empty($project['image_url'])): ?>
      <form method="post" data-archive-form onsubmit="return confirm('Retirer la photo de ce projet ?');">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="remove_image">
        <input type="hidden" name="id_projet" value="<?= e((string) $project['id_projet']) ?>">
        <button class="admin-button admin-button--light" type="submit">Retirer la photo</button>
      </form>
    <?php endif; ?>
    <?php if ($project['statut_publication'] === 'archive'): ?>
      <form method="post" data-archive-form>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="restore">
        <input type="hidden" name="id_projet" value="<?= e((string) $project['id_projet']) ?>">
        <button class="admin-button admin-button--light" type="submit">Restaurer</button>
      </form>
    <?php else: ?>
      <form method="post" data-archive-form>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="archive">
        <input type="hidden" name="id_projet" value="<?= e((string) $project['id_projet']) ?>">
        <button class="admin-button admin-button--light" type="submit">Archiver</button>
      </form>
    <?php endif; ?>
    <form method="post" class="delete-project-form" onsubmit="return confirm('Supprimer définitivement ce projet ?\n\nCette action est irréversible.');">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id_projet" value="<?= e((string) $project['id_projet']) ?>">
      <input type="hidden" name="confirm_delete" value="1">
      <button class="admin-button admin-button--danger" type="submit">Supprimer</button>
    </form>
  </div>
</article><?php endforeach; ?><?php if (!$projects): ?><p>Aucun projet créé. Utilise le formulaire pour publier ta première réalisation.</p><?php endif; ?></div></section>
  </div>
</div></main>
<script nonce="<?= e(csp_nonce()) ?>">
(() => {
  const file = document.getElementById('project_image');
  const preview = document.getElementById('image-preview');
  if (!file || !preview) return;
  file.addEventListener('change', () => {
    const selected = file.files && file.files[0];
    if (!selected) return;
    const url = URL.createObjectURL(selected);
    preview.hidden = false;
    preview.innerHTML = '<img src="' + url + '" alt="Aperçu de la capture sélectionnée"><span>Nouvelle capture</span>';
  });
})();
</script>
<script nonce="<?= e(csp_nonce()) ?>">
(() => {
  const form = document.getElementById('project-form');
  const normalize = value => {
    value = (value || '').trim();
    if (!value) return '';
    if (!/^[a-z][a-z0-9+.-]*:\/\//i.test(value)) value = 'https://' + value.replace(/^\/+/, '');
    return value;
  };
  const previewUrl = (inputId, previewId) => {
    const input = document.getElementById(inputId), preview = document.getElementById(previewId);
    if (!input || !preview) return;
    const update = () => { const url = normalize(input.value); preview.innerHTML = url ? '<a href="' + url.replace(/"/g, '&quot;') + '" target="_blank" rel="noopener noreferrer">↗ ' + url.replace(/</g, '&lt;') + '</a>' : ''; };
    input.addEventListener('input', update); input.addEventListener('blur', () => { if (input.value.trim()) input.value = normalize(input.value); update(); }); update();
  };
  previewUrl('lien_demo','demo-preview'); previewUrl('lien_repo','repo-preview');
  const file = document.getElementById('project_image'), preview = document.getElementById('image-preview'), drop = document.getElementById('project-dropzone');
  const showImage = f => { if (!f || !f.type.startsWith('image/')) return; const src = URL.createObjectURL(f); preview.hidden = false; preview.innerHTML = '<img src="' + src + '" alt="Aperçu de la capture"><span>Nouvelle capture prête à être publiée</span>'; };
  file?.addEventListener('change', () => showImage(file.files?.[0]));
  ['dragenter','dragover'].forEach(e => drop?.addEventListener(e, ev => { ev.preventDefault(); drop.classList.add('is-dragover'); }));
  ['dragleave','drop'].forEach(e => drop?.addEventListener(e, ev => { ev.preventDefault(); drop.classList.remove('is-dragover'); }));
  drop?.addEventListener('drop', ev => { const f = ev.dataTransfer?.files?.[0]; if (f) { try { const dt = new DataTransfer(); dt.items.add(f); file.files = dt.files; } catch (_) {} showImage(f); } });
  form?.addEventListener('submit', () => { ['lien_demo','lien_repo'].forEach(id => { const input=document.getElementById(id); if(input && input.value.trim()) input.value=normalize(input.value); }); });
  document.querySelectorAll('[data-archive-form]').forEach(f => f.addEventListener('submit', ev => {
    const action = f.querySelector('[name="action"]')?.value;
    if (action === 'archive' && !window.confirm('Archiver ce projet ?\n\nIl restera dans votre dashboard, mais ne sera plus visible sur le site public.')) ev.preventDefault();
    if (action === 'restore' && !window.confirm('Restaurer ce projet en brouillon ?')) ev.preventDefault();
  }));
})();
</script>
<?php admin_footer(); ?>
