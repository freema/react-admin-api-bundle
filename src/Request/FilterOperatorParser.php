<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Request;

/**
 * Parse a react-admin filter key like `createdAt_gte` or `email_contains` into a
 * (field, operator) tuple. Used by ListTrait::applyFilters to translate the
 * incoming query string into Doctrine QueryBuilder predicates.
 *
 * Backward compatible: a bare key like `email` resolves to ('email', 'eq').
 */
final class FilterOperatorParser
{
    /**
     * Operator suffixes ordered longest-first so the parser does not greedy-match
     * a prefix that happens to spell a shorter operator (e.g. `_gte` before `_gt`,
     * `_isNotNull` before `_isNull`).
     *
     * @var list<string>
     */
    public const OPERATORS = [
        '_isNotNull',
        '_startsWith',
        '_endsWith',
        '_contains',
        '_between',
        '_isNull',
        '_nin',
        '_neq',
        '_gte',
        '_lte',
        '_in',
        '_gt',
        '_lt',
    ];

    /**
     * @return array{0: string, 1: string} {field, operator without leading underscore}
     */
    public function parse(string $key): array
    {
        foreach (self::OPERATORS as $suffix) {
            if (str_ends_with($key, $suffix) && strlen($key) > strlen($suffix)) {
                return [substr($key, 0, -strlen($suffix)), substr($suffix, 1)];
            }
        }

        return [$key, 'eq'];
    }

    /**
     * @return array<string>
     */
    public function supportedOperators(): array
    {
        $names = ['eq'];
        foreach (self::OPERATORS as $op) {
            $names[] = substr($op, 1);
        }
        sort($names);

        return $names;
    }
}
