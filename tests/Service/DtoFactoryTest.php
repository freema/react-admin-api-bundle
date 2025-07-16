<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\Service;

use Freema\ReactAdminApiBundle\Service\DtoFactory;
use Freema\ReactAdminApiBundle\Interface\DtoInterface;
use Freema\ReactAdminApiBundle\Exception\DtoClassNotFoundException;
use Freema\ReactAdminApiBundle\Exception\DtoInterfaceNotImplementedException;
use Freema\ReactAdminApiBundle\Exception\DtoCreationException;
use PHPUnit\Framework\TestCase;

class DtoFactoryTest extends TestCase
{
    private DtoFactory $dtoFactory;

    protected function setUp(): void
    {
        $this->dtoFactory = new DtoFactory();
    }

    public function testCreateFromArraySuccessfully(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'active' => true,
            'age' => 30,
        ];

        $dto = $this->dtoFactory->createFromArray($data, TestDto::class);

        $this->assertInstanceOf(TestDto::class, $dto);
        $this->assertEquals('John Doe', $dto->name);
        $this->assertEquals('john@example.com', $dto->email);
        $this->assertTrue($dto->active);
        $this->assertEquals(30, $dto->age);
    }

    public function testCreateFromArrayWithMissingProperties(): void
    {
        $data = [
            'name' => 'John Doe',
            'nonExistentProperty' => 'value',
        ];

        $dto = $this->dtoFactory->createFromArray($data, TestDto::class);

        $this->assertInstanceOf(TestDto::class, $dto);
        $this->assertEquals('John Doe', $dto->name);
        $this->assertEquals('', $dto->email); // Default value
        $this->assertFalse($dto->active); // Default value
        $this->assertEquals(0, $dto->age); // Default value
    }

    public function testCreateFromArrayWithEmptyData(): void
    {
        $data = [];

        $dto = $this->dtoFactory->createFromArray($data, TestDto::class);

        $this->assertInstanceOf(TestDto::class, $dto);
        $this->assertEquals('', $dto->name);
        $this->assertEquals('', $dto->email);
        $this->assertFalse($dto->active);
        $this->assertEquals(0, $dto->age);
    }

    public function testThrowsExceptionWhenClassNotExists(): void
    {
        $this->expectException(DtoClassNotFoundException::class);
        $this->expectExceptionMessage("DTO class 'NonExistentClass' does not exist");

        $this->dtoFactory->createFromArray([], 'NonExistentClass');
    }

    public function testThrowsExceptionWhenClassDoesNotImplementInterface(): void
    {
        $this->expectException(DtoInterfaceNotImplementedException::class);
        $this->expectExceptionMessage("Class 'stdClass' must implement DtoInterface");

        $this->dtoFactory->createFromArray([], \stdClass::class);
    }

    public function testThrowsExceptionWhenReflectionFails(): void
    {
        $this->expectException(DtoCreationException::class);
        $this->expectExceptionMessage("Failed to create DTO");

        $this->dtoFactory->createFromArray([], BrokenDto::class);
    }

    public function testCreateFromArrayWithNullValues(): void
    {
        $data = [
            'name' => null,
            'email' => null,
            'active' => null,
            'age' => null,
        ];

        $dto = $this->dtoFactory->createFromArray($data, NullableTestDto::class);

        $this->assertInstanceOf(NullableTestDto::class, $dto);
        $this->assertNull($dto->name);
        $this->assertNull($dto->email);
        $this->assertNull($dto->active);
        $this->assertNull($dto->age);
    }

    public function testCreateFromArrayWithComplexData(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'active' => true,
            'age' => 30,
            'tags' => ['admin', 'user'],
            'metadata' => ['key' => 'value'],
        ];

        $dto = $this->dtoFactory->createFromArray($data, ComplexTestDto::class);

        $this->assertInstanceOf(ComplexTestDto::class, $dto);
        $this->assertEquals('John Doe', $dto->name);
        $this->assertEquals('john@example.com', $dto->email);
        $this->assertTrue($dto->active);
        $this->assertEquals(30, $dto->age);
        $this->assertEquals(['admin', 'user'], $dto->tags);
        $this->assertEquals(['key' => 'value'], $dto->metadata);
    }
}

// Test DTOs
class TestDto implements DtoInterface
{
    public string $name = '';
    public string $email = '';
    public bool $active = false;
    public int $age = 0;

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'active' => $this->active,
            'age' => $this->age,
        ];
    }

    public static function getMappedEntityClass(): string
    {
        return 'TestEntity';
    }

    public static function createFromEntity($entity): self
    {
        return new self();
    }
}

class ComplexTestDto implements DtoInterface
{
    public string $name = '';
    public string $email = '';
    public bool $active = false;
    public int $age = 0;
    public array $tags = [];
    public array $metadata = [];

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'active' => $this->active,
            'age' => $this->age,
            'tags' => $this->tags,
            'metadata' => $this->metadata,
        ];
    }

    public static function getMappedEntityClass(): string
    {
        return 'ComplexTestEntity';
    }

    public static function createFromEntity($entity): self
    {
        return new self();
    }
}

class BrokenDto implements DtoInterface
{
    private function __construct()
    {
        throw new \Exception('This constructor always fails');
    }

    public function toArray(): array
    {
        return [];
    }

    public static function getMappedEntityClass(): string
    {
        return 'BrokenEntity';
    }

    public static function createFromEntity($entity): self
    {
        return new self();
    }
}

class NullableTestDto implements DtoInterface
{
    public ?string $name = null;
    public ?string $email = null;
    public ?bool $active = null;
    public ?int $age = null;

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'active' => $this->active,
            'age' => $this->age,
        ];
    }

    public static function getMappedEntityClass(): string
    {
        return 'NullableTestEntity';
    }

    public static function createFromEntity($entity): self
    {
        return new self();
    }
}