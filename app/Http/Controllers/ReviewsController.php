<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReviewsController extends Controller
{
    private function loadReviews(): array
    {
        if (!Storage::disk('local')->exists('reviews.json')) {
            return [];
        }

        $raw = Storage::disk('local')->get('reviews.json');
        $parsed = json_decode($raw, true);

        return is_array($parsed) ? $parsed : [];
    }

    private function saveReviews(array $reviews): void
    {
        Storage::disk('local')->put(
            'reviews.json',
            json_encode(array_values($reviews), JSON_UNESCAPED_UNICODE)
        );
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort' => 'nullable|in:newest,oldest',
        ]);

        $page = (int)($validated['page'] ?? 1);
        $perPage = (int)($validated['per_page'] ?? 5);
        $sort = $validated['sort'] ?? 'newest';

        $reviews = $this->loadReviews();

        usort($reviews, function (array $left, array $right) use ($sort) {
            $leftDate = strtotime((string)($left['date'] ?? '')) ?: 0;
            $rightDate = strtotime((string)($right['date'] ?? '')) ?: 0;

            if ($leftDate === $rightDate) {
                $leftId = (int)($left['id'] ?? 0);
                $rightId = (int)($right['id'] ?? 0);
                return $sort === 'newest' ? ($rightId <=> $leftId) : ($leftId <=> $rightId);
            }

            return $sort === 'newest' ? ($rightDate <=> $leftDate) : ($leftDate <=> $rightDate);
        });

        $total = count($reviews);
        $lastPage = max(1, (int)ceil($total / $perPage));
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;

        $slice = array_slice($reviews, $offset, $perPage);

        $averageRating = $total > 0
            ? round(array_sum(array_map(fn ($item) => (int)($item['rating'] ?? 0), $reviews)) / $total, 1)
            : 0;

        return response()->json([
            'data' => array_values($slice),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'total' => $total,
                'sort' => $sort,
                'average_rating' => $averageRating,
            ],
        ]);
    }

    public function show(int $id)
    {
        $reviews = $this->loadReviews();
        $review = collect($reviews)->firstWhere('id', $id);

        if (!$review) {
            return response()->json(['message' => 'Отзыв не найден'], 404);
        }

        return response()->json($review);
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'rating' => 'nullable|integer|min:1|max:5',
            'reply' => 'nullable|string|max:3000',
        ]);

        $reviews = $this->loadReviews();
        $index = collect($reviews)->search(fn ($item) => (int)($item['id'] ?? 0) === $id);

        if ($index === false) {
            return response()->json(['message' => 'Отзыв не найден'], 404);
        }

        $current = $reviews[$index];

        if (array_key_exists('rating', $validated) && $validated['rating'] !== null) {
            $current['rating'] = (int)$validated['rating'];
        }

        if (array_key_exists('reply', $validated)) {
            $current['reply'] = (string)($validated['reply'] ?? '');
            $current['replied_at'] = now()->toIso8601String();
        }

        $reviews[$index] = $current;
        $this->saveReviews($reviews);

        return response()->json(['ok' => true, 'review' => $current]);
    }
}
