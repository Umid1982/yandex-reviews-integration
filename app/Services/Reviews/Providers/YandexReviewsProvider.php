<?php

namespace App\Services\Reviews\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class YandexReviewsProvider implements ReviewsProviderInterface
{
    private const API_TIMEOUT = 15;
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    private array $apiEndpoints = [
        "https://yandex.ru/maps/api/business/reviews?id={organizationId}&limit={limit}",
        "https://yandex.ru/maps/api/business/{organizationId}/reviews?limit={limit}",
        "https://maps.yandex.ru/api/business/{organizationId}/reviews?limit={limit}",
    ];

    /**
     * @param string $organizationId
     * @param int $limit
     * @return array
     * @throws Throwable
     */
    public function getReviews(string $organizationId, int $limit = 10): array
    {
        $cacheKey = "yandex_reviews:{$organizationId}:{$limit}";

        return cache()->remember($cacheKey, now()->addMinutes(10), function () use ($organizationId, $limit) {
            $data = $this->fetchReviewsData($organizationId, $limit);
            return $this->formatReviewsData($data, $limit);
        });
    }

    /**
     * @param string $organizationId
     * @param int $limit
     * @return array
     */
    private function fetchReviewsData(string $organizationId, int $limit): array
    {
        $data = $this->fetchFromApi($organizationId, $limit);

        if ($data) {
            return $data;
        }

        $data = $this->parseFromHtml($organizationId, $limit);

        if (!$data) {
            throw new RuntimeException('Не удалось получить данные отзывов');
        }

        return $data;
    }

    /**
     * @param string $organizationId
     * @param int $limit
     * @return array|null
     */
    private function fetchFromApi(string $organizationId, int $limit): ?array
    {
        foreach ($this->apiEndpoints as $endpoint) {
            $url = str_replace(
                ['{organizationId}', '{limit}'],
                [$organizationId, $limit],
                $endpoint
            );

            $response = $this->makeApiRequest($url, $organizationId);

            if ($response && $this->isValidResponse($response)) {
                return $response;
            }
        }

        return null;
    }

