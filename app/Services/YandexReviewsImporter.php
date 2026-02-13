<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class YandexReviewsImporter
{
    public function import(string $reviewsUrl, string $orgId): array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
            'Accept-Language' => 'ru-RU,ru;q=0.9,en;q=0.8',
        ])
            ->timeout(20)
            ->retry(3, 700, throw: false)
            ->get($reviewsUrl);

        if (!$response->successful()) {
            throw new RuntimeException('Не удалось получить страницу Яндекс. Проверьте ссылку и попробуйте позже.');
        }

        $reviews = $this->parseFromJsonLd((string)$response->body(), $orgId);

        if (count($reviews) === 0) {
            throw new RuntimeException('Не удалось извлечь отзывы со страницы Яндекс. Возможно, страница требует JS или временно недоступна.');
        }

        usort($reviews, function (array $left, array $right) {
            $leftDate = strtotime((string)($left['date'] ?? '')) ?: 0;
            $rightDate = strtotime((string)($right['date'] ?? '')) ?: 0;

            if ($leftDate === $rightDate) {
                return ((int)$right['id']) <=> ((int)$left['id']);
            }

            return $rightDate <=> $leftDate;
        });

        foreach ($reviews as $index => $review) {
            $reviews[$index]['id'] = $index + 1;
        }

        return $reviews;
    }

    private function parseFromJsonLd(string $html, string $orgId): array
    {
        $matches = [];
        preg_match_all('#<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#si', $html, $matches);

        $result = [];
        $dedupe = [];

        foreach ($matches[1] ?? [] as $block) {
            $decoded = json_decode(html_entity_decode(trim($block), ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);

            if (!$decoded) {
                continue;
            }

            $reviewNodes = [];
            $this->collectReviewNodes($decoded, $reviewNodes);

            foreach ($reviewNodes as $node) {
                $mapped = $this->mapNodeToReview($node, $orgId);
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

        return $result;
    }

    private function collectReviewNodes(mixed $node, array &$bucket): void
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

        foreach ($node as $value) {
            if (is_array($value)) {
                $this->collectReviewNodes($value, $bucket);
            }
        }
    }

    private function mapNodeToReview(array $node, string $orgId): ?array
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
        if ($rating < 1) {
            $rating = 5;
        }

        $dateRaw = (string)($node['datePublished'] ?? '');
        try {
            $date = $dateRaw !== '' ? Carbon::parse($dateRaw)->toDateString() : now()->toDateString();
        } catch (\Throwable) {
            $date = now()->toDateString();
        }

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
}
