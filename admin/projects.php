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
        'lien_demo' => trim((string) ($source['lien_demo'] ?? '')),
        'lien_repo' => trim((string) ($source['lien_repo'] ?? '')),
        'image_url' => trim((string) ($source['image_url'] ?? '')),
        'ordre_affichage' => max(0, (int) ($source['ordre_affichage'] ?? 0)),
    ];
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
    $details = ((string) $file["tmp_name"]);
    $extensions = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
    $mime = is_array($details) ? (string) ($details["mime"] ?? "") : "";
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
    return site_url("assets/uploads/projects/" . $filename);
}

if (!$pdo) {
    $error = 'La base de données est indisponible.';
} elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Votre session a expiré. Actualisez la page puis réessayez.';
    } else {
        $action = clean_text($_POST['action'] ?? '', 20);
        try {
            if ($action === 'delete') {
                $id = filter_var($_POST['id_projet'] ?? null, FILTER_VALIDATE_INT) ?: 0;
                if ($_POST['confirm_delete'] ?? '' !== 'yes') {
                    throw new RuntimeException('Cochez la confirmation avant de supprimer un projet.');
                }
                $pdo->prepare('DELETE FROM projet WHERE id_projet = :id')->execute(['id' => $id]);
                security_log('Projet supprimé', (int) $_SESSION['admin_id']);
                $success = 'Projet supprimé.';
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
                foreach (['lien_demo', 'lien_repo', 'image_url'] as $field) {
                    if ($data[$field] !== '' && !external_url($data[$field])) {
                        throw new RuntimeException('Les liens de démo, dépôt et image doivent commencer par https:// ou http://.');
                    }
                }
                $parameters = [
                    'title' => $data['titre'], 'description' => $data['description'], 'stack' => $data['stack_technique'],
                    'demo' => $data['lien_demo'] ?: null, 'repo' => $data['lien_repo'] ?: null, 'image' => $data['image_url'] ?: null, 'order' => $data['ordre_affichage'],
                ];
                if ($data['id_projet']) {
                    $parameters['id'] = $data['id_projet'];
                    $pdo->prepare('UPDATE projet SET titre = :title, description = :description, stack_technique = :stack, lien_demo = :demo, lien_repo = :repo, image_url = :image, ordre_affichage = :order WHERE id_projet = :id')->execute($parameters);
                    security_log('Projet modifié', (int) $_SESSION['admin_id']);
                    $success = 'Projet mis à jour.';
                } else {
                    $pdo->prepare('INSERT INTO projet (titre, description, stack_technique, lien_demo, lien_repo, image_url, ordre_affichage) VALUES (:title, :description, :stack, :demo, :repo, :image, :order)')->execute($parameters);
                    security_log('Projet créé', (int) $_SESSION['admin_id']);
                    $success = 'Nouveau projet publié dans le portfolio.';
                }
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
        $projects = $pdo->query('SELECT * FROM projet ORDER BY ordre_affichage ASC, id_projet ASC')->fetchAll();
        $editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT) ?: null;
        if ($editId && !$editing) {
            foreach ($projects as $project) {
                if ((int) $project['id_projet'] === $editId) {
                    $editing = project_form_data($project);
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
    <section class="admin-panel project-form-card"><h2><?= $editing['id_projet'] ? 'Modifier le projet' : 'Ajouter un projet' ?></h2><form method="post" enctype="multipart/form-data" class="contact-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id_projet" value="<?= e((string) $editing['id_projet']) ?>"><div class="field"><label for="titre">Nom du projet</label><input id="titre" name="titre" required maxlength="150" value="<?= e($editing['titre']) ?>" placeholder="Ex. Boutique Kora"></div><div class="field"><label for="description">Description</label><textarea id="description" name="description" required minlength="20" maxlength="4000" placeholder="Le besoin, la solution et le résultat…"><?= e($editing['description']) ?></textarea></div><div class="field"><label for="stack_technique">Technologies</label><input id="stack_technique" name="stack_technique" required maxlength="255" value="<?= e($editing['stack_technique']) ?>" placeholder="PHP, MySQL, JavaScript"></div><div class="field"><label for="image_url">Lien de l’image <span class="optional">(optionnel)</span></label><input id="image_url" name="image_url" type="url" maxlength="255" value="<?= e($editing['image_url']) ?>" placeholder="https://…"></div><div class="field"><label for="project_image">ou importer une image <span class="optional">(JPG, PNG, WebP · 4 Mo max.)</span></label><input id="project_image" name="project_image" type="file" accept="image/jpeg,image/png,image/webp"></div><div class="field"><label for="lien_demo">Lien de démo <span class="optional">(optionnel)</span></label><input id="lien_demo" name="lien_demo" type="url" maxlength="255" value="<?= e($editing['lien_demo']) ?>" placeholder="https://…"></div><div class="field"><label for="lien_repo">Lien du dépôt <span class="optional">(optionnel)</span></label><input id="lien_repo" name="lien_repo" type="url" maxlength="255" value="<?= e($editing['lien_repo']) ?>" placeholder="https://…"></div><div class="field"><label for="ordre_affichage">Ordre d’affichage</label><input id="ordre_affichage" name="ordre_affichage" type="number" min="0" max="9999" value="<?= e((string) $editing['ordre_affichage']) ?>"></div><button class="admin-button admin-button--coral" type="submit"><?= $editing['id_projet'] ? 'Enregistrer les modifications' : 'Publier ce projet' ?></button><?php if ($editing['id_projet']): ?><a class="admin-button admin-button--light" href="<?= e(site_url('admin/projects.php')) ?>" style="margin-left:7px">Annuler</a><?php endif; ?></form></section>
    <section class="admin-panel"><h2>Projets publiés <small style="color:#71838a;font:12px var(--sans)"><?= count($projects) ?></small></h2><div class="admin-project-list"><?php foreach ($projects as $project): ?><article class="admin-project"><span class="project-order"><?= e((string) $project['ordre_affichage']) ?></span><div class="admin-project-copy"><b><?= e($project['titre']) ?></b><p><?= e(mb_strimwidth($project['description'], 0, 95, '…')) ?></p></div><div class="admin-project-actions"><a class="admin-button admin-button--light" href="<?= e(site_url('admin/projects.php?edit=' . (int) $project['id_projet'])) ?>">Modifier</a><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id_projet" value="<?= e((string) $project['id_projet']) ?>"><label class="delete-check"><input name="confirm_delete" value="yes" type="checkbox"> Confirmer</label><button class="admin-button" type="submit">Supprimer</button></form></div></article><?php endforeach; ?><?php if (!$projects): ?><p>Aucun projet créé. Utilise le formulaire pour publier ta première réalisation.</p><?php endif; ?></div></section>
  </div>
</div></main>
<?php admin_footer(); ?>
