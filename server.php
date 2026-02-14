<?php

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

$projectRoot = __DIR__;
$storageRoot = $projectRoot . '/storage/app';
$usersFile = $storageRoot . '/users.json';
$tokensFile = $storageRoot . '/tokens.json';
$reviewsFile = $storageRoot . '/reviews.json';
$settingsFile = $storageRoot . '/settings.json';
$importMetaFile = $storageRoot . '/import_meta.json';

if (!is_dir($storageRoot)) {
    @mkdir($storageRoot, 0777, true);
}

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

function readJson(string $path, array $fallback = []): array
{
    if (!file_exists($path)) {
        return $fallback;
    }

    $raw = file_get_contents($path);
    $decoded = json_decode((string) $raw, true);

    return is_array($decoded) ? $decoded : $fallback;
}

function writeJson(string $path, array $value): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    file_put_contents($path, json_encode($value, JSON_UNESCAPED_UNICODE));
}

function requestData(): array
{
    $raw = file_get_contents('php://input');
    $decoded = json_decode((string) $raw, true);

    return is_array($decoded) ? $decoded : [];
}

function renderDemoHtml(string $projectRoot): string
{
    $manifestPath = $projectRoot . '/public/build/manifest.json';
    $entryJs = '/resources/js/demo.js';
    $entryCss = '';

    if (file_exists($manifestPath)) {
        $manifestRaw = file_get_contents($manifestPath);
        $manifest = json_decode((string) $manifestRaw, true);

        if (is_array($manifest) && isset($manifest['resources/js/demo.js']['file'])) {
            $entryJs = '/build/' . ltrim((string) $manifest['resources/js/demo.js']['file'], '/');
        }

        if (is_array($manifest) && isset($manifest['resources/js/demo.js']['css'][0])) {
            $entryCss = '/build/' . ltrim((string) $manifest['resources/js/demo.js']['css'][0], '/');
        }
    }

    $cssTag = $entryCss !== ''
        ? '<link rel="stylesheet" href="' . htmlspecialchars($entryCss, ENT_QUOTES) . '">'
        : '';

    return '<!doctype html>'
        . '<html><head><meta charset="utf-8" />'
        . '<meta name="viewport" content="width=device-width, initial-scale=1.0" />'
        . '<title>Imtera — UI Demo</title>'
        . $cssTag
        . '<style>body{font-family:Inter,ui-sans-serif,system-ui;margin:0;padding:0;}</style>'
        . '</head><body><div id="demo-app"></div>'
        . '<script type="module" src="' . htmlspecialchars($entryJs, ENT_QUOTES) . '"></script>'
        . '</body></html>';
}

function readUsers(string $usersFile): array
{
    $users = readJson($usersFile, []);

    return array_values(array_filter($users, static fn($item) => is_array($item) && isset($item['email'])));
}

function saveUsers(string $usersFile, array $users): void
{
    writeJson($usersFile, array_values($users));
}

function readTokens(string $tokensFile): array
{
    return readJson($tokensFile, []);
}

function saveTokens(string $tokensFile, array $tokens): void
{
    writeJson($tokensFile, $tokens);
}

function ensureDemoUser(string $usersFile): void
{
    $users = readUsers($usersFile);
    foreach ($users as $user) {
        if (mb_strtolower((string)($user['email'] ?? '')) === 'demo@imtera.ru') {
            return;
        }
    }

    $nextId = 1;
    foreach ($users as $user) {
        $nextId = max($nextId, ((int)($user['id'] ?? 0)) + 1);
    }

    $users[] = [
        'id' => $nextId,
        'name' => 'Imtera Demo',
        'email' => 'demo@imtera.ru',
        'password_hash' => password_hash('demo12345', PASSWORD_DEFAULT),
        'created_at' => gmdate('c'),
    ];

    saveUsers($usersFile, $users);
}

function issueToken(string $tokensFile, int $userId): string
{
    $tokens = readTokens($tokensFile);
    $token = bin2hex(random_bytes(24));

    $tokens[$token] = [
        'user_id' => $userId,
        'created_at' => gmdate('c'),
    ];

    saveTokens($tokensFile, $tokens);

    return $token;
}

