<?php
namespace App\Http\Controllers;

use App\Services\YandexReviewsImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SettingsController extends Controller
{
    private function parseYandexReviewsUrl(?string $url): ?array
    {
        if (!$url) {
            return null;
        }

        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) {
            return null;
        }

        $host = mb_strtolower($parts['host']);
        if (!str_contains($host, 'yandex.')) {
            return null;
        }

        $path = $parts['path'] ?? '';
        if (!preg_match('#/org/[^/]+/(\d+)/reviews/?#u', $path, $matches)) {
            return null;
        }

        $orgId = $matches[1];

        return [
            'org_id' => $orgId,
            'reviews_url' => "https://yandex.ru/maps/org/{$orgId}/reviews/",
        ];
    }

    public function show(Request $r)
    {
        $json = Storage::disk('local')->exists('settings.json') ? json_decode(Storage::disk('local')->get('settings.json'), true) : [];
        return response()->json($json);
    }

    public function save(Request $r)
    {
        $r->validate(['yandex_url'=>'nullable|url']);

        $rawUrl = $r->yandex_url;
        $parsed = $this->parseYandexReviewsUrl($rawUrl);

        if ($rawUrl && !$parsed) {
            return response()->json([
                'message' => 'Нужна ссылка на страницу отзывов организации Яндекс.Карт.',
            ], 422);
        }

        $data = [
            'yandex_url' => $rawUrl,
            'yandex_org_id' => $parsed['org_id'] ?? null,
            'yandex_reviews_url' => $parsed['reviews_url'] ?? null,
        ];

        Storage::disk('local')->put('settings.json', json_encode($data));
        return response()->json(['ok'=>true, 'settings' => $data]);
    }

    public function import(Request $r)
    {
        $stored = Storage::disk('local')->exists('settings.json')
            ? json_decode(Storage::disk('local')->get('settings.json'), true)
            : [];

        $rawUrl = $r->input('yandex_url', $stored['yandex_url'] ?? null);
        if (!$rawUrl) {
            return response()->json(['message' => 'Сначала сохраните ссылку Яндекс.'], 422);
        }

        $parsed = $this->parseYandexReviewsUrl($rawUrl);
        if (!$parsed) {
            return response()->json(['message' => 'Невалидная ссылка Яндекс.'], 422);
        }

        $payload = [
            'job_id' => uniqid('imp_'),
            'status' => 'queued',
            'source' => 'yandex_maps',
            'org_id' => $parsed['org_id'],
            'reviews_url' => $parsed['reviews_url'],
        ];

        try {
            $importer = app(YandexReviewsImporter::class);
            $reviews = $importer->import($parsed['reviews_url'], $parsed['org_id']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        Storage::disk('local')->put('reviews.json', json_encode($reviews, JSON_UNESCAPED_UNICODE));

        $payload['status'] = 'completed';
        $payload['imported_count'] = count($reviews);
        $payload['finished_at'] = now()->toIso8601String();

        Storage::disk('local')->put('last_import.json', json_encode($payload));

        return response()->json($payload);
    }
}
