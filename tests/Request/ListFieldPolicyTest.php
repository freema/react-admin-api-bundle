<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\Request;

use Doctrine\ORM\Mapping\ClassMetadata;
use Freema\ReactAdminApiBundle\Exception\InvalidListRequestException;
use Freema\ReactAdminApiBundle\Request\ListFieldPolicy;
use Freema\ReactAdminApiBundle\Tests\Functional\App\Dto\AccountDto;
use Freema\ReactAdminApiBundle\Tests\Functional\App\Entity\Account;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ListFieldPolicyTest extends TestCase
{
    /**
     * @return ClassMetadata<Account>
     */
    private function metadata(): ClassMetadata
    {
        $metadata = new ClassMetadata(Account::class);
        $metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
        $metadata->mapField(['fieldName' => 'name', 'type' => 'string']);
        $metadata->mapField(['fieldName' => 'email', 'type' => 'string']);
        $metadata->mapField(['fieldName' => 'password', 'type' => 'string']);

        return $metadata;
    }

    public function test_automatic_mode_allows_the_identifier_and_fields_the_dto_exposes(): void
    {
        $policy = new ListFieldPolicy($this->metadata(), AccountDto::class);

        $this->assertTrue($policy->isFilterable('id'));
        $this->assertTrue($policy->isFilterable('name'));
        $this->assertTrue($policy->isSortable('email'));
        $this->assertFalse($policy->isFilterable('password'));
        $this->assertFalse($policy->isSortable('password'));
    }

    public function test_without_a_dto_every_mapped_field_is_allowed(): void
    {
        $policy = new ListFieldPolicy($this->metadata());

        $this->assertTrue($policy->isFilterable('password'));
        $this->assertFalse($policy->isFilterable('notAField'));
    }

    public function test_an_explicit_allowlist_wins(): void
    {
        $policy = new ListFieldPolicy($this->metadata(), AccountDto::class, ['name'], ['email']);

        $this->assertTrue($policy->isFilterable('name'));
        $this->assertFalse($policy->isFilterable('email'));
        $this->assertFalse($policy->isFilterable('id'));
        $this->assertTrue($policy->isSortable('email'));
        $this->assertFalse($policy->isSortable('name'));
    }

    /**
     * @return iterable<array{string}>
     */
    public static function injectionAttempts(): iterable
    {
        yield ['id, e.password'];
        yield ['name LIKE :x OR e.password'];
        yield ['name)'];
        yield ['password'];
        yield [''];
        yield ['0'];
    }

    #[DataProvider('injectionAttempts')]
    public function test_injection_attempts_are_rejected(string $field): void
    {
        $policy = new ListFieldPolicy($this->metadata(), AccountDto::class);

        $this->expectException(InvalidListRequestException::class);
        $policy->assertSortable($field);
    }

    public function test_sort_direction(): void
    {
        $this->assertSame('ASC', ListFieldPolicy::sortDirection(null));
        $this->assertSame('ASC', ListFieldPolicy::sortDirection(''));
        $this->assertSame('ASC', ListFieldPolicy::sortDirection('asc'));
        $this->assertSame('DESC', ListFieldPolicy::sortDirection('DESC'));

        $this->expectException(InvalidListRequestException::class);
        ListFieldPolicy::sortDirection('ASC, e.password');
    }
}