function readAuthToken(): string
{
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!str_starts_with($auth, 'Bearer ')) {
        return '';
    }

    return trim(substr($auth, 7));
}

function getAuthenticatedUser(string $usersFile, string $tokensFile): ?array
{
    $token = readAuthToken();
    if ($token === '') {
        return null;
    }

    $tokens = readTokens($tokensFile);
    $tokenData = $tokens[$token] ?? null;
    if (!is_array($tokenData)) {
        return null;
    }

    $userId = (int)($tokenData['user_id'] ?? 0);
    $users = readUsers($usersFile);
    foreach ($users as $user) {
        if ((int)($user['id'] ?? 0) === $userId) {
            return $user;
        }
    }

    return null;
}

function parseYandexInput(?string $inputUrl): ?array
{
    $inputUrl = trim((string)$inputUrl);
    if ($inputUrl === '') {
        return null;
    }

    $parts = parse_url($inputUrl);
    if (!$parts || empty($parts['host'])) {
        return null;
    }

    $host = mb_strtolower((string)$parts['host']);
    if (!str_contains($host, 'yandex.')) {
        return null;
    }

    $path = (string)($parts['path'] ?? '');
    $query = (string)($parts['query'] ?? '');

    $orgId = '';
    if (preg_match('#/org/[^/]+/(\d+)(?:/reviews/?)?#u', $path, $matches)) {
        $orgId = (string)$matches[1];
    }

    if ($orgId === '' && preg_match('/(?:^|[?&])oid=(\d{6,})/u', $query, $oidQuery)) {
        $orgId = (string)$oidQuery[1];
    }

    if ($orgId === '' && preg_match('/oid%3D(\d{6,})/u', $inputUrl, $oidEncoded)) {
        $orgId = (string)$oidEncoded[1];
    }

    if ($orgId === '') {
        return null;
    }

    return [
        'org_id' => $orgId,
        'reviews_url' => 'https://yandex.ru/maps/org/' . $orgId . '/reviews/',
        'input_url' => $inputUrl,
    ];
}

function fetchHtml(string $url): ?string
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 20,
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
                'Accept-Language: ru-RU,ru;q=0.9,en;q=0.8',
            ],
        ],
    ]);

    $html = @file_get_contents($url, false, $context);
    return is_string($html) && $html !== '' ? $html : null;
}

function collectReviewNodes($node, array &$bucket): void
{
    if (!is_array($node)) {
        return;
    }

    $type = $node['@type'] ?? null;
    if (is_string($type) && mb_strtolower($type) === 'review') {
        $bucket[] = $node;
    }

    if (is_array($type)) {
        foreach ($type as $typeItem) {
            if (is_string($typeItem) && mb_strtolower($typeItem) === 'review') {
                $bucket[] = $node;
                break;
            }
        }
    }

    if (!empty($node['reviewBody']) || !empty($node['description'])) {
        $bucket[] = $node;
    }

    foreach ($node as $value) {
        if (is_array($value)) {
            collectReviewNodes($value, $bucket);
        }
    }
}

function mapReviewNode(array $node, string $orgId): ?array
{
    $authorNode = $node['author'] ?? null;
    $author = 'Пользователь';

    if (is_array($authorNode) && !empty($authorNode['name'])) {
        $author = trim((string)$authorNode['name']);
    } elseif (is_string($authorNode) && trim($authorNode) !== '') {
        $author = trim($authorNode);
    }

    $ratingRaw = $node['reviewRating']['ratingValue'] ?? $node['ratingValue'] ?? null;
    $rating = max(1, min(5, (int)round((float)$ratingRaw ?: 0)));

    $dateRaw = (string)($node['datePublished'] ?? '');
    $timestamp = strtotime($dateRaw) ?: time();
    $date = gmdate('Y-m-d', $timestamp);

    $text = trim((string)($node['reviewBody'] ?? $node['description'] ?? ''));
    if ($text === '') {
        return null;
    }

    return [
        'id' => 0,
        'source' => 'yandex_maps',
        'org_id' => $orgId,
        'author' => $author,
        'rating' => $rating,
        'date' => $date,
        'text' => $text,
        'reply' => '',
        'replied_at' => null,
    ];
}

