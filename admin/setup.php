<?php
declare(strict_types=1);

require __DIR__ . '/../includes/admin.php';

$error = '';
$adminExists = false;

try {
    $pdo = db();
    if (!$pdo) {
        throw new RuntimeException('La base de données est indisponible. Vérifiez la configuration MAMP.');
    }

    $adminExists = (int) $pdo->query('SELECT COUNT(*) FROM administrateur')->fetchColumn() > 0;
    if ($adminExists) {
        header('Location: ' . site_url('admin/login.php'));
        exit;
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
            $error = 'Votre session a expiré. Actualisez la page puis réessayez.';
        } else {
            $name = clean_text($_POST['name'] ?? '', 100);
            $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL) ?: '';
            $password = (string) ($_POST['password'] ?? '');
            $confirmation = (string) ($_POST['password_confirmation'] ?? '');

            if (mb_strlen($name) < 2) {
                $error = 'Indiquez un nom d’au moins 2 caractères.';
            } elseif ($email === '') {
                $error = 'Saisissez une adresse e-mail valide.';
            } elseif (strlen($password) < 12) {
                $error = 'Le mot de passe doit contenir au moins 12 caractères.';
            } elseif (!hash_equals($password, $confirmation)) {
                $error = 'Les deux mots de passe ne correspondent pas.';
            } else {
                $statement = $pdo->prepare('INSERT INTO administrateur (nom, email, mot_de_passe_hash) VALUES (:name, :email, :password)');
                $statement->execute([
                    'name' => $name,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ]);

                session_regenerate_id(true);
                $_SESSION['admin_id'] = (int) $pdo->lastInsertId();
                $_SESSION['admin_name'] = $name;
                security_log('Création du premier administrateur', (int) $_SESSION['admin_id']);
                header('Location: ' . site_url('admin/'));
                exit;
            }
        }
    }
} catch (Throwable $exception) {
    error_log('[portfolio] Admin setup: ' . $exception->getMessage());
    $error = 'La création du compte est momentanément indisponible. Vérifiez la connexion à la base de données.';
}

admin_header('Créer le premier administrateur');
?>
<main class="admin-login">
  <section class="auth-card">
    <p class="eyebrow"><span></span> Configuration initiale</p>
    <h1>Créer le compte administrateur</h1>
    <p>Cette page est disponible uniquement tant qu’aucun administrateur n’existe. Créez votre compte avant de publier le site.</p>
    <?php if ($error): ?><p class="admin-alert" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post" class="contact-form">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <div class="field"><label for="name">Nom complet</label><input id="name" name="name" required maxlength="100" autocomplete="name" value="<?= e($_POST['name'] ?? '') ?>"></div>
      <div class="field"><label for="email">E-mail de connexion</label><input id="email" name="email" type="email" required maxlength="150" autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>"></div>
      <div class="field"><label for="password">Mot de passe</label><input id="password" name="password" type="password" required minlength="12" autocomplete="new-password"><p class="form-note">12 caractères minimum.</p></div>
      <div class="field"><label for="password_confirmation">Confirmer le mot de passe</label><input id="password_confirmation" name="password_confirmation" type="password" required minlength="12" autocomplete="new-password"></div>
      <button class="button button--primary button--full" type="submit">Créer mon compte →</button>
    </form>
    <p class="form-note"><a href="<?= e(site_url('admin/login.php')) ?>">J’ai déjà un compte administrateur</a></p>
  </section>
</main>
<?php admin_footer(); ?>
