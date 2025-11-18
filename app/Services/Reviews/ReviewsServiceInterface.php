<?php

namespace App\Services\Reviews;

interface ReviewsServiceInterface
{
    /**
     * @param int $limit
     * @return array
     */
    public function getReviews(int $limit = 10): array;
}
