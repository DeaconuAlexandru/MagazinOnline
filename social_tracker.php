<?php
declare(strict_types=1);

if (defined('SOCIAL_TRACKER_LOADED')) {
    return;
}
define('SOCIAL_TRACKER_LOADED', true);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!function_exists('getClientIp')) {
    function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return trim((string)$_SERVER['HTTP_CF_CONNECTING_IP']);
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim((string)($parts[0] ?? '0.0.0.0'));
        }

        return trim((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
    }
}

if (!function_exists('hashIp')) {
    function hashIp(string $ip): string
    {
        return hash('sha256', $ip . '|salttokenmonitor');
    }
}

if (!function_exists('detectSource')) {
    function detectSource(): string
    {
        $utm = strtolower(trim((string)($_GET['utm_source'] ?? '')));
        if (in_array($utm, ['youtube', 'instagram', 'facebook', 'tiktok'], true)) {
            return $utm;
        }

        $ref = strtolower((string)($_SERVER['HTTP_REFERER'] ?? ''));

        if (strpos($ref, 'youtube.com') !== false || strpos($ref, 'youtu.be') !== false) {
            return 'youtube';
        }
        if (strpos($ref, 'instagram.com') !== false) {
            return 'instagram';
        }
        if (strpos($ref, 'facebook.com') !== false || strpos($ref, 'fb.com') !== false) {
            return 'facebook';
        }
        if (strpos($ref, 'tiktok.com') !== false) {
            return 'tiktok';
        }

        return 'direct';
    }
}

if (!function_exists('getCountryCode')) {
    function getCountryCode(): string
    {
        $cfCountry = strtoupper(trim((string)($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '')));
        if ($cfCountry !== '' && $cfCountry !== 'XX') {
            return $cfCountry;
        }

        return 'ZZ';
    }
}

if (!function_exists('getLandingPage')) {
    function getLandingPage(): string
    {
        $script = basename((string)($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
        $script = trim($script);

        return $script !== '' ? mb_substr($script, 0, 255) : 'index.php';
    }
}

if (!function_exists('getLandingUrl')) {
    function getLandingUrl(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int)($_SERVER['SERVER_PORT'] ?? 80) === 443;
        $scheme = $https ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? '');
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');

        return mb_substr($scheme . '://' . $host . $uri, 0, 255);
    }
}

if (!function_exists('getSessionKey')) {
    function getSessionKey(): string
    {
        $sid = session_id();
        if ($sid !== '') {
            return $sid;
        }

        return hash('sha256', uniqid('session_', true));
    }
}

if (!function_exists('nullIfEmpty')) {
    function nullIfEmpty($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }
}

if (!function_exists('resolveVisitorIdentity')) {
    function resolveVisitorIdentity(PDO $pdo): array
    {
        $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $username = nullIfEmpty($_SESSION['username'] ?? null);
        $email = nullIfEmpty($_SESSION['email'] ?? null);

        if ($userId) {
            try {
                $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ? LIMIT 1");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();

                if ($user) {
                    if ($username === null) {
                        $username = nullIfEmpty($user['username'] ?? null);
                    }
                    if ($email === null) {
                        $email = nullIfEmpty($user['email'] ?? null);
                    }
                }
            } catch (Throwable $e) {
                // silent fail
            }

            if ($username === null || $username === '') {
                if ($email !== null && strpos($email, '@') !== false) {
                    $username = strstr($email, '@', true) ?: null;
                }
            }

            if ($username === null || $username === '') {
                $username = 'user#' . $userId;
            }

            return [
                'user_id' => $userId,
                'username' => $username,
                'email' => $email,
                'visitor_type' => 'logged_in',
            ];
        }

        return [
            'user_id' => null,
            'username' => 'guest',
            'email' => null,
            'visitor_type' => 'guest',
        ];
    }
}

if (!function_exists('recordSocialVisit')) {
    function recordSocialVisit(PDO $pdo, ?string $landingPage = null): void
    {
        try {
            if (defined('SOCIAL_VISIT_DONE')) {
                return;
            }
            define('SOCIAL_VISIT_DONE', true);

            $page = $landingPage ?: getLandingPage();
            $url = getLandingUrl();
            $source = detectSource();
            $ipHash = hashIp(getClientIp());
            $userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
            $referrer = substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 2000);
            $sessionKey = getSessionKey();
            $countryCode = getCountryCode();

            $identity = resolveVisitorIdentity($pdo);

            $stmt = $pdo->prepare("
                SELECT id
                FROM social_visits
                WHERE session_key = :session_key
                  AND visit_date = CURDATE()
                  AND source = :source
                  AND landing_page = :landing_page
                LIMIT 1
            ");
            $stmt->execute([
                ':session_key' => $sessionKey,
                ':source' => $source,
                ':landing_page' => $page,
            ]);

            $existingId = (int)($stmt->fetchColumn() ?: 0);

            if ($existingId > 0) {
                $stmt = $pdo->prepare("
                    UPDATE social_visits
                    SET visited_at = NOW(),
                        updated_at = NOW(),
                        ip_hash = :ip_hash,
                        user_agent = :user_agent,
                        referrer = :referrer,
                        visitor_type = :visitor_type,
                        user_id = :user_id,
                        username = :username,
                        email = :email,
                        landing_url = :landing_url,
                        country_code = :country_code
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':ip_hash' => $ipHash,
                    ':user_agent' => $userAgent,
                    ':referrer' => $referrer,
                    ':visitor_type' => $identity['visitor_type'],
                    ':user_id' => $identity['user_id'],
                    ':username' => $identity['username'],
                    ':email' => $identity['email'],
                    ':landing_url' => $url,
                    ':country_code' => $countryCode,
                    ':id' => $existingId,
                ]);

                return;
            }

            $stmt = $pdo->prepare("
                INSERT INTO social_visits
                    (source, landing_page, visited_at, visit_date, ip_hash, user_agent, referrer, session_key, visitor_type, user_id, username, email, landing_url, country_code, updated_at)
                VALUES
                    (:source, :landing_page, NOW(), CURDATE(), :ip_hash, :user_agent, :referrer, :session_key, :visitor_type, :user_id, :username, :email, :landing_url, :country_code, NOW())
            ");

            $stmt->execute([
                ':source' => $source,
                ':landing_page' => $page,
                ':ip_hash' => $ipHash,
                ':user_agent' => $userAgent,
                ':referrer' => $referrer,
                ':session_key' => $sessionKey,
                ':visitor_type' => $identity['visitor_type'],
                ':user_id' => $identity['user_id'],
                ':username' => $identity['username'],
                ':email' => $identity['email'],
                ':landing_url' => $url,
                ':country_code' => $countryCode,
            ]);
        } catch (Throwable $e) {
            error_log('social_tracker error: ' . $e->getMessage());
        }
    }
}