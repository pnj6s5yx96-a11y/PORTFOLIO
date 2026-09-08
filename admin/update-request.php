<?php
declare(strict_types=1);
require __DIR__ . '/../bootstrap.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)) { http_response_code(403); exit('Requête invalide.'); }
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT); $status = clean_text($_POST['status'] ?? '', 30);
if (!$id || !in_array($status, ['Non traité', 'En cours', 'Converti', 'Sans suite'], true)) { http_response_code(422); exit('Données invalides.'); }
try { db()?->prepare('UPDATE demande_contact SET statut = :status WHERE id_demande = :id')->execute(['status' => $status, 'id' => $id]); security_log('Statut de demande modifié', (int) $_SESSION['admin_id']); } catch (Throwable $exception) { error_log('[portfolio] Status update: ' . $exception->getMessage()); }
header("Location: " . site_url("admin/"));
