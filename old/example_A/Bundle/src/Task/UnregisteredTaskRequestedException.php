<?php

declare(strict_types=1);

namespace VLM\TaskWorkerBundle\Task;

use LogicException;

class UnregisteredTaskRequestedException extends LogicException
{
}
