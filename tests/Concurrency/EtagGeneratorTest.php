<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\Concurrency;

use Freema\ReactAdminApiBundle\Concurrency\EtagGenerator;
use Freema\ReactAdminApiBundle\Interface\AdminEntityInterface;
use PHPUnit\Framework\TestCase;

final class EtagFixtureEntity implements AdminEntityInterface
{
    public function __construct(
        private readonly int $id,
        private readonly int $version,
        private readonly ?\DateTimeImmutable $updatedAt = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}

final class EtagGeneratorTest extends TestCase
{
    private EtagGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new EtagGenerator();
    }

    public function test_etag_is_stable_for_the_same_entity_revision(): void
    {
        $entity = new EtagFixtureEntity(1, 5, new \DateTimeImmutable('2026-01-01T00:00:00Z'));
        self::assertSame($this->generator->generate($entity), $this->generator->generate($entity));
    }

    public function test_etag_changes_when_version_changes(): void
    {
        $a = new EtagFixtureEntity(1, 5, new \DateTimeImmutable('2026-01-01T00:00:00Z'));
        $b = new EtagFixtureEntity(1, 6, new \DateTimeImmutable('2026-01-01T00:00:00Z'));
        self::assertNotSame($this->generator->generate($a), $this->generator->generate($b));
    }

    public function test_etag_changes_when_updated_at_changes(): void
    {
        $a = new EtagFixtureEntity(1, 5, new \DateTimeImmutable('2026-01-01T00:00:00Z'));
        $b = new EtagFixtureEntity(1, 5, new \DateTimeImmutable('2026-01-01T00:00:01Z'));
        self::assertNotSame($this->generator->generate($a), $this->generator->generate($b));
    }

    public function test_matches_accepts_wildcard_or_exact_value(): void
    {
        $entity = new EtagFixtureEntity(1, 5);
        $tag = $this->generator->generate($entity);
        self::assertTrue($this->generator->matches('*', $tag));
        self::assertTrue($this->generator->matches($tag, $tag));
        self::assertTrue($this->generator->matches('"foo", '.$tag, $tag));
        self::assertFalse($this->generator->matches('W/"different"', $tag));
    }
}
