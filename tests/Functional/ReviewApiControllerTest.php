<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Enum\Platform;
use App\Factory\ReviewFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class ReviewApiControllerTest extends WebTestCase
{
    // ResetDatabase rebuilds the schema for the test run and clears rows between
    // tests; Factories enables the ReviewFactory below. Together they replace the
    // hand-written schema reset + seeding.
    use Factories;
    use ResetDatabase;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testListReturnsSeededReviews(): void
    {
        ReviewFactory::new()->onPlatform(Platform::Google)->reviewedAt('-1 day')->create();
        ReviewFactory::new()->onPlatform(Platform::Facebook)->reviewedAt('-2 days')->create();
        ReviewFactory::new()->onPlatform(Platform::Trustpilot)->reviewedAt('-3 days')->create();

        $this->client->request('GET', '/api/reviews');

        self::assertResponseIsSuccessful();
        $payload = $this->json();

        self::assertSame(3, $payload['meta']['total']);
        self::assertCount(3, $payload['data']);
        self::assertSame('google', $payload['data'][0]['platform']); // newest first
    }

    public function testListCanBeFilteredByPlatform(): void
    {
        ReviewFactory::createMany(2, ['platform' => Platform::Google]);
        ReviewFactory::new()->onPlatform(Platform::Trustpilot)->create();

        $this->client->request('GET', '/api/reviews?platform=trustpilot');

        self::assertResponseIsSuccessful();
        $payload = $this->json();

        self::assertSame(1, $payload['meta']['total']);
        self::assertSame('trustpilot', $payload['data'][0]['platform']);
    }

    public function testStatsAggregatesRatings(): void
    {
        ReviewFactory::new()->withRating(5)->create();
        ReviewFactory::new()->withRating(3)->create();
        ReviewFactory::new()->withRating(4)->create();

        $this->client->request('GET', '/api/reviews/stats');

        self::assertResponseIsSuccessful();
        $payload = $this->json();

        self::assertSame(3, $payload['total']);
        self::assertEqualsWithDelta(4.0, $payload['average'], 0.001); // (5 + 3 + 4) / 3
        self::assertSame(1, $payload['distribution']['5']);
        self::assertSame(0, $payload['distribution']['1']);
    }

    public function testCreatePersistsReviewAndGeneratesExternalId(): void
    {
        // externalId omitted — the API generates one for non-imported reviews.
        $this->client->request(
            'POST',
            '/api/reviews',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'platform' => 'yelp',
                'authorName' => 'New Reviewer',
                'rating' => 5,
                'content' => 'Added through the API.',
            ], \JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
        $payload = $this->json();
        self::assertSame('yelp', $payload['platform']);
        self::assertNotNull($payload['id']);
        self::assertStringStartsWith('manual-', $payload['externalId']);

        self::assertSame(1, ReviewFactory::repository()->count());
    }

    public function testCreateHonoursSuppliedExternalId(): void
    {
        $this->client->request(
            'POST',
            '/api/reviews',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'platform' => 'yelp',
                'externalId' => 'y-explicit-1',
                'authorName' => 'Importer',
                'rating' => 4,
                'content' => 'Came with its own id.',
            ], \JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
        self::assertSame('y-explicit-1', $this->json()['externalId']);
    }

    public function testCreateRejectsInvalidRating(): void
    {
        $this->client->request(
            'POST',
            '/api/reviews',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'platform' => 'yelp',
                'externalId' => 'y-2',
                'authorName' => 'Bad Rating',
                'rating' => 9,
                'content' => 'Rating is out of range.',
            ], \JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(422);
    }

    /**
     * @return array<string, mixed>
     */
    private function json(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }
}
