<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseHelper;
use App\Services\Reviews\ReviewsServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Throwable;

class ReviewController extends Controller
{
    use ApiResponseHelper;

    public function __construct(private readonly ReviewsServiceInterface $reviewsService)
    {
    }

    /**
     * @return View
     */
    public function index(): View
    {
        return view('reviews.index');
    }

    /**
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        try {
            return $this->successResponse(
                $this->reviewsService->getReviews(10)
            );
        } catch (Throwable $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
