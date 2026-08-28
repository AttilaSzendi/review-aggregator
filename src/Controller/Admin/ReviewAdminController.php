<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\CreateReviewInput;
use App\Dto\ReviewFilter;
use App\Enum\Platform;
use App\Form\ReviewType;
use App\Repository\ReviewRepository;
use App\Service\ReviewCreator;
use App\Service\ReviewStatsCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/reviews', name: 'admin_reviews_')]
final class ReviewAdminController extends AbstractController
{
    public function __construct(
        private readonly ReviewRepository $reviews,
        private readonly ReviewStatsCalculator $statsCalculator,
        private readonly ReviewCreator $reviewCreator,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $filter = ReviewFilter::fromQuery($request->query->all());
        $page = $this->reviews->findPaginated($filter);
        $stats = $this->statsCalculator->calculate($this->reviews->findRatings($filter));

        return $this->render('admin/review/index.html.twig', [
            'reviews' => $page['items'],
            'total' => $page['total'],
            'filter' => $filter,
            'stats' => $stats,
            'platforms' => Platform::cases(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $form = $this->createForm(ReviewType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CreateReviewInput $input */
            $input = $form->getData();
            $this->reviewCreator->create($input);

            $this->addFlash('success', 'Review added.');

            return $this->redirectToRoute('admin_reviews_index');
        }

        // 422 on an invalid submission (standard Symfony practice; also lets
        // Turbo/AJAX clients detect the failure); 200 on the initial GET.
        $status = $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK;

        return $this->render('admin/review/new.html.twig', [
            'form' => $form->createView(),
        ], new Response(status: $status));
    }
}