function parseYandexReviews(string $html, string $orgId): array
{
    $result = [];
    $dedupe = [];

    $totalAvailable = 0;
    if (preg_match_all('/"reviewCount"\s*:\s*"?(\d+)"?/u', $html, $countMatches)) {
        foreach ($countMatches[1] as $countValue) {
            $totalAvailable = max($totalAvailable, (int)$countValue);
        }
    }

    $scriptMatches = [];
    preg_match_all('#<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#si', $html, $scriptMatches);

    foreach ($scriptMatches[1] ?? [] as $block) {
        $decoded = json_decode(html_entity_decode(trim($block), ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
        if (!$decoded) {
            continue;
        }

        $reviewNodes = [];
        collectReviewNodes($decoded, $reviewNodes);

        if (is_array($decoded) && isset($decoded['aggregateRating']['reviewCount'])) {
            $totalAvailable = max($totalAvailable, (int)$decoded['aggregateRating']['reviewCount']);
        }

        foreach ($reviewNodes as $node) {
            $mapped = mapReviewNode($node, $orgId);
            if (!$mapped) {
                continue;
            }

            $hash = md5($mapped['author'] . '|' . $mapped['date'] . '|' . $mapped['text']);
            if (isset($dedupe[$hash])) {
                continue;
            }

            $dedupe[$hash] = true;
            $result[] = $mapped;
        }
    }

    if (count($result) === 0) {
        if (preg_match_all('/"reviewBody"\s*:\s*"(.*?)"/u', $html, $bodyMatches)) {
            foreach ($bodyMatches[1] as $index => $rawBody) {
                $text = trim(html_entity_decode(str_replace(['\\n', '\\t', '\\r', '\\"', '\\\\'], ["\n", ' ', ' ', '"', '\\'], (string)$rawBody), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($text === '') {
                    continue;
                }

                $hash = md5($text);
                if (isset($dedupe[$hash])) {
                    continue;
                }

                $dedupe[$hash] = true;
                $result[] = [
                    'id' => 0,
                    'source' => 'yandex_maps',
                    'org_id' => $orgId,
                    'author' => 'Пользователь Яндекс',
                    'rating' => 5,
                    'date' => gmdate('Y-m-d'),
                    'text' => $text,
                    'reply' => '',
                    'replied_at' => null,
                ];

                if (count($result) >= 100) {
                    break;
                }
            }
        }
    }

    usort($result, static function (array $left, array $right): int {
        $leftDate = strtotime((string)($left['date'] ?? '')) ?: 0;
        $rightDate = strtotime((string)($right['date'] ?? '')) ?: 0;

        return $rightDate <=> $leftDate;
    });

    foreach ($result as $index => $item) {
        $result[$index]['id'] = $index + 1;
    }

    return [
        'reviews' => $result,
        'total_available' => max($totalAvailable, count($result)),
    ];
}

ensureDemoUser($usersFile);

if ($uri === '/' || $uri === '/demo.html') {
    header('Content-Type: text/html; charset=utf-8');
    echo renderDemoHtml($projectRoot);
    return true;
}

if (str_starts_with($uri, '/api/')) {
    $isPublicApi = str_starts_with($uri, '/api/public/');
    $authExclusions = ['/api/login', '/api/register'];
    $needsAuth = !$isPublicApi && !in_array($uri, $authExclusions, true);

    $authUser = getAuthenticatedUser($usersFile, $tokensFile);
    if ($needsAuth && !$authUser) {
        jsonResponse(['message' => 'Unauthenticated.'], 401);
        return true;
    }

    if ($uri === '/api/register' && $method === 'POST') {
        $data = requestData();
        $name = trim((string)($data['name'] ?? ''));
        $email = mb_strtolower(trim((string)($data['email'] ?? '')));
        $password = (string)($data['password'] ?? '');

        if ($name === '' || $email === '' || !str_contains($email, '@') || strlen($password) < 6) {
            jsonResponse(['message' => 'Проверьте корректность имени, email и пароля (минимум 6 символов).'], 422);
            return true;
        }

        $users = readUsers($usersFile);
        foreach ($users as $existingUser) {
            if (mb_strtolower((string)($existingUser['email'] ?? '')) === $email) {
                jsonResponse(['message' => 'Пользователь с таким email уже существует.'], 422);
                return true;
            }
        }

        $nextId = 1;
        foreach ($users as $user) {
            $nextId = max($nextId, ((int)($user['id'] ?? 0)) + 1);
        }

        $newUser = [
            'id' => $nextId,
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => gmdate('c'),
        ];

        $users[] = $newUser;
        saveUsers($usersFile, $users);

        $token = issueToken($tokensFile, $nextId);

        jsonResponse([
            'token' => $token,
            'user' => [
                'id' => $nextId,
                'email' => $email,
                'name' => $name,
            ],
        ]);
        return true;
    }

    if ($uri === '/api/login' && $method === 'POST') {
        $data = requestData();
        $email = mb_strtolower(trim((string)($data['email'] ?? '')));
        $password = (string)($data['password'] ?? '');

        $users = readUsers($usersFile);
        $matchedUser = null;
        foreach ($users as $user) {
            if (mb_strtolower((string)($user['email'] ?? '')) === $email) {
                $matchedUser = $user;
                break;
            }
        }

        if (!$matchedUser || !password_verify($password, (string)($matchedUser['password_hash'] ?? ''))) {
            jsonResponse(['message' => 'Invalid credentials'], 401);
            return true;
        }

        $token = issueToken($tokensFile, (int)$matchedUser['id']);

        jsonResponse([
            'token' => $token,
            'user' => [
                'id' => (int)$matchedUser['id'],
                'email' => (string)$matchedUser['email'],
                'name' => (string)($matchedUser['name'] ?? ''),
            ],
        ]);
        return true;
    }

    if ($uri === '/api/logout' && $method === 'POST') {
        $token = readAuthToken();
        if ($token !== '') {
            $tokens = readTokens($tokensFile);
            unset($tokens[$token]);
            saveTokens($tokensFile, $tokens);
        }

        jsonResponse(['ok' => true]);
        return true;
    }

    if (in_array($uri, ['/api/settings', '/api/public/settings'], true)) {
        if ($method === 'GET') {
            jsonResponse(readJson($settingsFile, []));
            return true;
        }

        if ($method === 'POST') {
            $data = requestData();
            $rawYandex = trim((string)($data['yandex_url'] ?? ''));
            $parsed = parseYandexInput($rawYandex);

            if ($rawYandex !== '' && !$parsed) {
                jsonResponse(['message' => 'Нужна корректная ссылка на отзывы организации Яндекс.Карт.'], 422);
                return true;
            }

            $settings = [
                'yandex_url' => $rawYandex,
                'yandex_org_id' => $parsed['org_id'] ?? null,
                'yandex_reviews_url' => $parsed['reviews_url'] ?? null,
            ];
            writeJson($settingsFile, $settings);
            jsonResponse(['ok' => true, 'settings' => $settings]);
            return true;
        }
    }

    if (in_array($uri, ['/api/import', '/api/public/import'], true) && $method === 'POST') {
        $settings = readJson($settingsFile, []);
        $data = requestData();
        $rawUrl = trim((string)($data['yandex_url'] ?? ($settings['yandex_url'] ?? '')));

        $parsed = parseYandexInput($rawUrl);
        if (!$parsed) {
            jsonResponse(['message' => 'Сначала сохраните корректную ссылку Яндекс.'], 422);
            return true;
        }

        $html = fetchHtml($parsed['reviews_url']);
        if ($html === null) {
            jsonResponse(['message' => 'Не удалось загрузить страницу Яндекс. Попробуйте позже.'], 422);
            return true;
        }

        $parsedResult = parseYandexReviews($html, $parsed['org_id']);
        $reviews = $parsedResult['reviews'];
        $totalAvailable = (int)$parsedResult['total_available'];

        if (count($reviews) === 0 && $totalAvailable === 0) {
            jsonResponse(['message' => 'Не удалось извлечь отзывы со страницы Яндекс.'], 422);
            return true;
        }

        writeJson($reviewsFile, $reviews);
        writeJson($importMetaFile, [
            'source' => 'yandex_maps',
            'org_id' => $parsed['org_id'],
            'total_available' => $totalAvailable,
            'imported_count' => count($reviews),
            'finished_at' => gmdate('c'),
        ]);

        jsonResponse([
            'job_id' => uniqid('imp_'),
            'status' => 'completed',
            'imported_count' => count($reviews),
            'total_available' => $totalAvailable,
            'finished_at' => gmdate('c'),
        ]);
        return true;
    }

    if (in_array($uri, ['/api/reviews', '/api/public/reviews'], true) && $method === 'GET') {
        $reviews = readJson($reviewsFile, []);
        $importMeta = readJson($importMetaFile, []);

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 5)));
        $sort = (string)($_GET['sort'] ?? 'default');

        if ($sort === 'newest' || $sort === 'oldest') {
            usort($reviews, static function (array $left, array $right) use ($sort): int {
                $leftDate = strtotime((string)($left['date'] ?? '')) ?: 0;
                $rightDate = strtotime((string)($right['date'] ?? '')) ?: 0;
                return $sort === 'oldest' ? ($leftDate <=> $rightDate) : ($rightDate <=> $leftDate);
            });
        } elseif ($sort === 'negative') {
            usort($reviews, static function (array $left, array $right): int {
                $ratingCmp = ((int)($left['rating'] ?? 0)) <=> ((int)($right['rating'] ?? 0));
                if ($ratingCmp !== 0) {
                    return $ratingCmp;
                }

                $leftDate = strtotime((string)($left['date'] ?? '')) ?: 0;
                $rightDate = strtotime((string)($right['date'] ?? '')) ?: 0;
                return $rightDate <=> $leftDate;
            });
        } elseif ($sort === 'positive') {
            usort($reviews, static function (array $left, array $right): int {
                $ratingCmp = ((int)($right['rating'] ?? 0)) <=> ((int)($left['rating'] ?? 0));
                if ($ratingCmp !== 0) {
                    return $ratingCmp;
                }

                $leftDate = strtotime((string)($left['date'] ?? '')) ?: 0;
                $rightDate = strtotime((string)($right['date'] ?? '')) ?: 0;
                return $rightDate <=> $leftDate;
            });
        }

        $knownTotal = (int)($importMeta['total_available'] ?? 0);
        $total = max(count($reviews), $knownTotal);
        $lastPage = max(1, (int)ceil(max(1, $total) / $perPage));
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;
        $slice = array_values(array_slice($reviews, $offset, $perPage));

        $average = count($reviews) > 0
            ? round(array_sum(array_map(static fn(array $item): int => (int)($item['rating'] ?? 0), $reviews)) / count($reviews), 1)
            : 0;

        jsonResponse([
            'data' => $slice,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'total' => $total,
                'sort' => $sort,
                'average_rating' => $average,
            ],
        ]);
        return true;
    }

    if (preg_match('#^/api(?:/public)?/reviews/(\d+)$#', $uri, $matches) === 1) {
        $id = (int)$matches[1];
        $reviews = readJson($reviewsFile, []);

        $index = null;
        foreach ($reviews as $key => $item) {
            if ((int)($item['id'] ?? 0) === $id) {
                $index = $key;
                break;
            }
        }

        if ($index === null) {
            jsonResponse(['message' => 'Отзыв не найден'], 404);
            return true;
        }

        if ($method === 'GET') {
            jsonResponse($reviews[$index]);
            return true;
        }

        if ($method === 'PATCH') {
            $data = requestData();
            if (isset($data['rating'])) {
                $reviews[$index]['rating'] = max(1, min(5, (int)$data['rating']));
            }
            if (array_key_exists('reply', $data)) {
                $reviews[$index]['reply'] = (string)($data['reply'] ?? '');
                $reviews[$index]['replied_at'] = gmdate('c');
            }
            writeJson($reviewsFile, array_values($reviews));
            jsonResponse(['ok' => true, 'review' => $reviews[$index]]);
            return true;
        }
    }

    jsonResponse(['message' => 'Not Found'], 404);
    return true;
}

if (file_exists($projectRoot . '/public' . $uri) && $uri !== '/') {
    return false;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'Not Found';
return true;
