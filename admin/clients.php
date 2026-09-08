<?php
declare(strict_types=1);

require __DIR__ . '/../includes/admin.php';
require_admin();
$pdo = db();
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Votre session a expiré. Actualisez la page.';
    } else {
        $name = clean_text($_POST['name'] ?? '', 100);
        $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL) ?: '';
        $phone = clean_text($_POST['phone'] ?? '', 20);
        $password = (string) ($_POST['password'] ?? '');
        $requestId = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
        if (mb_strlen($name) < 2 || !$email || strlen($password) < 12) {
            $error = 'Renseignez un nom, un e-mail valide et un mot de passe temporaire de 12 caractères minimum.';
        } elseif (!$pdo) {
            $error = 'Base de données indisponible.';
        } else {
            try {
                $pdo->beginTransaction();
                $insert = $pdo->prepare('INSERT INTO client_partenaire (nom, email, telephone, mot_de_passe_hash) VALUES (:nom, :email, :telephone, :password)');
                $insert->execute(['nom' => $name, 'email' => $email, 'telephone' => $phone ?: null, 'password' => password_hash($password, PASSWORD_DEFAULT)]);
                $clientId = (int) $pdo->lastInsertId();
                if ($requestId) {
                    $update = $pdo->prepare('UPDATE demande_contact SET id_client = :client, statut = "Converti" WHERE id_demande = :request');
                    $update->execute(['client' => $clientId, 'request' => $requestId]);
                }
                $pdo->commit(); security_log('Création d’un compte partenaire', (int) $_SESSION['admin_id']);
                $success = 'Compte partenaire créé. Transmettez les identifiants de manière sécurisée.';
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('[portfolio] Client creation: ' . $exception->getMessage());
                $error = 'Création impossible. Vérifiez que cette adresse e-mail n’est pas déjà utilisée.';
            }
        }
    }
}
$clients = []; $convertibleRequests = [];
try {
    if ($pdo) {
        $clients = $pdo->query('SELECT c.*, COUNT(d.id_demande) AS demandes FROM client_partenaire c LEFT JOIN demande_contact d ON d.id_client = c.id_client GROUP BY c.id_client ORDER BY c.date_creation DESC')->fetchAll();
        $convertibleRequests = $pdo->query('SELECT id_demande, nom, email, type_projet FROM demande_contact WHERE id_client IS NULL ORDER BY date_soumission DESC')->fetchAll();
    }
} catch (Throwable $exception) { error_log('[portfolio] Client list: ' . $exception->getMessage()); }
admin_header('Partenaires', 'clients');
?>
<main class="admin-main"><div class="admin-shell"><div class="admin-title-row"><div><p class="eyebrow"><span></span> Comptes utilisateurs</p><h1>Partenaires</h1><p>Créez un espace de suivi uniquement après validation de la relation.</p></div><a class="admin-button admin-button--light" href="<?= e(site_url('admin/')) ?>">← Tableau de bord</a></div><div class="admin-stats"><section class="admin-panel"><h2>Nouveau compte</h2><?php if ($error): ?><p class="admin-alert" role="alert"><?= e($error) ?></p><?php endif; ?><?php if ($success): ?><p style="color:#317343;font-size:13px"><?= e($success) ?></p><?php endif; ?><form method="post" class="contact-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="field"><label for="name">Nom ou entreprise</label><input id="name" name="name" required maxlength="100"></div><div class="field"><label for="email">E-mail</label><input id="email" name="email" type="email" required maxlength="150"></div><div class="field"><label for="phone">Téléphone</label><input id="phone" name="phone" maxlength="20"></div><div class="field"><label for="password">Mot de passe temporaire</label><input id="password" name="password" type="password" minlength="12" required autocomplete="new-password"></div><div class="field"><label for="request_id">Rattacher à une demande</label><select id="request_id" name="request_id"><option value="">Aucune demande</option><?php foreach ($convertibleRequests as $request): ?><option value="<?= e((string) $request['id_demande']) ?>">#<?= e((string) $request['id_demande']) ?> — <?= e($request['nom']) ?> (<?= e((string) $request['type_projet']) ?>)</option><?php endforeach; ?></select></div><button class="admin-button" type="submit">Créer le compte</button></form></section><section class="admin-panel"><h2>Accès partenaire</h2><p>Le partenaire se connecte ensuite sur <code>/espace-client/login.php</code> et ne voit que ses propres demandes.</p><p>Pour renforcer la sécurité, communiquez le mot de passe temporaire par un canal distinct de l’e-mail.</p><p><a class="admin-button" href="<?= e(site_url('espace-client/login.php')) ?>" target="_blank" rel="noopener">Ouvrir l’espace partenaire ↗</a></p></section></div><section class="admin-panel"><h2>Comptes existants</h2><table class="admin-table"><thead><tr><th>Partenaire</th><th>Téléphone</th><th>Créé le</th><th>Demandes liées</th></tr></thead><tbody><?php foreach ($clients as $client): ?><tr><td><strong><?= e($client['nom']) ?></strong><br><a href="mailto:<?= e($client['email']) ?>"><?= e($client['email']) ?></a></td><td><?= e((string) ($client['telephone'] ?: '—')) ?></td><td><?= e(date('d/m/Y', strtotime($client['date_creation']))) ?></td><td><?= e((string) $client['demandes']) ?></td></tr><?php endforeach; ?><?php if (!$clients): ?><tr><td colspan="4">Aucun compte partenaire pour le moment.</td></tr><?php endif; ?></tbody></table></section></div></main>
<?php admin_footer(); ?>
