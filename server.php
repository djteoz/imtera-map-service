<?php

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$storageRoot = __DIR__ . '/storage/app';
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

function isAuthorizedRequest(): bool
{
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    return str_starts_with($auth, 'Bearer ');
}

if ($uri === '/') {
    readfile(__DIR__.'/public/demo.html');
    return true;
}

if (str_starts_with($uri, '/api/')) {
    $reviewsFile = $storageRoot . '/reviews.json';
    $settingsFile = $storageRoot . '/settings.json';

    $isPublicApi = str_starts_with($uri, '/api/public/');
    $requiresAuth = !$isPublicApi && !in_array($uri, ['/api/login'], true);

    if ($requiresAuth && !isAuthorizedRequest()) {
        jsonResponse(['message' => 'Unauthenticated.'], 401);
        return true;
    }

    if ($uri === '/api/login' && $method === 'POST') {
        $data = requestData();
        $email = (string) ($data['email'] ?? '');
        $password = (string) ($data['password'] ?? '');

        if ($email !== 'demo@imtera.ru' || $password !== 'demo12345') {
            jsonResponse(['message' => 'Invalid credentials'], 401);
            return true;
        }

        jsonResponse([
            'token' => 'demo-token-' . md5($email),
            'user' => [
                'id' => 1,
                'email' => 'demo@imtera.ru',
                'name' => 'Imtera Demo',
            ],
        ]);
        return true;
    }

    if ($uri === '/api/logout' && $method === 'POST') {
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
            $settings = [
                'yandex_url' => (string) ($data['yandex_url'] ?? ''),
            ];
            writeJson($settingsFile, $settings);
            jsonResponse(['ok' => true, 'settings' => $settings]);
            return true;
        }
    }

    if (in_array($uri, ['/api/import', '/api/public/import'], true) && $method === 'POST') {
        $reviews = readJson($reviewsFile, []);
        jsonResponse([
            'job_id' => uniqid('imp_'),
            'status' => 'completed',
            'imported_count' => count($reviews),
            'finished_at' => gmdate('c'),
        ]);
        return true;
    }

    if (in_array($uri, ['/api/reviews', '/api/public/reviews'], true) && $method === 'GET') {
        $reviews = readJson($reviewsFile, []);

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 5)));
        $sort = (string) ($_GET['sort'] ?? 'newest');

        usort($reviews, static function (array $left, array $right) use ($sort): int {
            $leftDate = strtotime((string) ($left['date'] ?? '')) ?: 0;
            $rightDate = strtotime((string) ($right['date'] ?? '')) ?: 0;
            return $sort === 'oldest' ? ($leftDate <=> $rightDate) : ($rightDate <=> $leftDate);
        });

        $total = count($reviews);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;
        $slice = array_values(array_slice($reviews, $offset, $perPage));

        $average = $total > 0
            ? round(array_sum(array_map(static fn(array $item): int => (int) ($item['rating'] ?? 0), $reviews)) / $total, 1)
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
        $id = (int) $matches[1];
        $reviews = readJson($reviewsFile, []);

        $index = null;
        foreach ($reviews as $key => $item) {
            if ((int) ($item['id'] ?? 0) === $id) {
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
                $reviews[$index]['rating'] = max(1, min(5, (int) $data['rating']));
            }
            if (array_key_exists('reply', $data)) {
                $reviews[$index]['reply'] = (string) ($data['reply'] ?? '');
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

if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

require_once __DIR__.'/public/index.php';