    /**
     * @param string $url
     * @param string $organizationId
     * @return array|null
     */
    private function makeApiRequest(string $url, string $organizationId): ?array
    {
        try {
            $response = Http::timeout(self::API_TIMEOUT)
                ->withHeaders($this->getApiHeaders($organizationId))
                ->withoutVerifying()
                ->get($url);

            if (!$response->successful()) {
                $this->logApiError($url, $response->status(), $response->body());
                return null;
            }

            $data = $response->json();
            return is_array($data) ? $data : null;

        } catch (Throwable $e) {
            Log::warning('Yandex API request failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * @param string $organizationId
     * @return string[]
     */
    private function getApiHeaders(string $organizationId): array
    {
        return [
            'User-Agent' => self::USER_AGENT,
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Referer' => "https://yandex.ru/maps/org/{$organizationId}/",
            'Origin' => 'https://yandex.ru',
            'Connection' => 'keep-alive',
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-origin',
        ];
    }

    /**
     * @param array|null $data
     * @return bool
     */
    private function isValidResponse(?array $data): bool
    {
        return $data && (isset($data['items']) || isset($data['reviews']));
    }

    /**
     * @param string $url
     * @param int $status
     * @param string $body
     * @return void
     */
    private function logApiError(string $url, int $status, string $body): void
    {
        Log::info('Yandex API response', [
            'url' => $url,
            'status' => $status,
            'body' => substr($body, 0, 1000), // Ограничиваем длину лога
        ]);
    }

    /**
     * @param string $organizationId
     * @param int $limit
     * @return array|null
     */
    private function parseFromHtml(string $organizationId, int $limit): ?array
    {
        $url = "https://yandex.ru/maps/org/{$organizationId}/reviews/";

        try {
            $response = Http::timeout(self::API_TIMEOUT)
                ->withHeaders($this->getHtmlHeaders())
                ->withoutVerifying()
                ->get($url);

            if (!$response->successful()) {
                Log::warning('Yandex HTML parse failed', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);
                return null;
            }

            return $this->extractDataFromHtml($response->body(), $url);

        } catch (Throwable $e) {
            Log::error('Yandex HTML parse error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * @return array
     */
    private function getHtmlHeaders(): array
    {
        return [
            'User-Agent' => self::USER_AGENT,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => 1,
        ];
    }

    /**
     * @param string $html
     * @param string $url
     * @return array|null
     */
    private function extractDataFromHtml(string $html, string $url): ?array
    {
        $parsers = [
            'window.__INITIAL_STATE__' => fn($html) => $this->parseJsonFromPattern('/window\.__INITIAL_STATE__\s*=\s*({.+?});/s', $html),
            'window.__INITIAL_DATA__' => fn($html) => $this->parseJsonFromPattern('/window\.__INITIAL_DATA__\s*=\s*({.+?});/s', $html),
            'window.ymaps' => fn($html) => $this->parseJsonFromPattern('/window\.ymaps\s*=\s*({.+?});/s', $html),
            'script_json' => fn($html) => $this->parseJsonFromScriptTags($html),
            'data_attributes' => fn($html) => $this->parseJsonFromDataAttributes($html),
            'json_reviews' => fn($html) => $this->parseJsonFromReviewsPattern($html),
            'html_comments' => fn($html) => $this->parseJsonFromComments($html),
        ];

        foreach ($parsers as $parserName => $parser) {
            $data = $parser($html);
            if ($data && $this->containsReviewsData($data)) {
                Log::info("Yandex HTML parse success", ['parser' => $parserName]);
                return $data;
            }
        }

        $this->logHtmlParseFailure($html, $url);
        return null;
    }

    /**
     * @param string $pattern
     * @param string $html
     * @return array|null
     */
    private function parseJsonFromPattern(string $pattern, string $html): ?array
    {
        if (preg_match($pattern, $html, $matches)) {
            return json_decode($matches[1], true);
        }
        return null;
    }

    /**
     * @param string $html
     * @return array|null
     */
    private function parseJsonFromScriptTags(string $html): ?array
    {
        if (preg_match_all('/<script[^>]*type=["\']application\/json["\'][^>]*>(.+?)<\/script>/s', $html, $matches)) {
            foreach ($matches[1] as $jsonStr) {
                $data = json_decode(trim($jsonStr), true);
                if ($data) {
                    return $data;
                }
            }
        }
        return null;
    }

    /**
     * @param string $html
     * @return array|null
     */
    private function parseJsonFromDataAttributes(string $html): ?array
    {
        if (preg_match_all('/data-[\w-]+=["\']({.+?})["\']/s', $html, $matches)) {
            foreach ($matches[1] as $jsonStr) {
                $decoded = html_entity_decode($jsonStr, ENT_QUOTES | ENT_HTML5);
                $data = json_decode($decoded, true);
                if ($data) {
                    return $data;
                }
            }
        }
        return null;
    }

    /**
     * @param string $html
     * @return array|null
     */
    private function parseJsonFromReviewsPattern(string $html): ?array
    {
        if (preg_match_all('/{"(?:reviews|items|businessReviews)":\s*\[.+?\]/s', $html, $matches)) {
            foreach ($matches[0] as $jsonStr) {
                $data = json_decode($jsonStr, true);
                if ($data) {
                    return $data;
                }
            }
        }
        return null;
    }

    /**
     * @param string $html
     * @return array|null
     */
    private function parseJsonFromComments(string $html): ?array
    {
        if (preg_match('/<!--\s*({.+?})\s*-->/s', $html, $matches)) {
            return json_decode($matches[1], true);
        }
        return null;
    }

    /**
     * @param array $data
     * @return bool
     */
    private function containsReviewsData(array $data): bool
    {
        return isset($data['businessReviews']) || isset($data['reviews']) || isset($data['items']);
    }

    /**
     * @param string $html
     * @param string $url
     * @return void
     */
    private function logHtmlParseFailure(string $html, string $url): void
    {
        Log::info('Yandex HTML parse: no data found', [
            'url' => $url,
            'html_length' => strlen($html),
            'html_preview' => substr($html, 0, 5000),
        ]);
    }

    /**
     * @param array $data
     * @param int $limit
     * @return array
     */
    private function formatReviewsData(array $data, int $limit): array
    {
        $items = $data['items'] ?? $data['reviews'] ?? [];
        $reviews = [];

        foreach (array_slice($items, 0, $limit) as $item) {
            $reviews[] = [
                'id' => $item['id'] ?? uniqid(),
                'author' => $item['author']['name'] ?? $item['authorName'] ?? 'Аноним',
                'phone' => $item['author']['phone'] ?? $item['phone'] ?? null,
                'rating' => $item['rating'] ?? $item['stars'] ?? 5,
                'text' => $item['text'] ?? $item['reviewText'] ?? '',
                'date' => $item['date'] ?? $item['createdAt'] ?? now()->toIso8601String(),
                'branch' => $item['branch'] ?? $item['branchName'] ?? null,
            ];
        }

        return [
            'reviews' => $reviews,
            'rating' => $this->calculateRating($data, $reviews),
            'total_reviews' => $data['total'] ?? $data['totalReviews'] ?? count($reviews),
        ];
    }

    /**
     * @param array $data
     * @param array $reviews
     * @return float
     */
    private function calculateRating(array $data, array $reviews): float
    {
        if (isset($data['rating']) || isset($data['averageRating'])) {
            return $data['rating'] ?? $data['averageRating'];
        }

        $ratings = array_column($reviews, 'rating');
        return count($ratings) > 0 ? array_sum($ratings) / count($ratings) : 0.0;
    }
}
