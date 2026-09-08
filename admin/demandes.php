<?php
declare(strict_types=1);

require __DIR__ . '/../includes/admin.php';
require_once __DIR__ . '/../src/Services/Mailer.php';

use Portfolio\Services\Mailer;

require_admin();
$pdo = db();
$success = '';
$error = '';
$selectedId = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;

if (!$pdo) {
    $error = 'La base de données est indisponible.';
} elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Votre session a expiré. Actualisez la page puis réessayez.';
    } else {
        $action = clean_text($_POST['action'] ?? '', 20);
        $requestId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
        try {
            $requestStatement = $pdo->prepare('SELECT * FROM demande_contact WHERE id_demande = :id LIMIT 1');
            $requestStatement->execute(['id' => $requestId]);
            $target = $requestStatement->fetch();
            if (!$target) {
                throw new RuntimeException('Demande introuvable.');
            }
            $selectedId = $requestId;

            if ($action === 'status') {
                $status = clean_text($_POST['status'] ?? '', 30);
                if (!in_array($status, request_statuses(), true)) {
                    throw new RuntimeException('Statut invalide.');
                }
                $pdo->prepare('UPDATE demande_contact SET statut = :status WHERE id_demande = :id')->execute(['status' => $status, 'id' => $requestId]);
                security_log('Statut de demande modifié', (int) $_SESSION['admin_id']);
                $success = 'Statut mis à jour.';
            }

            if ($action === 'reply') {
                $subject = clean_text($_POST['subject'] ?? '', 150);
                $message = trim(strip_tags((string) ($_POST['message'] ?? '')));
                $message = mb_substr($message, 0, 3000);
                if (mb_strlen($subject) < 3 || mb_strlen($message) < 5) {
                    throw new RuntimeException('Renseignez un objet et un message de quelques mots au minimum.');
                }

                $html = '<p>' . nl2br(e($message)) . '</p><hr><p style="color:#718086;font-size:12px">Réponse de ' . e((string) config('owner')) . ' concernant votre demande « ' . e((string) $target['type_projet']) . ' ».</p>';
                if (!Mailer::send((string) $target['email'], $subject, $html, $message)) {
                    throw new RuntimeException('L’e-mail n’a pas pu être envoyé. Vérifiez le mot de passe d’application SMTP iCloud dans le fichier .env.');
                }
                $pdo->prepare('UPDATE demande_contact SET statut = "En cours" WHERE id_demande = :id AND statut = "Non traité"')->execute(['id' => $requestId]);
                security_log('Réponse e-mail envoyée à un prospect', (int) $_SESSION['admin_id']);
                $success = 'Réponse envoyée à ' . $target['email'] . '.';
            }
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            error_log('[portfolio] Request inbox: ' . $exception->getMessage());
            $error = 'Action impossible. Réessayez dans un instant.';
        }
    }
}

$requests = [];
$selected = null;
try {
    if ($pdo) {
        $requests = $pdo->query('SELECT * FROM demande_contact ORDER BY FIELD(statut, "Non traité", "En cours", "Converti", "Sans suite"), date_soumission DESC')->fetchAll();
        if (!$selectedId && $requests) {
            $selectedId = (int) $requests[0]['id_demande'];
        }
        foreach ($requests as $item) {
            if ((int) $item['id_demande'] === $selectedId) {
                $selected = $item;
                break;
            }
        }
    }
} catch (Throwable $exception) {
    error_log('[portfolio] Request reading: ' . $exception->getMessage());
    $error = 'Impossible de charger les demandes.';
}

admin_header('Boîte de réception', 'requests');
?>
<main class="admin-main"><div class="admin-shell">
  <div class="admin-title-row"><div><p class="eyebrow"><span></span> Relations clients</p><h1>Boîte de réception</h1><p>Traitez les demandes, ajustez leur statut et répondez à vos prospects depuis le même espace.</p></div><a class="admin-button admin-button--light" href="<?= e(site_url('admin/export.php?type=contacts')) ?>">Exporter les contacts</a></div>
  <?php if ($error): ?><p class="admin-alert" role="alert"><?= e($error) ?></p><?php endif; ?>
  <?php if ($success): ?><p class="admin-success" role="status"><?= e($success) ?></p><?php endif; ?>
  <div class="inbox-layout">
    <section class="admin-panel"><h2>Demandes <small style="color:#71838a;font:12px var(--sans)"><?= count($requests) ?></small></h2><div class="inbox-list">
      <?php foreach ($requests as $request): ?><a class="message-item <?= (int) $request['id_demande'] === $selectedId ? 'is-active' : '' ?>" href="<?= e(site_url('admin/demandes.php?id=' . (int) $request['id_demande'])) ?>"><div class="message-item-head"><b><?= e($request['nom']) ?></b><small><?= e(date('d/m', strtotime($request['date_soumission']))) ?></small></div><p><?= e((string) $request['type_projet']) ?> · <span class="status status--<?= e(str_replace(' ', '-', $request['statut'])) ?>"><?= e($request['statut']) ?></span></p><p><?= e(mb_strimwidth($request['message'], 0, 82, '…')) ?></p></a><?php endforeach; ?>
      <?php if (!$requests): ?><p>Aucune demande pour le moment.</p><?php endif; ?>
    </div></section>
    <section class="admin-panel">
      <?php if ($selected): ?>
        <div class="request-detail-head"><div><p class="eyebrow"><span></span> Demande #<?= e((string) $selected['id_demande']) ?></p><h2><?= e($selected['nom']) ?></h2><div class="request-contact"><a href="mailto:<?= e($selected['email']) ?>"><?= e($selected['email']) ?></a><?php if (!empty($selected['telephone'])): ?><a href="<?= e('https://wa.me/' . preg_replace('/\D+/', '', $selected['telephone'])) ?>" target="_blank" rel="noopener">WhatsApp ↗</a><?php endif; ?><span><?= e((string) $selected['type_projet']) ?> · <?= e((string) $selected['budget']) ?></span></div></div>
          <form method="post" class="inline-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= e((string) $selected['id_demande']) ?>"><select name="status" aria-label="Modifier le statut"><?php foreach (request_statuses() as $status): ?><option value="<?= e($status) ?>" <?= $status === $selected['statut'] ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select><button class="admin-button admin-button--light" type="submit">Enregistrer</button></form>
        </div>
        <p class="request-message"><?= e($selected['message']) ?></p>
        <form method="post" class="reply-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="reply"><input type="hidden" name="id" value="<?= e((string) $selected['id_demande']) ?>"><p class="eyebrow"><span></span> Répondre par e-mail</p><div class="field"><label for="subject">Objet</label><input id="subject" name="subject" required maxlength="150" value="Re: votre demande de <?= e((string) $selected['type_projet']) ?>"></div><div class="field"><label for="message">Votre message</label><textarea id="message" name="message" required minlength="5" maxlength="3000">Bonjour <?= e($selected['nom']) ?>,

Merci pour votre message. </textarea></div><button class="admin-button admin-button--coral" type="submit">Envoyer la réponse ↗</button><p class="form-note">L’e-mail est envoyé avec ton adresse configurée : <?= e((string) config('email')) ?>.</p></form>
      <?php else: ?><p>Sélectionnez une demande pour voir ses détails.</p><?php endif; ?>
    </section>
  </div>
</div></main>
<?php admin_footer(); ?>
