<?php

declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Interface;

use Vlp\Mailer\Api\Admin\Request\ListDataRequest;
use Vlp\Mailer\Api\Admin\Result\ListDataResult;

interface DataRepositoryListInterface
{
    public function list(ListDataRequest $dataRequest): ListDataResult;
}
