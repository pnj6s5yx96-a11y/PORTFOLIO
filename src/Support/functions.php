<?php
declare(strict_types=1);

function config(?string $key = null): mixed
{
    global $config;
    if ($key === null) {
        return $config;
    }
    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return null;
        }
        $value = $value[$segment];
    }
    return $value;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function db(): ?PDO
{
    static $pdo = false;
    if ($pdo !== false) {
        return $pdo;
    }
    try {
        $db = config('db');
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['name']),
            $db['user'],
            $db['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (Throwable $exception) {
        error_log('[portfolio] Database unavailable: ' . $exception->getMessage());
        $pdo = null;
    }
    return $pdo;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csp_nonce(): string
{
    return (string) ($GLOBALS['csp_nonce'] ?? '');
}

function csrf_is_valid(?string $token): bool
{
    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function abort_json(int $status, string $message, array $errors = []): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['ok' => false, 'message' => $message, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
    exit;
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function client_ip(): string
{
    // Trust the direct connection only. Configure a trusted proxy at server level if needed.
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function anonymised_ip(): string
{
    return substr(hash_hmac('sha256', client_ip(), (string) config('key')), 0, 32);
}

function clean_text(mixed $value, int $maxLength = 255): string
{
    $value = trim((string) $value);
    $value = preg_replace('/\s+/u', ' ', $value) ?? '';
    return mb_substr(strip_tags($value), 0, $maxLength);
}

function app_base(): string
{
    $script = str_replace("\\", "/", (string) ($_SERVER["SCRIPT_NAME"] ?? ""));
    if ($script === "" || $script[0] !== "/") {
        return "";
    }

    $base = rtrim(str_replace("\\", "/", dirname($script)), "/");
    foreach (["/admin", "/api", "/espace-client"] as $suffix) {
        if (str_ends_with($base, $suffix)) {
            $base = substr($base, 0, -strlen($suffix));
            break;
        }
    }

    return $base === "/" ? "" : $base;
}

function current_path(): string
{
    $path = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH) ?: "/";
    $base = app_base();
    if ($base !== "" && ($path === $base || str_starts_with($path, $base . "/"))) {
        $path = substr($path, strlen($base)) ?: "/";
    }
    return $path;
}

function is_admin(): bool
{
    return isset($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!is_admin()) {
        header("Location: " . site_url("admin/login.php"));
        exit;
    }
}

function is_client(): bool
{
    return isset($_SESSION['client_id']);
}

function require_client(): void
{
    if (!is_client()) {
        header("Location: " . site_url("espace-client/login.php"));
        exit;
    }
}

function security_log(string $action, ?int $adminId = null): void
{
    $pdo = db();
    if (!$pdo) {
        return;
    }
    try {
        $stmt = $pdo->prepare('INSERT INTO journal_securite (action, ip, id_admin) VALUES (:action, :ip, :admin)');
        $stmt->execute(['action' => mb_substr($action, 0, 150), 'ip' => anonymised_ip(), 'admin' => $adminId]);
    } catch (Throwable $exception) {
        error_log('[portfolio] Security log failed: ' . $exception->getMessage());
    }
}

function whatsapp_url(string $message = 'Bonjour Michael, je souhaite discuter d’un projet web.'): string
{
    $number = (string) config('whatsapp_number');
    return $number !== '' ? 'https://wa.me/' . rawurlencode($number) . '?text=' . rawurlencode($message) : site_url("contact.php");
}

function site_url(string $path = ''): string
{
    $base = rtrim((string) config('url'), '/');
    return $base !== "" ? $base . "/" . ltrim($path, "/") : app_base() . "/" . ltrim($path, "/");
}

function external_url(?string $url): ?string
{
    $url = trim((string) $url);
    if ($url === '') {
        return null;
    }
    $parts = parse_url($url);
    return is_array($parts) && isset($parts['scheme']) && in_array(strtolower($parts['scheme']), ['http', 'https'], true) ? $url : null;
}
