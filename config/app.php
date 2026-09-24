<?php
declare(strict_types=1);

/**
 * Copy .env.example to .env and customise the values before deployment.
 * This lightweight loader avoids committing credentials to the repository.
 */
function env(string $key, ?string $default = null): ?string
{
    static $values = null;

    if ($values === null) {
        $values = [];
        $file = dirname(__DIR__) . '/.env';
        if (is_readable($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$name, $value] = explode('=', $line, 2);
                $values[trim($name)] = trim($value, " \t\n\r\0\x0B\"");
            }
        }
    }

    return $_ENV[$key] ?? getenv($key) ?: $values[$key] ?? $default;
}

function config_external_url(?string $url): ?string
{
    $url = trim((string) $url);
    if ($url === '') { return null; }
    $parts = parse_url($url);
    return is_array($parts) && isset($parts['scheme']) && in_array(strtolower($parts['scheme']), ['http', 'https'], true) ? $url : null;
}

return [
    'environment' => env('APP_ENV', 'local'),
    'url' => rtrim((string) env('APP_URL', ''), '/'),
    'key' => (string) env('APP_KEY', 'replace-this-before-production'),
    'owner' => (string) env('SITE_OWNER', 'Michael AKAKPOSSE'),
    'email' => (string) env('SITE_EMAIL', 'bonjour@votre-domaine.tld'),
    'whatsapp_number' => preg_replace('/\D+/', '', (string) env('WHATSAPP_NUMBER', '')),
    'google_site_verification' => (string) env('GOOGLE_SITE_VERIFICATION', ''),
    'github_url' => config_external_url(env('GITHUB_URL', '')),
    'linkedin_url' => config_external_url(env('LINKEDIN_URL', '')),
    'seo_locality' => (string) env('SEO_LOCALITY', 'Cotonou'),
    'seo_country' => (string) env('SEO_COUNTRY', 'Bénin'),
    'hero_image' => (string) env('SITE_HERO_IMAGE', 'assets/images/profile.jpg'),
    'profile_image' => (string) env('SITE_PROFILE_IMAGE', 'assets/images/profile.jpg'),
    'db' => [
        'host' => (string) env('DB_HOST', '127.0.0.1'),
        'port' => (string) env('DB_PORT', '3306'),
        'name' => (string) env('DB_NAME', 'PORTFOLIO'),
        'user' => (string) env('DB_USER', 'root'),
        'password' => (string) env('DB_PASSWORD', ''),
    ],
    'mail' => [
        'host' => (string) env('SMTP_HOST', ''),
        'port' => (int) env('SMTP_PORT', '587'),
        'username' => (string) env('SMTP_USERNAME', ''),
        'password' => (string) env('SMTP_PASSWORD', ''),
        'encryption' => (string) env('SMTP_ENCRYPTION', 'tls'),
        'from_email' => (string) env('SMTP_FROM_EMAIL', env('SITE_EMAIL', '')),
        'from_name' => (string) env('SMTP_FROM_NAME', env('SITE_OWNER', 'Michael AKAKPOSSE')),
    ],
    'whatsapp_webhook_url' => (string) env('WHATSAPP_WEBHOOK_URL', ''),
];
