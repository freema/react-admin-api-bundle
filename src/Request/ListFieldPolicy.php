<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Request;

use Doctrine\ORM\Mapping\ClassMetadata;
use Freema\ReactAdminApiBundle\Exception\InvalidListRequestException;

/**
 * Decides which entity fields a list request may filter and sort on.
 *
 * Field names arrive from the client and end up in DQL as `e.<field>`, so a
 * name is accepted only when it is a mapped field of the entity (Doctrine
 * metadata) and the resource exposes it:
 *
 *  - with an explicit allowlist (getFilterableFields() / getSortableFields() on
 *    the repository), only the listed fields;
 *  - otherwise the identifier fields plus the public properties of the
 *    resource's DTO, so a column the API never returns (e.g. `password`) cannot
 *    be filtered or sorted on.
 *
 * Without a DTO class (a repository called directly, not through the bundle's
 * controllers) every mapped field is allowed; injection is still impossible.
 */
final class ListFieldPolicy
{
    private const FIELD_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)*$/';

    /** @var array<class-string, list<string>> */
    private static array $dtoFieldCache = [];

    /**
     * @param ClassMetadata<object> $metadata
     * @param class-string|null     $dtoClass
     * @param list<string>|null     $filterableFields explicit allowlist, null for automatic
     * @param list<string>|null     $sortableFields   explicit allowlist, null for automatic
     */
    public function __construct(
        private readonly ClassMetadata $metadata,
        private readonly ?string $dtoClass = null,
        private readonly ?array $filterableFields = null,
        private readonly ?array $sortableFields = null,
    ) {
    }

    public function isFilterable(string $field): bool
    {
        return $this->isAllowed($field, $this->filterableFields);
    }

    public function isSortable(string $field): bool
    {
        return $this->isAllowed($field, $this->sortableFields);
    }

    /**
     * @throws InvalidListRequestException
     */
    public function assertFilterable(string $field): void
    {
        if (!$this->isFilterable($field)) {
            throw InvalidListRequestException::filterField($field);
        }
    }

    /**
     * @throws InvalidListRequestException
     */
    public function assertSortable(string $field): void
    {
        if (!$this->isSortable($field)) {
            throw InvalidListRequestException::sortField($field);
        }
    }

    /**
     * Normalize a client sort order. Empty means ASC.
     *
     * @return 'ASC'|'DESC'
     *
     * @throws InvalidListRequestException
     */
    public static function sortDirection(?string $order): string
    {
        $normalized = strtoupper(trim((string) $order));

        return match ($normalized) {
            '', 'ASC' => 'ASC',
            'DESC' => 'DESC',
            default => throw InvalidListRequestException::sortOrder((string) $order),
        };
    }

    /**
     * @param list<string>|null $explicit
     */
    private function isAllowed(string $field, ?array $explicit): bool
    {
        if (!preg_match(self::FIELD_PATTERN, $field) || !$this->metadata->hasField($field)) {
            return false;
        }

        if ($explicit !== null) {
            return in_array($field, $explicit, true);
        }

        if (in_array($field, $this->metadata->getIdentifierFieldNames(), true)) {
            return true;
        }

        if ($this->dtoClass === null) {
            return true;
        }

        return in_array($field, self::dtoFields($this->dtoClass), true);
    }

    /**
     * Public, non-static properties of a DTO: the fields the API exposes.
     *
     * @param class-string $dtoClass
     *
     * @return list<string>
     */
    public static function dtoFields(string $dtoClass): array
    {
        if (!isset(self::$dtoFieldCache[$dtoClass])) {
            $fields = [];
            if (class_exists($dtoClass)) {
                foreach ((new \ReflectionClass($dtoClass))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                    if (!$property->isStatic()) {
                        $fields[] = $property->getName();
                    }
                }
            }
            self::$dtoFieldCache[$dtoClass] = $fields;
        }

        return self::$dtoFieldCache[$dtoClass];
    }
}
