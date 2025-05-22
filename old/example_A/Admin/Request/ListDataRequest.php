<?php

declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Request;

use Symfony\Component\HttpFoundation\Request;
use Vlp\Mailer\Api\Admin\Result\ListDataResult;

class ListDataRequest
{
    private const PARAM_PAGE = 'page';

    private const PARAM_PER_PAGE = 'per_page';

    private const PARAM_SORT_FIELD = 'sort_field';

    private const PARAM_SORT_ORDER = 'sort_order';

    private const PARAM_FILTER = 'filter';

    private int $page = 1;

    private int $perPage = 10;

    private string $sortBy = 'id';

    private string $sortOrder = 'ASC';

    private array $filter = [];

    public function __construct(Request $request)
    {
        $query = $request->query->all();

        if (isset($query[self::PARAM_PAGE]) && is_numeric($query[self::PARAM_PAGE])) {
            $this->page = max(1, (int) $query[self::PARAM_PAGE]);
        }

        if (isset($query[self::PARAM_PER_PAGE]) && is_numeric($query[self::PARAM_PER_PAGE])) {
            $this->perPage = max(1, (int) $query[self::PARAM_PER_PAGE]);
        }

        if (isset($query[self::PARAM_SORT_FIELD])) {
            $this->sortBy = $query[self::PARAM_SORT_FIELD];
        }

        if (isset($query[self::PARAM_SORT_ORDER]) && in_array($query[self::PARAM_SORT_ORDER], ['ASC', 'DESC'])) {
            $this->sortOrder = $query[self::PARAM_SORT_ORDER];
        }

        if (isset($query[self::PARAM_FILTER])) {
            $this->filter = is_array($query[self::PARAM_FILTER])
                ? $query[self::PARAM_FILTER]
                : json_decode($query[self::PARAM_FILTER], true) ?? [];
        }
    }

    public function getRangeFrom(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function getRangeTo(): int
    {
        return $this->getRangeFrom() + $this->perPage - 1;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getSortBy(): string
    {
        return $this->sortBy;
    }

    public function getSortOrder(): string
    {
        return $this->sortOrder;
    }

    public function getFilter(): array
    {
        return $this->filter;
    }

    public function createResult(array $data, int $total): ListDataResult
    {
        return new ListDataResult($data, $total);
    }
}
