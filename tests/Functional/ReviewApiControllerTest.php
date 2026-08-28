<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Review;
use App\Enum\Platform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ReviewApiControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $this->resetSchema();
        $this->seedReviews();
    }

    public function testListReturnsSeededReviews(): void
    {
        $this->client->request('GET', '/api/reviews');

        self::assertResponseIsSuccessful();
        $payload = $this->json();

        self::assertSame(3, $payload['meta']['total']);
        self::assertCount(3, $payload['data']);
        // Ordered by reviewedAt DESC — newest first.
        self::assertSame('google', $payload['data'][0]['platform']);
    }

    public function testListCanBeFilteredByPlatform(): void
    {
        $this->client->request('GET', '/api/reviews?platform=trustpilot');

        self::assertResponseIsSuccessful();
        $payload = $this->json();

        self::assertSame(1, $payload['meta']['total']);
        self::assertSame('trustpilot', $payload['data'][0]['platform']);
    }

    public function testStatsAggregatesRatings(): void
    {
        $this->client->request('GET', '/api/reviews/stats');

        self::assertResponseIsSuccessful();
        $payload = $this->json();

        self::assertSame(3, $payload['total']);
        // JSON does not distinguish 4 from 4.0, so compare numerically.
        self::assertEqualsWithDelta(4.0, $payload['average'], 0.001); // (5 + 3 + 4) / 3
        self::assertSame(1, $payload['distribution']['5']);
        self::assertSame(0, $payload['distribution']['1']);
    }

    public function testCreatePersistsReview(): void
    {
        $this->client->request(
            'POST',
            '/api/reviews',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'platform' => 'yelp',
                'externalId' => 'y-1',
                'authorName' => 'New Reviewer',
                'rating' => 5,
                'content' => 'Added through the API.',
            ], \JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
        $payload = $this->json();
        self::assertSame('yelp', $payload['platform']);
        self::assertNotNull($payload['id']);

        self::assertSame(4, $this->em->getRepository(Review::class)->count([]));
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

    private function resetSchema(): void
    {
        $tool = new SchemaTool($this->em);
        $classes = $this->em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($classes);
        $tool->createSchema($classes);
    }

    private function seedReviews(): void
    {
        $rows = [
            [Platform::Google, 'g-1', 'Anna', 5, 'Great', '-1 day'],
            [Platform::Facebook, 'f-1', 'Bob', 3, 'Okay', '-2 days'],
            [Platform::Trustpilot, 'tp-1', 'Cara', 4, 'Good', '-3 days'],
        ];

        foreach ($rows as [$platform, $externalId, $author, $rating, $content, $when]) {
            $this->em->persist(new Review(
                $platform,
                $externalId,
                $author,
                $rating,
                $content,
                new \DateTimeImmutable($when),
            ));
        }
        $this->em->flush();
        $this->em->clear();
    }
}
