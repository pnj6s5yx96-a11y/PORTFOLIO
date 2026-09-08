<?php
declare(strict_types=1);
require __DIR__ . '/../includes/admin.php';
if (is_admin()) { header("Location: " . site_url("admin/")); exit; }
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) { $error = 'Votre session a expiré. Actualisez la page.'; }
    else {
        $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL) ?: '';
        $password = (string) ($_POST['password'] ?? '');
        $pdo = db();
        try {
            $user = $pdo && $email ? $pdo->prepare('SELECT * FROM administrateur WHERE email = :email LIMIT 1') : null;
            if ($user) { $user->execute(['email' => $email]); $user = $user->fetch(); }
            $lock = $pdo && $email ? $pdo->prepare('SELECT verrouille_jusqu_a FROM tentative_connexion WHERE type_compte = "administrateur" AND email = :email AND ip_anonymisee = :ip LIMIT 1') : null;
            if ($lock) { $lock->execute(['email' => $email, 'ip' => anonymised_ip()]); $lock = $lock->fetchColumn(); }
            $locked = $lock && strtotime((string) $lock) > time();
            if (!$user || $locked || !password_verify($password, $user['mot_de_passe_hash'])) {
                if ($user && !$locked) {
                    $pdo->prepare('INSERT INTO tentative_connexion (type_compte, email, ip_anonymisee, tentatives, dernier_echec, verrouille_jusqu_a) VALUES ("administrateur", :email, :ip, 1, NOW(), NULL) ON DUPLICATE KEY UPDATE tentatives = IF(verrouille_jusqu_a IS NOT NULL AND verrouille_jusqu_a > NOW(), tentatives, tentatives + 1), dernier_echec = NOW(), verrouille_jusqu_a = IF(tentatives >= 5, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NULL)')->execute(['email' => $email, 'ip' => anonymised_ip()]);
                }
                security_log('Échec de connexion administrateur');
                $error = $locked ? 'Compte temporairement verrouillé. Réessayez dans quelques minutes.' : 'Identifiants invalides.';
            } else {
                $pdo->prepare('DELETE FROM tentative_connexion WHERE type_compte = "administrateur" AND email = :email AND ip_anonymisee = :ip')->execute(['email' => $email, 'ip' => anonymised_ip()]);
                session_regenerate_id(true); $_SESSION['admin_id'] = (int) $user['id_admin']; $_SESSION['admin_name'] = $user['nom'];
                security_log('Connexion administrateur réussie', (int) $user['id_admin']); header("Location: " . site_url("admin/")); exit;
            }
        } catch (Throwable $exception) { error_log('[portfolio] Admin login: ' . $exception->getMessage()); $error = 'Connexion momentanément indisponible.'; }
    }
}
admin_header('Connexion');
?>
<main class="admin-login"><section class="auth-card"><p class="eyebrow"><span></span> Espace sécurisé</p><h1>Administration</h1><p>Connectez-vous pour consulter l’activité et gérer les demandes reçues.</p><?php if ($error): ?><p class="admin-alert" role="alert"><?= e($error) ?></p><?php endif; ?><form method="post" class="contact-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="field"><label for="email">E-mail</label><input id="email" name="email" type="email" required autocomplete="username"></div><div class="field"><label for="password">Mot de passe</label><input id="password" name="password" type="password" required autocomplete="current-password"></div><button class="button button--primary button--full" type="submit">Se connecter →</button></form><p class="form-note">Accès réservé à l’administrateur. Si c’est la première utilisation, <a href="<?= e(site_url('admin/setup.php')) ?>">créez le premier compte</a>.</p></section></main>
<?php admin_footer(); ?>
