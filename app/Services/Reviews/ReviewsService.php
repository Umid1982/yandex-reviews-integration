<?php

namespace App\Services\Reviews;

use App\Repositories\Setting\SettingRepositoryInterface;
use App\Services\Reviews\Providers\ReviewsProviderInterface;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ReviewsService implements ReviewsServiceInterface
{
    public function __construct(
        private readonly SettingRepositoryInterface $settingRepository,
        private readonly ReviewsProviderInterface   $provider
    )
    {
    }

    /**
     * @param int $limit
     * @return array
     */
    public function getReviews(int $limit = 10): array
    {
        $organizationId = $this->getOrganizationId();

        try {
            return $this->getRealReviews($organizationId, $limit);
        } catch (Throwable $e) {
            Log::warning('Failed to fetch reviews from Yandex, using mock data', [
                'error' => $e->getMessage(),
                'organization_id' => $organizationId,
            ]);

            return $this->getMockReviews();
        }
    }

    /**
     * @return string
     */
    private function getOrganizationId(): string
    {
        $organizationId = $this->settingRepository->getOrganizationId();

        if (!$organizationId) {
            throw new RuntimeException('Ссылка на Яндекс карты не настроена');
        }

        return $organizationId;
    }

    /**
     * @param string $organizationId
     * @param int $limit
     * @return array
     */
    private function getRealReviews(string $organizationId, int $limit): array
    {
        $data = $this->provider->getReviews($organizationId, $limit);
        $data['_source'] = 'yandex';

        return $data;
    }

    /**
     * @return array
     */
    private function getMockReviews(): array
    {
        return [
            'reviews' => [
                [
                    'id' => 1,
                    'author' => 'Иван Иванов',
                    'branch' => 'Филиал 1',
                    'text' => 'Очень понравилось обслуживание!',
                    'rating' => 5,
                    'date' => '2024-11-12 14:22',
                    'phone' => '+7 (999) 123-45-67',
                ],
                [
                    'id' => 2,
                    'author' => 'Петр Петров',
                    'branch' => 'Филиал 2',
                    'text' => 'Хорошее место, рекомендую.',
                    'rating' => 4,
                    'date' => '2024-11-10 10:15',
                ],
            ],
            'rating' => 4.7,
            'total_reviews' => 148,
            '_source' => 'mock',
            '_message' => 'Используются демо-данные. Яндекс API недоступен или требует авторизацию.',
        ];
    }
}
