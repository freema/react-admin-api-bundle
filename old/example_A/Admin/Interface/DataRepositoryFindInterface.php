<?php
declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Interface;

use Vlp\Mailer\Api\Admin\Dto\AdminApiDto;

interface DataRepositoryFindInterface
{
    public function findWithDto(string $id): ?AdminApiDto;
}
