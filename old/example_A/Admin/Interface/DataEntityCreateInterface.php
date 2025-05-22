<?php

declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Interface;

use Vlp\Mailer\Api\Admin\Dto\AdminApiDto;
use Vlp\Mailer\Api\Admin\Exception\ValidationException;

interface DataEntityCreateInterface
{
    /**
     * @throws ValidationException
     */
    public function setDataFromAdminApiDto(AdminApiDto $dto): void;
}
