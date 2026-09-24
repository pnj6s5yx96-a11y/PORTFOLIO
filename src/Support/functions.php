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


function site_image_url(string $path, string $fallback = 'assets/images/profile-placeholder.svg'): string
{
    $path = trim($path);
    if ($path === '') {
        $path = $fallback;
    }
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }
    $relative = ltrim(str_replace('\\', '/', $path), '/');
    return site_url($relative);
}

function configured_hero_image(): string
{
    $path = (string) config('hero_image');
    if ($path !== '' && (preg_match('~^https?://~i', $path) || is_file(__DIR__ . '/../../' . ltrim($path, '/')))) {
        return $path;
    }
    return 'assets/images/profile-placeholder.svg';
}

function configured_profile_image(): string
{
    $path = (string) config('profile_image');
    if ($path !== '' && (preg_match('~^https?://~i', $path) || is_file(__DIR__ . '/../../' . ltrim($path, '/')))) {
        return $path;
    }
    return 'assets/images/profile-placeholder.svg';
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
    $value = strip_tags($value);
    return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
}

function app_base(): string
{
    $script = str_replace("\\", "/", (string) ($_SERVER["SCRIPT_NAME"] ?? ""));
    if ($script === "" || $script[0] !== "/") {
        return "";
    }

    $base = rtrim(str_replace("\\", "/", dirname($script)), "/");
    foreach (["/admin", "/api", "/espace-client", "/en"] as $suffix) {
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

function project_image_url(?string $image): ?string
{
    $image = trim((string) $image);
    if ($image === "") {
        return null;
    }
    if (external_url($image)) {
        return $image;
    }

    $path = ltrim(str_replace("\\", "/", $image), "/");
    $base = trim(app_base(), "/");
    if ($base !== "" && str_starts_with($path, $base . "/")) {
        $path = substr($path, strlen($base) + 1);
    }

    return str_starts_with($path, "assets/") ? site_url($path) : null;
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

function translated_project(array $project, string $lang = 'fr'): array
{
    if ($lang !== 'en') {
        return $project;
    }
    static $translations = null;
    if ($translations === null) {
        $translations = require __DIR__ . '/../Data/projects_en.php';
    }
    $translation = $translations[$project['slug']] ?? null;
    if (!is_array($translation)) {
        return $project;
    }
    return array_merge($project, $translation);
}


function seo_meta_defaults(string $pageKey, string $lang = 'fr'): array
{
    $owner = (string) config('owner');
    $fr = [
        'home' => ['Développeur web freelance à Cotonou, Bénin | ' . $owner, 'Développeur web freelance à Cotonou, Bénin. Création de sites vitrines, applications web et solutions e-commerce sur mesure, au Bénin et à l’international.'],
        'about' => ['À propos de ' . $owner . ' | Développeur web à Cotonou', 'Découvrez le parcours, l’approche et les compétences de ' . $owner . ', développeur web freelance à Cotonou, spécialisé dans les sites et applications web.'],
        'services' => ['Création de sites web et applications à Cotonou | ' . $owner, 'Création de sites vitrines, applications web et solutions e-commerce sur mesure pour entreprises, indépendants et projets au Bénin et à l’international.'],
        'projects' => ['Portfolio développeur web à Cotonou | Réalisations de ' . $owner, 'Découvrez les projets web réalisés par ' . $owner . ' : sites vitrines, applications métier et expériences e-commerce développés avec PHP, MySQL et JavaScript.'],
        'contact' => ['Contact développeur web freelance à Cotonou | ' . $owner, 'Parlez de votre projet web avec ' . $owner . '. Demandez un devis pour un site vitrine, une application web ou une solution e-commerce.'],
        'blog' => ['Conseils développement web et SEO au Bénin | ' . $owner, 'Guides pratiques sur le développement web, le SEO local et la création de sites internet pour les entreprises au Bénin.'],
    ];
    $en = [
        'home' => ['Freelance Web Developer in Cotonou, Benin | ' . $owner, 'Freelance web developer in Cotonou, Benin. Custom websites, web applications and e-commerce solutions for businesses in Benin and internationally.'],
        'about' => ['About ' . $owner . ' | Freelance Web Developer in Cotonou', 'Discover the background, approach and skills of ' . $owner . ', a freelance web developer in Cotonou building useful, fast and maintainable web experiences.'],
        'services' => ['Website & Web Application Development in Cotonou | ' . $owner, 'Custom websites, web applications and e-commerce solutions for businesses, entrepreneurs and organizations in Benin and internationally.'],
        'projects' => ['Web Developer Portfolio in Cotonou | ' . $owner, 'Explore web projects by ' . $owner . ': business websites, web applications and e-commerce experiences built with PHP, MySQL and JavaScript.'],
        'contact' => ['Contact a Freelance Web Developer in Cotonou | ' . $owner, 'Discuss your web project with ' . $owner . '. Request a quote for a business website, web application or e-commerce solution.'],
        'blog' => ['Web Development & SEO Guides in Benin | ' . $owner, 'Practical guides about web development, local SEO and website creation for businesses in Benin.'],
    ];
    return ($lang === 'en' ? $en : $fr)[$pageKey] ?? ($lang === 'en' ? $en['home'] : $fr['home']);
}

function seo_meta_ensure_table(): bool
{
    static $ready = null;
    if ($ready !== null) return $ready;
    $pdo = db(); if (!$pdo) return $ready = false;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS seo_meta (id_seo INT NOT NULL AUTO_INCREMENT PRIMARY KEY, page_key VARCHAR(100) NOT NULL, language_code CHAR(2) NOT NULL, seo_title VARCHAR(180) NOT NULL DEFAULT '', meta_description VARCHAR(320) NOT NULL DEFAULT '', focus_keyword VARCHAR(150) NOT NULL DEFAULT '', canonical_url VARCHAR(500) NOT NULL DEFAULT '', robots VARCHAR(80) NOT NULL DEFAULT 'index,follow', date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uq_seo_page_lang (page_key, language_code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        return $ready = true;
    } catch (Throwable $e) { error_log('[portfolio] SEO table init failed: '.$e->getMessage()); return $ready = false; }
}

function seo_meta_get(string $key, string $lang = 'fr'): array
{
    static $cache = [];
    $cacheKey = $key . '|' . $lang;
    if (isset($cache[$cacheKey])) return $cache[$cacheKey];
    $defaults = seo_meta_defaults($key, $lang);
    $result = ['title'=>$defaults[0], 'description'=>$defaults[1], 'focus_keyword'=>'', 'canonical'=>'', 'robots'=>'index,follow'];
    $pdo=db();
    if($pdo && seo_meta_ensure_table()) {
        try { $st=$pdo->prepare('SELECT seo_title,meta_description,focus_keyword,canonical_url,robots FROM seo_meta WHERE page_key=:k AND language_code=:l LIMIT 1'); $st->execute(['k'=>$key,'l'=>$lang]); if($r=$st->fetch()){ $result['title']=trim((string)$r['seo_title'])?:$result['title']; $result['description']=trim((string)$r['meta_description'])?:$result['description']; $result['focus_keyword']=trim((string)$r['focus_keyword']); $result['canonical']=trim((string)$r['canonical_url']); $result['robots']=trim((string)$r['robots'])?:$result['robots']; } } catch(Throwable $e){error_log('[portfolio] SEO metadata read failed: '.$e->getMessage());}
    }
    return $cache[$cacheKey]=$result;
}

function seo_meta_save(string $key, string $lang, array $data): void
{
    $pdo=db(); if(!$pdo || !seo_meta_ensure_table()) throw new RuntimeException('Base de données ou table SEO indisponible.');
    $robots=in_array(($data['robots']??''),['index,follow','noindex,follow','noindex,nofollow'],true)?$data['robots']:'index,follow';
    $st=$pdo->prepare('INSERT INTO seo_meta(page_key,language_code,seo_title,meta_description,focus_keyword,canonical_url,robots) VALUES(:k,:l,:t,:d,:f,:c,:r) ON DUPLICATE KEY UPDATE seo_title=VALUES(seo_title),meta_description=VALUES(meta_description),focus_keyword=VALUES(focus_keyword),canonical_url=VALUES(canonical_url),robots=VALUES(robots)');
    $st->execute(['k'=>$key,'l'=>$lang,'t'=>clean_text($data['title']??'',180),'d'=>clean_text($data['description']??'',320),'f'=>clean_text($data['focus_keyword']??'',150),'c'=>trim((string)($data['canonical']??'')),'r'=>$robots]);
}

function seo_project_get(int $projectId, string $lang = 'fr'): array
{
    $result=['title'=>'','description'=>'','focus_keyword'=>'','canonical'=>'','robots'=>'index,follow'];
    if($projectId<=0) return $result; $pdo=db(); if(!$pdo || !seo_meta_ensure_table()) return $result;
    try { $st=$pdo->prepare('SELECT seo_title,meta_description,focus_keyword,canonical_url,robots FROM seo_meta WHERE page_key=:k AND language_code=:l LIMIT 1'); $st->execute(['k'=>'project:'.$projectId,'l'=>$lang]); if($r=$st->fetch()){ $result=['title'=>trim((string)$r['seo_title']),'description'=>trim((string)$r['meta_description']),'focus_keyword'=>trim((string)$r['focus_keyword']),'canonical'=>trim((string)$r['canonical_url']),'robots'=>trim((string)$r['robots'])?:'index,follow']; } } catch(Throwable $e){error_log('[portfolio] Project SEO read failed: '.$e->getMessage());}
    return $result;
}

function page_url(string $route, string $lang = 'fr'): string
{
    $lang = $lang === 'en' ? 'en' : 'fr';
    $map = [
        'home' => ['fr' => '', 'en' => 'en/'],
        'about' => ['fr' => 'a-propos.php', 'en' => 'en/about.php'],
        'projects' => ['fr' => 'projets.php', 'en' => 'en/projects.php'],
        'services' => ['fr' => 'services.php', 'en' => 'en/services.php'],
        'contact' => ['fr' => 'contact.php', 'en' => 'en/contact.php'],
        'legal' => ['fr' => 'mentions-legales.php', 'en' => 'en/legal.php'],
        'blog' => ['fr' => 'blog.php', 'en' => 'en/blog.php'],
    ];
    return site_url($map[$route][$lang] ?? $map['home'][$lang]);
}

function project_page_url(string $slug, string $lang = 'fr'): string
{
    $route = $lang === 'en' ? 'en/project.php?slug=' : 'projet.php?slug=';
    return site_url($route . rawurlencode($slug));
}

function seo_keywords(string $lang = 'fr'): array
{
    return $lang === 'en'
        ? ['web developer Benin', 'freelance web developer Cotonou', 'website development Benin', 'web application development', 'e-commerce development']
        : ['développeur web Bénin', 'développeur web freelance Cotonou', 'création site web Cotonou', 'application web Bénin', 'e-commerce Bénin'];
}
