<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\CreateReviewInput;
use App\Dto\ReviewFilter;
use App\Entity\Review;
use App\Repository\ReviewRepository;
use App\Service\ReviewStatsCalculator;
use App\View\ReviewViewFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reviews', name: 'api_reviews_')]
final class ReviewApiController extends AbstractController
{
    public function __construct(
        private readonly ReviewRepository $reviews,
        private readonly ReviewStatsCalculator $statsCalculator,
        private readonly ReviewViewFactory $view,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $filter = ReviewFilter::fromQuery($request->query->all());
        $page = $this->reviews->findPaginated($filter);

        return $this->json([
            'data' => $this->view->reviewList($page['items']),
            'meta' => [
                'page' => $filter->page,
                'perPage' => $filter->perPage,
                'total' => $page['total'],
            ],
        ]);
    }

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(Request $request): JsonResponse
    {
        $filter = ReviewFilter::fromQuery($request->query->all());
        $stats = $this->statsCalculator->calculate($this->reviews->findRatings($filter));

        return $this->json($this->view->stats($stats));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateReviewInput $input): JsonResponse
    {
        $review = new Review(
            $input->platform,
            $input->externalId,
            $input->authorName,
            $input->rating,
            $input->content,
            new \DateTimeImmutable(),
        );
        $this->reviews->save($review);

        return $this->json($this->view->review($review), Response::HTTP_CREATED);
    }
}
