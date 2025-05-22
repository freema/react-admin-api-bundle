<?php

declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Interface;

use Vlp\Mailer\Api\Admin\Request\CreateDataRequest;
use Vlp\Mailer\Api\Admin\Result\CreateDataResult;

interface DataRepositoryCreateInterface
{
    public function create(CreateDataRequest $dataRequest): CreateDataResult;
}
