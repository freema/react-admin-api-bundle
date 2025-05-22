<?php

declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Interface;

use Vlp\Mailer\Api\Admin\Request\UpdateDataRequest;
use Vlp\Mailer\Api\Admin\Result\UpdateDataResult;

interface DataRepositoryUpdateInterface
{
    public function update(UpdateDataRequest $dataRequest): UpdateDataResult;
}
