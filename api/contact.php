<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../src/Services/Mailer.php';

use Portfolio\Services\Mailer;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    abort_json(405, 'Méthode non autorisée.');
}
if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
    security_log('Échec CSRF sur le formulaire de contact');
    abort_json(403, 'Votre session a expiré. Actualisez la page avant de réessayer.');
}
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    // A bot must receive no confirmation that the honeypot exists.
    security_log('Soumission anti-spam bloquée');
    json_response(['ok' => true, 'message' => 'Merci, votre demande a bien été envoyée.']);
}

$allowedProjects = ['Site vitrine', 'Application web', 'E-commerce', 'Autre'];
$allowedBudgets = ['Moins de 500€', '500€ - 1000€', '1000€ - 3000€', '3000€ - 5000€', 'Plus de 5000€', 'Non précisé'];
$allowedDeadlines = ['Dès que possible', 'Dans le mois', 'Dans 1 à 3 mois', 'Plus de 3 mois', 'À définir ensemble'];
$name = clean_text($_POST['name'] ?? '', 100);
$email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL) ?: '';
$phone = clean_text($_POST['phone'] ?? '', 20);
$projectType = clean_text($_POST['project_type'] ?? '', 100);
$budget = clean_text($_POST['budget'] ?? '', 50);
$deadline = clean_text($_POST['deadline'] ?? '', 50);
$message = trim(strip_tags((string) ($_POST['message'] ?? '')));
$message = mb_substr(preg_replace('/\r\n?/', "\n", $message) ?? '', 0, 2000);
$errors = [];

if (mb_strlen($name) < 2) $errors['name'] = 'Indiquez votre nom (au moins 2 caractères).';
if ($email === '') $errors['email'] = 'Saisissez une adresse e-mail valide.';
if (!in_array($projectType, $allowedProjects, true)) $errors['project_type'] = 'Choisissez le type de projet.';
if (!in_array($budget, $allowedBudgets, true)) $errors['budget'] = 'Choisissez une tranche de budget.';
if (!in_array($deadline, $allowedDeadlines, true)) $errors['deadline'] = 'Indiquez le délai souhaité.';
if (mb_strlen($message) < 30) $errors['message'] = 'Décrivez votre besoin en au moins 30 caractères.';
if (($_POST['consent'] ?? '') !== '1') $errors['consent'] = 'Votre accord est nécessaire pour envoyer la demande.';
if ($phone !== '' && !preg_match('/^[0-9+().\s-]{6,20}$/', $phone)) $errors['phone'] = 'Vérifiez le format du numéro de téléphone.';
if ($errors) abort_json(422, 'Certains champs demandent votre attention.', $errors);

$pdo = db();
if (!$pdo) abort_json(503, 'Le formulaire est momentanément indisponible. Vous pouvez me contacter par e-mail ou WhatsApp.');

$ipHash = anonymised_ip();
try {
    $pdo->beginTransaction();
    $rate = $pdo->prepare('SELECT last_submission FROM limite_formulaire WHERE ip_anonymisee = :ip FOR UPDATE');
    $rate->execute(['ip' => $ipHash]);
    $lastSubmission = $rate->fetchColumn();
    if ($lastSubmission && (time() - strtotime((string) $lastSubmission)) < 60) {
        $pdo->rollBack();
        security_log('Limite de fréquence du formulaire atteinte');
        abort_json(429, 'Merci de patienter une minute avant une nouvelle demande.');
    }
    $pdo->prepare('INSERT INTO limite_formulaire (ip_anonymisee, last_submission) VALUES (:ip, NOW()) ON DUPLICATE KEY UPDATE last_submission = NOW()')->execute(['ip' => $ipHash]);
    $insert = $pdo->prepare('INSERT INTO demande_contact (nom, email, telephone, type_projet, budget, message, canal_origine) VALUES (:nom, :email, :telephone, :type, :budget, :message, "formulaire")');
    $insert->execute(['nom' => $name, 'email' => $email, 'telephone' => $phone ?: null, 'type' => $projectType, 'budget' => $budget, 'message' => $message . "\n\nDélai souhaité : " . $deadline]);
    $requestId = (int) $pdo->lastInsertId();
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[portfolio] Contact persistence failed: ' . $exception->getMessage());
    abort_json(503, 'Le formulaire est momentanément indisponible. Vous pouvez me contacter par e-mail ou WhatsApp.');
}

$owner = (string) config('owner');
$subject = 'Nouvelle demande — ' . $projectType;
$adminHtml = '<h2>Nouvelle demande de projet</h2><p><strong>Nom :</strong> ' . e($name) . '<br><strong>E-mail :</strong> ' . e($email) . '<br><strong>Téléphone :</strong> ' . e($phone ?: 'Non renseigné') . '<br><strong>Projet :</strong> ' . e($projectType) . '<br><strong>Budget :</strong> ' . e($budget) . '<br><strong>Délai :</strong> ' . e($deadline) . '</p><p><strong>Besoin :</strong><br>' . nl2br(e($message)) . '</p>';
$adminText = "Nouvelle demande de {$name}\nEmail : {$email}\nTéléphone : " . ($phone ?: 'Non renseigné') . "\nProjet : {$projectType}\nBudget : {$budget}\nDélai : {$deadline}\n\n{$message}";
$adminEmailSent = Mailer::send((string) config('email'), $subject, $adminHtml, $adminText);
$ackHtml = '<h2>Merci pour votre message, ' . e($name) . '.</h2><p>Votre demande a bien été reçue. ' . e($owner) . ' revient vers vous sous 24 à 48 heures ouvrées.</p><p>À bientôt.</p>';
$ackEmailSent = Mailer::send($email, 'Votre demande a bien été reçue', $ackHtml, "Merci pour votre message, {$name}. Votre demande a bien été reçue. {$owner} revient vers vous sous 24 à 48 heures ouvrées.");

$whatsappStatus = 'Échec';
$webhook = (string) config('whatsapp_webhook_url');
if ($webhook !== '') {
    $payload = json_encode(['event' => 'new_contact_request', 'request_id' => $requestId, 'name' => $name, 'email' => $email, 'phone' => $phone, 'project_type' => $projectType, 'budget' => $budget, 'deadline' => $deadline, 'message' => $message], JSON_UNESCAPED_UNICODE);
    $context = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\nAccept: application/json\r\n", 'content' => $payload, 'timeout' => 5, 'ignore_errors' => true]]);
    $result = @file_get_contents($webhook, false, $context);
    $httpCode = isset($http_response_header[0]) ? (int) filter_var($http_response_header[0], FILTER_SANITIZE_NUMBER_INT) : 0;
    $whatsappStatus = ($result !== false && $httpCode >= 200 && $httpCode < 300) ? 'Envoyé' : 'Échec';
}

try {
    $notification = $pdo->prepare('INSERT INTO notification (canal, statut_envoi, id_demande) VALUES (:canal, :status, :request)');
    $notification->execute(['canal' => 'email', 'status' => $adminEmailSent && $ackEmailSent ? 'Envoyé' : 'Échec', 'request' => $requestId]);
    $notification->execute(['canal' => 'whatsapp', 'status' => $whatsappStatus, 'request' => $requestId]);
} catch (Throwable $exception) { error_log('[portfolio] Notification log failed: ' . $exception->getMessage()); }

json_response(['ok' => true, 'message' => 'Merci, votre demande est bien partie. Je reviens vers vous sous 24 à 48 heures ouvrées.']);
