<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Enum\Platform;
use App\Factory\ReviewFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class ReviewAdminControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testIndexShowsAggregateAndReviews(): void
    {
        ReviewFactory::createMany(3, ['platform' => Platform::Google]);

        $crawler = $this->client->request('GET', '/admin/reviews');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.stat-big');
        self::assertCount(3, $crawler->filter('tbody tr'));
    }

    public function testAddReviewThroughForm(): void
    {
        $crawler = $this->client->request('GET', '/admin/reviews/new');
        self::assertResponseIsSuccessful();

        $this->client->submit($crawler->selectButton('Save review')->form([
            'review[platform]' => Platform::Google->value,
            'review[externalId]' => 'form-1',
            'review[authorName]' => 'Form User',
            'review[rating]' => '5',
            'review[content]' => 'Added via the admin form.',
        ]));

        self::assertResponseRedirects('/admin/reviews');
        self::assertSame(1, ReviewFactory::repository()->count());
    }

    public function testFormRejectsInvalidRatingViaSharedDtoRules(): void
    {
        $crawler = $this->client->request('GET', '/admin/reviews/new');

        $this->client->submit($crawler->selectButton('Save review')->form([
            'review[platform]' => Platform::Google->value,
            'review[externalId]' => 'form-2',
            'review[authorName]' => 'Bad Rating',
            'review[rating]' => '9', // violates CreateReviewInput's Range(1, 5)
            'review[content]' => 'Should not be saved.',
        ]));

        // Invalid form re-renders instead of redirecting, and nothing is persisted.
        self::assertResponseIsUnprocessable();
        self::assertSame(0, ReviewFactory::repository()->count());
    }
}
