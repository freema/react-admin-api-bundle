<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\Request;

use Freema\ReactAdminApiBundle\Request\FilterOperatorParser;
use PHPUnit\Framework\TestCase;

final class FilterOperatorParserTest extends TestCase
{
    private FilterOperatorParser $parser;

    protected function setUp(): void
    {
        $this->parser = new FilterOperatorParser();
    }

    public function test_bare_field_defaults_to_eq(): void
    {
        self::assertSame(['email', 'eq'], $this->parser->parse('email'));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function suffixCases(): array
    {
        return [
            'gt' => ['age_gt', 'age', 'gt'],
            'gte (prefers long match over gt)' => ['age_gte', 'age', 'gte'],
            'lt' => ['age_lt', 'age', 'lt'],
            'lte' => ['age_lte', 'age', 'lte'],
            'neq' => ['status_neq', 'status', 'neq'],
            'in' => ['id_in', 'id', 'in'],
            'nin' => ['id_nin', 'id', 'nin'],
            'between' => ['createdAt_between', 'createdAt', 'between'],
            'isNull' => ['deletedAt_isNull', 'deletedAt', 'isNull'],
            'isNotNull (prefers long match over isNull)' => ['deletedAt_isNotNull', 'deletedAt', 'isNotNull'],
            'contains' => ['name_contains', 'name', 'contains'],
            'startsWith' => ['slug_startsWith', 'slug', 'startsWith'],
            'endsWith' => ['email_endsWith', 'email', 'endsWith'],
        ];
    }

    /**
     * @dataProvider suffixCases
     */
    public function test_parses_known_suffixes(string $key, string $expectedField, string $expectedOp): void
    {
        self::assertSame([$expectedField, $expectedOp], $this->parser->parse($key));
    }

    public function test_never_leaves_empty_field_name(): void
    {
        // '_gte' alone is treated as a bare field (no real field name in front).
        self::assertSame(['_gte', 'eq'], $this->parser->parse('_gte'));
    }

    public function test_supported_operators_are_unique(): void
    {
        $ops = $this->parser->supportedOperators();
        self::assertSame($ops, array_values(array_unique($ops)));
        self::assertContains('eq', $ops);
        self::assertContains('between', $ops);
        self::assertContains('isNotNull', $ops);
    }
}
