<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\Request;

use Freema\ReactAdminApiBundle\DataProvider\CustomDataProvider;
use Freema\ReactAdminApiBundle\DataProvider\SimpleRestDataProvider;
use Freema\ReactAdminApiBundle\Request\ListDataRequest;
use Freema\ReactAdminApiBundle\Request\Provider\List\CustomProvider;
use Freema\ReactAdminApiBundle\Request\Provider\List\RestProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ListDataRequestTest extends TestCase
{
    public function test_the_page_is_bounded(): void
    {
        $request = new ListDataRequest(limit: 1_000_000, offset: -5);
        $this->assertSame(ListDataRequest::MAX_LIMIT, $request->getLimit());
        $this->assertSame(0, $request->getOffset());

        $this->assertSame(1, (new ListDataRequest(limit: 0))->getLimit());
        $this->assertNull((new ListDataRequest())->getLimit());
    }

    public function test_with_methods_keep_the_rest(): void
    {
        $request = new ListDataRequest(limit: 10, offset: 20, sortField: 'name', sortOrder: 'DESC', filterValues: ['a' => 1, 'b' => 2]);

        $withDto = $request->withDtoClass(\stdClass::class);
        $this->assertSame(\stdClass::class, $withDto->getDtoClass());
        $this->assertSame(10, $withDto->getLimit());
        $this->assertSame(['a' => 1, 'b' => 2], $withDto->getFilterValues());

        $narrowed = $withDto->withFilterValues(['b' => 2]);
        $this->assertSame(['b' => 2], $narrowed->getFilterValues());
        $this->assertSame('{"b":2}', $narrowed->getFilter());
        $this->assertSame(\stdClass::class, $narrowed->getDtoClass());
        $this->assertSame('name', $narrowed->getSortField());
    }

    public function test_providers_cap_the_page_size(): void
    {
        $huge = new Request(['page' => '2', 'per_page' => '1000000', 'sort_field' => 'id']);
        $this->assertSame(ListDataRequest::MAX_LIMIT, (new CustomProvider())->createRequest($huge)->getLimit());
        $this->assertSame(ListDataRequest::MAX_LIMIT, (new CustomProvider())->createRequest($huge)->getOffset());
        $this->assertSame(ListDataRequest::MAX_LIMIT, (new CustomDataProvider())->transformListRequest($huge)->getLimit());

        $noPerPage = new Request(['sort_field' => 'id']);
        $this->assertSame(ListDataRequest::MAX_LIMIT, (new CustomProvider())->createRequest($noPerPage)->getLimit());

        $range = new Request(['range' => '[0, 999999]', 'sort' => '["id","ASC"]']);
        $this->assertSame(ListDataRequest::MAX_LIMIT, (new RestProvider())->createRequest($range)->getLimit());
        $this->assertSame(ListDataRequest::MAX_LIMIT, (new SimpleRestDataProvider())->transformListRequest($range)->getLimit());
    }

    public function test_an_inverted_range_does_not_divide_by_zero(): void
    {
        $request = new Request(['range' => '[5, 4]', 'sort' => '["id","ASC"]']);

        $this->assertSame(1, (new SimpleRestDataProvider())->transformListRequest($request)->getLimit());
    }
}
