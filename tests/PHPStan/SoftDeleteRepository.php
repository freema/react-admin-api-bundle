<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Tests\PHPStan;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Freema\ReactAdminApiBundle\SoftDeleteTrait;
use Freema\ReactAdminApiBundle\Tests\Functional\App\Entity\Account;

/**
 * Never instantiated: PHPStan analyses a trait only through a class that
 * uses it, and no repository in the test app uses SoftDeleteTrait.
 *
 * @extends ServiceEntityRepository<Account>
 */
final class SoftDeleteRepository extends ServiceEntityRepository
{
    use SoftDeleteTrait;
}
