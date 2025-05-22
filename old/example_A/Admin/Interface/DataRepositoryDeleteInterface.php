<?php
declare(strict_types=1);

namespace Vlp\Mailer\Api\Admin\Interface;

use Vlp\Mailer\Api\Admin\Request\DeleteDataRequest;
use Vlp\Mailer\Api\Admin\Request\DeleteManyDataRequest;
use Vlp\Mailer\Api\Admin\Result\DeleteDataResult;

interface DataRepositoryDeleteInterface
{
    public function delete(DeleteDataRequest $dataRequest): DeleteDataResult;

    public function deleteMany(DeleteManyDataRequest $dataRequest): DeleteDataResult;
}
