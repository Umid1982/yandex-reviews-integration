<?php

namespace App\Services\Reviews\Providers;

interface ReviewsProviderInterface
{
    /**
     * @param string $organizationId
     * @param int $limit
     * @return array
     */
    public function getReviews(string $organizationId, int $limit = 10): array;
}
